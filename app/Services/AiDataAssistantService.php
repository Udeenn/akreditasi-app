<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AiDataAssistantService — Orchestrator chat AI berbasis Gemini dengan eksekusi lokal privacy-safe.
 */
class AiDataAssistantService
{
    public function __construct(
        private AiSafeQueryService $queryService
    ) {}

    public function ask(string $question, array $history = []): array
    {
        $apiKey = (string) config('ai.api_key', '');
        if ($apiKey === '') {
            throw new RuntimeException('API Key AI belum dikonfigurasi. Tambahkan GEMINI_API_KEY di file .env terlebih dahulu.');
        }

        $cacheKey = $this->buildPlanCacheKey($question, $history);
        if ($plan = Cache::get($cacheKey)) {
            if (($plan['type'] ?? '') === 'text') {
                return $this->emptyResult((string) ($plan['answer'] ?? ''), null, cached: true);
            }
            if (($plan['type'] ?? '') === 'tools' && !empty($plan['calls'])) {
                [$merged, $calls] = $this->executeToolCalls((array) $plan['calls']);
                $rowsHash = sha1((string) json_encode($merged['rows']));
                $answer = (!empty($plan['answer']) && ($plan['rows_hash'] ?? '') === $rowsHash)
                    ? (string) $plan['answer']
                    : $this->buildLocalNarrative($calls[0]['name'] ?? '', $calls[0]['args'] ?? [], $merged);
                return $this->formatResult($merged, $answer, cached: true, calls: $calls);
            }
        }

        $model   = (string) config('ai.model', 'gemini-3.5-flash-lite');
        $timeout = (int) config('ai.timeout', 30);
        $url     = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $contents = $this->buildGeminiContents($question, $history);
        $data = $this->callGemini($url, $apiKey, $timeout, [
            'contents'         => $contents,
            'tools'            => [['functionDeclarations' => $this->getToolDeclarations()]],
            'generationConfig' => ['temperature' => 0.1],
        ], throwOnError: true);

        $candidate = $data['candidates'][0]['content'] ?? [];
        $funcCalls = [];
        $textParts = [];

        foreach ($candidate['parts'] ?? [] as $part) {
            if (isset($part['functionCall'])) {
                $funcCalls[] = $part['functionCall'];
            }
            if (isset($part['text'])) {
                $textParts[] = $part['text'];
            }
        }

        if (empty($funcCalls)) {
            $textAnswer = trim(implode("\n", $textParts)) ?: 'Silakan ajukan pertanyaan yang lebih spesifik terkait data anggota, koleksi, kunjungan, atau peminjaman.';
            Cache::put($cacheKey, ['type' => 'text', 'answer' => $textAnswer], now()->endOfDay());
            return $this->emptyResult($textAnswer);
        }

        try {
            [$merged, $calls, $responseParts] = $this->executeToolCalls($funcCalls);
        } catch (\Throwable $e) {
            return $this->emptyResult(
                'Query yang disusun tidak dapat dijalankan karena alasan keamanan/validasi: ' . $e->getMessage() . '. Coba perjelas pertanyaan Anda.',
                implode(' | ', array_map(fn($c) => (string) ($c['name'] ?? ''), $funcCalls))
            );
        }

        // PII & listing langsung (anggota/katalog) dirangkum di PHP; statistik/perbandingan/SQL non-PII dinarasikan Gemini agar analitis
        $onlySimpleListing = count($calls) === 1 && in_array($calls[0]['name'], ['find_members', 'top_visitors', 'search_catalog'], true);
        if (!empty($merged['contains_pii']) || $onlySimpleListing) {
            Cache::put($cacheKey, ['type' => 'tools', 'calls' => $calls], now()->endOfDay());
            return $this->formatResult($merged, $this->buildLocalNarrative($calls[0]['name'], $calls[0]['args'], $merged), calls: $calls);
        }

        $contents[] = $candidate;
        $contents[] = ['role' => 'user', 'parts' => $responseParts];

        $followUp = $this->callGemini($url, $apiKey, $timeout, [
            'contents'         => $contents,
            'generationConfig' => ['temperature' => 0.3],
        ]);

        $answerText = '';
        foreach ($followUp['candidates'][0]['content']['parts'] ?? [] as $p) {
            $answerText .= $p['text'] ?? '';
        }

        $finalAnswer = trim($answerText) ?: $this->buildLocalNarrative($calls[0]['name'], $calls[0]['args'], $merged);
        Cache::put($cacheKey, [
            'type'      => 'tools',
            'calls'     => $calls,
            'answer'    => $finalAnswer,
            'rows_hash' => sha1((string) json_encode($merged['rows'])),
        ], now()->endOfDay());

        return $this->formatResult($merged, $finalAnswer, calls: $calls);
    }

    private function callGemini(string $url, string $apiKey, int $timeout, array $body, bool $throwOnError = false): array
    {
        $body['systemInstruction'] = ['parts' => [['text' => $this->getSystemPrompt()]]];

        $response = Http::timeout($timeout)
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->post($url, $body);

        if (!$response->successful()) {
            Log::error('Gemini API error', ['status' => $response->status(), 'body' => $response->body()]);
            if ($throwOnError) {
                throw new RuntimeException('Gagal menghubungi Gemini API: ' . ($response->json('error.message') ?? ('HTTP ' . $response->status())));
            }
            return [];
        }

        return $response->json() ?? [];
    }

    private function buildPlanCacheKey(string $question, array $history): string
    {
        $normQ = mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $question)));
        $normH = array_map(
            fn($m) => [($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user', mb_strtolower(trim((string) ($m['text'] ?? '')))],
            array_values(array_filter(array_slice($history, -4), fn($m) => !empty($m['role']) && !empty($m['text'])))
        );
        return 'ai_plan:v4:' . date('Y-m-d') . ':' . sha1(json_encode(['q' => $normQ, 'h' => $normH]));
    }

    private function emptyResult(string $answer, ?string $querySummary = null, bool $cached = false): array
    {
        return [
            'answer'        => $answer,
            'columns'       => [],
            'rows'          => [],
            'total_count'   => 0,
            'query_summary' => $querySummary,
            'contains_pii'  => false,
            'cached'        => $cached,
        ];
    }

    private function formatResult(array $result, string $answer, bool $cached = false, array $calls = []): array
    {
        return [
            'answer'        => $answer,
            'columns'       => $result['columns'],
            'rows'          => $result['rows'],
            'total_count'   => $result['total_count'],
            'query_summary' => $result['query_summary'],
            'contains_pii'  => $result['contains_pii'],
            'cached'        => $cached,
            'calls'         => $calls,
        ];
    }

    private function executeToolCalls(array $funcCalls): array
    {
        $calls = [];
        $results = [];
        $responseParts = [];

        foreach ($funcCalls as $fc) {
            $name = (string) ($fc['name'] ?? '');
            $args = (array) ($fc['args'] ?? []);
            $res  = $this->executeTool($name, $args);

            $calls[]   = ['name' => $name, 'args' => $args];
            $results[] = $res;

            $fr = ['name' => $name, 'response' => $this->buildPrivacySafeToolResponse($res)];
            if (!empty($fc['id'])) {
                $fr['id'] = $fc['id'];
            }
            $responseParts[] = ['functionResponse' => $fr];
        }

        return [$this->mergeToolResults($results), $calls, $responseParts];
    }

    private function mergeToolResults(array $results): array
    {
        if (count($results) === 1) {
            return $results[0];
        }

        $containsPii = false;
        $summaries   = [];
        $allHavePeriode = !empty($results);

        foreach ($results as $r) {
            $containsPii = $containsPii || !empty($r['contains_pii']);
            if (!empty($r['query_summary'])) {
                $summaries[] = $r['query_summary'];
            }
            if (!in_array('Periode', $r['columns'] ?? [], true)) {
                $allHavePeriode = false;
            }
        }

        if ($allHavePeriode) {
            $cols = ['Periode'];
            $byPeriode = [];
            foreach ($results as $r) {
                foreach ($r['columns'] as $c) {
                    if ($c !== 'Periode' && !in_array($c, $cols, true)) {
                        $cols[] = $c;
                    }
                }
                foreach ($r['rows'] as $row) {
                    $p = (string) ($row['Periode'] ?? '-');
                    $byPeriode[$p] = ($byPeriode[$p] ?? ['Periode' => $p]) + $row;
                }
            }
            $rows = array_values($byPeriode);
            return [
                'contains_pii'  => $containsPii,
                'total_count'   => count($rows),
                'columns'       => $cols,
                'rows'          => $rows,
                'query_summary' => implode(' | ', $summaries),
            ];
        }

        $primary = $results[0];
        $primary['contains_pii']  = $containsPii;
        $primary['query_summary'] = implode(' | ', $summaries);
        return $primary;
    }

    private function executeTool(string $name, array $args): array
    {
        return match ($name) {
            'find_members'      => $this->queryService->findMembers($args),
            'search_catalog'    => $this->queryService->searchCatalog($args),
            'visit_summary'     => $this->queryService->visitSummary($args),
            'top_visitors'      => $this->queryService->topVisitors($args),
            'borrowing_summary' => $this->queryService->borrowingSummary($args),
            'run_readonly_sql'  => $this->queryService->executeSafeSelect(
                (string) ($args['connection'] ?? 'mysql2'),
                (string) ($args['sql'] ?? '')
            ),
            default => throw new RuntimeException("Tool '{$name}' tidak dikenali."),
        };
    }

    private function buildPrivacySafeToolResponse(array $result): array
    {
        $base = [
            'status'      => 'ok',
            'total_found' => $result['total_count'],
            'columns'     => $result['columns'],
        ];

        // Defense-in-depth: cegah baris PII dikirim ke LLM dalam kondisi apa pun
        if (!empty($result['contains_pii'])) {
            return $base + ['contains_pii' => true];
        }

        return $base + ['contains_pii' => false, 'sample_rows' => array_slice($result['rows'], 0, 15)];
    }

    private function buildLocalNarrative(string $toolName, array $args, array $result): string
    {
        $total = (int) ($result['total_count'] ?? 0);
        $shown = count($result['rows'] ?? []);

        if ($shown === 0 && $total === 0) {
            return 'Tidak ditemukan data yang cocok dengan permintaan Anda. Coba ubah kata kunci, prodi, atau rentang tanggalnya.';
        }

        $rows = $result['rows'] ?? [];
        if (isset($rows[2]['Periode']) && str_starts_with((string) $rows[2]['Periode'], 'Perubahan')) {
            return $this->buildComparisonNarrative($rows);
        }

        $limitNote = $shown < $total
            ? " Berikut ditampilkan **{$shown}** baris teratas pada tabel (klik **Export CSV** untuk mengunduh)."
            : ' Rincian lengkapnya ditampilkan pada tabel di bawah.';

        return match ($toolName) {
            'find_members' => 'Ditemukan **' . $total . '** data **' . ($args['member_type'] ?? 'anggota') . '**'
                . (!empty($args['prodi']) ? " prodi **{$args['prodi']}**" : '') . '.' . $limitNote,

            'top_visitors' => 'Ditemukan **' . $total . '** pengunjung kategori **' . ($args['member_type'] ?? 'pengunjung')
                . '** pada periode **' . ($args['start_date'] ?? date('Y-01-01')) . ' s/d ' . ($args['end_date'] ?? date('Y-m-d')) . '**.' . $limitNote,

            'search_catalog' => (function () use ($args, $total, $limitNote) {
                $cat    = ($args['category'] ?? 'all') === 'all' ? 'koleksi' : $args['category'];
                $status = strtolower((string) ($args['item_status'] ?? 'available'));
                $tag    = match ($status) {
                    'withdrawn' => ' (withdrawn)',
                    'lost'      => ' (hilang)',
                    'damaged'   => ' (rusak)',
                    default     => '',
                };
                return 'Ditemukan **' . $total . '** ' . ($status === 'available' ? 'judul' : 'eksemplar') . " **{$cat}**{$tag} yang sesuai." . $limitNote;
            })(),

            'visit_summary' => (function () use ($result, $total) {
                $rows = $result['rows'] ?? [];
                if (isset($rows[2]['Periode']) && str_starts_with((string) $rows[2]['Periode'], 'Perubahan')) {
                    return "Perbandingan kunjungan periode **{$rows[0]['Periode']}** (**{$rows[0]['Total_Kunjungan']}**) vs **{$rows[1]['Periode']}** (**{$rows[1]['Total_Kunjungan']}**), selisih **{$rows[2]['Total_Kunjungan']}**.";
                }
                $sum = isset($rows[0]['Total_Kunjungan'])
                    ? (int) $rows[0]['Total_Kunjungan']
                    : array_sum(array_map(fn($r) => (int) ($r['Jumlah_Kunjungan'] ?? 0), $rows));
                return "Total kunjungan tercatat sebanyak **{$sum} kali** dalam **{$total}** periode pada tabel di bawah.";
            })(),

            'borrowing_summary' => (function () use ($args, $result, $shown) {
                $mode = $args['mode'] ?? 'summary';
                $row  = $result['rows'][0] ?? [];
                if ($mode === 'ongoing') {
                    return "Saat ini tercatat **{$row['Total_Sedang_Dipinjam']}** peminjaman berlangsung (**{$row['Terlambat_Jatuh_Tempo']}** melewati jatuh tempo).";
                }
                if ($mode === 'top_books') {
                    return "Berikut **{$shown}** judul buku paling sering dipinjam pada periode terpilih.";
                }
                return "Ringkasan sirkulasi periode **{$row['Periode']}**: **{$row['Pinjam_Baru']}** pinjam baru, **{$row['Perpanjang']}** perpanjang, **{$row['Kembali']}** kembali (**{$row['Peminjam_Unik']}** peminjam unik).";
            })(),

            default => "Ditemukan total **{$total}** data" . ($shown < $total ? " (menampilkan {$shown} baris pertama pada tabel di bawah)." : '.'),
        };
    }

    private function buildComparisonNarrative(array $rows): string
    {
        [$prev, $curr, $delta] = [$rows[0], $rows[1], $rows[2]];
        $labels = [
            'Pinjam_Baru'      => 'Peminjaman Baru (Issue)',
            'Perpanjang'       => 'Perpanjangan (Renewal)',
            'Kembali'          => 'Pengembalian (Return)',
            'Peminjam_Unik'    => 'Peminjam Unik',
            'Kunjungan_Utama'  => 'Kunjungan Utama',
            'Kunjungan_Corner' => 'Kunjungan Corner',
            'Total_Kunjungan'  => 'Total Kunjungan',
        ];

        $lines = ["Berdasarkan data statistik perpustakaan antara periode **{$prev['Periode']}** dan **{$curr['Periode']}**:"];
        $naik = [];
        $turun = [];

        foreach ($curr as $col => $currVal) {
            if ($col === 'Periode') {
                continue;
            }
            $p = (int) ($prev[$col] ?? 0);
            $c = (int) $currVal;
            $label = $labels[$col] ?? str_replace('_', ' ', (string) $col);
            $pFmt = number_format($p, 0, ',', '.');
            $cFmt = number_format($c, 0, ',', '.');
            $dText = (string) ($delta[$col] ?? ($c - $p));

            if ($c > $p) {
                $lines[] = "* **{$label}:** mengalami kenaikan dari **{$pFmt}** menjadi **{$cFmt}** (**{$dText}**).";
                $naik[] = $label;
            } elseif ($c < $p) {
                $lines[] = "* **{$label}:** mengalami penurunan dari **{$pFmt}** menjadi **{$cFmt}** (**{$dText}**).";
                $turun[] = $label;
            } else {
                $lines[] = "* **{$label}:** stabil di angka **{$cFmt}**.";
            }
        }

        $kesimpulan = [];
        if (!empty($naik)) {
            $kesimpulan[] = 'indikator yang cenderung **naik** meliputi ' . implode(', ', $naik);
        }
        if (!empty($turun)) {
            $kesimpulan[] = 'sedangkan yang mengalami **penurunan** meliputi ' . implode(', ', $turun);
        }
        if (!empty($kesimpulan)) {
            $lines[] = "\n**Analisis Tren:**\nSecara keseluruhan dibanding periode yang sama tahun lalu, " . implode(', ', $kesimpulan) . '.';
        }

        return implode("\n", $lines);
    }

    private function buildGeminiContents(string $question, array $history): array
    {
        $contents = [];
        foreach (array_slice($history, -4) as $msg) {
            if (!empty($msg['role']) && !empty($msg['text'])) {
                $contents[] = [
                    'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => (string) $msg['text']]],
                ];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $question]]];

        return $contents;
    }

    private function getSystemPrompt(): string
    {
        $today  = date('Y-m-d');
        $schema = AiSafeQueryService::getSchemaPrompt();

        return <<<PROMPT
Asisten Data Perpustakaan UMS (Admin). Hari ini: {$today}.
Pilih tool terbaik: utamakan `find_members`, `top_visitors`, `search_catalog` (gunakan parameter `prodi` dan `item_status` untuk filter prodi maupun status `withdrawn`/`lost`/`damaged`), `visit_summary`, `borrowing_summary` (isi `compare_start_date` & `compare_end_date` jika diminta membandingkan periode, mis. tahun lalu vs tahun ini) sebelum `run_readonly_sql`.
Wajib pakai `top_visitors` untuk ranking pengunjung per kategori/prodi.
Saat merangkum hasil statistik atau perbandingan, beri narasi analitis bahasa Indonesia yang kaya dan jelas (soroti kecenderungan naik/turun, persentase selisih, dan poin menonjol sesuai data tanpa mengarang angka). Tabel sudah dirender otomatis di UI, jadi JANGAN membuat ulang tabel Markdown (|---|) di dalam teks jawaban.
Skema:
{$schema}
PROMPT;
    }

    private function getToolDeclarations(): array
    {
        return [
            [
                'name'        => 'find_members',
                'description' => 'Cari anggota (alumni/mahasiswa/dosen/tendik/lb/ksp) per prodi, nama/NIM, atau status.',
                'parameters'  => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'member_type' => ['type' => 'STRING', 'description' => 'alumni|mahasiswa|dosen|tendik|lb|ksp|all'],
                        'prodi'       => ['type' => 'STRING', 'description' => 'Kode/nama prodi, mis. L200, Teknik Informatika'],
                        'keyword'     => ['type' => 'STRING', 'description' => 'Kata kunci nama atau NIM'],
                        'status'      => ['type' => 'STRING', 'description' => 'active|expired|all'],
                        'limit'       => ['type' => 'INTEGER', 'description' => '1-100, default 50'],
                    ],
                ],
            ],
            [
                'name'        => 'search_catalog',
                'description' => 'Cari koleksi buku/ebook/jurnal/prosiding/referensi Koha (aktif maupun withdrawn/hilang/rusak).',
                'parameters'  => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'keyword'     => ['type' => 'STRING', 'description' => 'Kata kunci judul/pengarang/penerbit (kosongkan jika filter utama hanya prodi)'],
                        'category'    => ['type' => 'STRING', 'description' => 'textbook|referensi|ebook|jurnal|prosiding|all'],
                        'prodi'       => ['type' => 'STRING', 'description' => 'Kode atau nama prodi, mis. D400, Teknik Elektro, L200, PGSD'],
                        'item_status' => ['type' => 'STRING', 'description' => 'available|withdrawn|lost|damaged|all (default available)'],
                        'year_from'   => ['type' => 'INTEGER', 'description' => 'Tahun terbit minimum'],
                        'limit'       => ['type' => 'INTEGER', 'description' => '1-100, default 30'],
                    ],
                ],
            ],
            [
                'name'        => 'visit_summary',
                'description' => 'Jumlah kunjungan perpustakaan per hari, bulan, total, atau perbandingan dua periode.',
                'parameters'  => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'start_date'         => ['type' => 'STRING', 'description' => 'YYYY-MM-DD periode utama'],
                        'end_date'           => ['type' => 'STRING', 'description' => 'YYYY-MM-DD periode utama'],
                        'compare_start_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD periode pembanding opsional (mis. tahun lalu)'],
                        'compare_end_date'   => ['type' => 'STRING', 'description' => 'YYYY-MM-DD akhir periode pembanding opsional'],
                        'group_by'           => ['type' => 'STRING', 'description' => 'day|month|total'],
                    ],
                ],
            ],
            [
                'name'        => 'top_visitors',
                'description' => 'Ranking pengunjung tersering lintas DB menurut kategori/prodi.',
                'parameters'  => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'member_type' => ['type' => 'STRING', 'description' => 'dosen|mahasiswa|alumni|tendik|pustakawan|lb|all'],
                        'prodi'       => ['type' => 'STRING', 'description' => 'Kode prodi opsional'],
                        'start_date'  => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                        'end_date'    => ['type' => 'STRING', 'description' => 'YYYY-MM-DD'],
                        'limit'       => ['type' => 'INTEGER', 'description' => '1-100, default 20'],
                    ],
                ],
            ],
            [
                'name'        => 'borrowing_summary',
                'description' => 'Statistik peminjaman (bisa bandingkan 2 periode), pinjaman berlangsung, atau buku terlaris.',
                'parameters'  => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'mode'               => ['type' => 'STRING', 'description' => 'summary|ongoing|top_books'],
                        'start_date'         => ['type' => 'STRING', 'description' => 'YYYY-MM-DD periode utama'],
                        'end_date'           => ['type' => 'STRING', 'description' => 'YYYY-MM-DD periode utama'],
                        'compare_start_date' => ['type' => 'STRING', 'description' => 'YYYY-MM-DD periode pembanding opsional (mis. tahun lalu)'],
                        'compare_end_date'   => ['type' => 'STRING', 'description' => 'YYYY-MM-DD akhir periode pembanding opsional'],
                    ],
                ],
            ],
            [
                'name'        => 'run_readonly_sql',
                'description' => 'Fallback SELECT read-only ke mysql atau mysql2 jika tool lain tidak cukup.',
                'parameters'  => [
                    'type'       => 'OBJECT',
                    'properties' => [
                        'connection' => ['type' => 'STRING', 'description' => 'mysql2|mysql'],
                        'sql'        => ['type' => 'STRING', 'description' => 'Single SELECT max LIMIT 100'],
                    ],
                    'required' => ['connection', 'sql'],
                ],
            ],
        ];
    }
}
