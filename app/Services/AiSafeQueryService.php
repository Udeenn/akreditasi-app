<?php

namespace App\Services;

use App\Helpers\CnClassHelperr;
use App\Helpers\QueryHelper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * AiSafeQueryService — Eksekutor query read-only tervalidasi untuk Asisten Data AI.
 */
class AiSafeQueryService
{
    private const MAX_LIMIT = 100;

    private const ALLOWED_TABLES = [
        'mysql' => ['visitorhistory', 'visitorcorner'],
        'mysql2' => [
            'borrowers',
            'borrower_attributes',
            'authorised_values',
            'items',
            'biblio',
            'biblioitems',
            'issues',
            'old_issues',
            'statistics',
            'itemtypes',
        ],
    ];

    private const FORBIDDEN_COLUMNS = [
        'password', 'secret', 'token', 'remember_token',
        'userid', 'email', 'emailpro', 'b_email',
        'phone', 'phonepro', 'mobile', 'b_phone', 'smsalertnumber',
        'address', 'address2', 'streetnumber', 'b_address', 'b_address2',
        'dateofbirth', 'sex', 'contactname', 'altcontactphone',
    ];

    public function runPaginatedTool(string $name, array $args, int $page = 1, int $perPage = 50, string $sortBy = '', string $sortDir = 'desc', bool $forExport = false): array
    {
        $args['_page']       = max(1, $page);
        $args['_per_page']   = $perPage;
        $args['_sort_by']    = $sortBy;
        $args['_sort_dir']   = $sortDir;
        $args['_for_export'] = $forExport;

        return match ($name) {
            'find_members'   => $this->findMembers($args),
            'search_catalog' => $this->searchCatalog($args),
            'top_visitors'   => $this->topVisitors($args),
            default => throw new InvalidArgumentException("Tool '{$name}' tidak mendukung pagination."),
        };
    }

    public function findMembers(array $args): array
    {
        $memberType = strtolower(trim((string) ($args['member_type'] ?? 'all')));
        $prodi      = trim((string) ($args['prodi'] ?? ''));
        $keyword    = trim((string) ($args['keyword'] ?? ''));
        $status     = strtolower(trim((string) ($args['status'] ?? 'all')));
        [$page, $perPage, $offset, $sortBy, $sortDir] = $this->paginationArgs($args, 50);

        $query = $this->memberQuery()
            ->leftJoin('authorised_values as av', function ($join) {
                $join->on('av.authorised_value', '=', DB::raw("COALESCE(NULLIF(TRIM(ba.attribute), ''), LEFT(b.cardnumber, 4))"))
                    ->where('av.category', '=', 'PRODI');
            });

        $this->applyMemberTypeFilter($query, $memberType);

        if ($prodi !== '') {
            $upperProdi = strtoupper($prodi);
            $query->where(function ($q) use ($prodi, $upperProdi) {
                $q->where('ba.attribute', '=', $upperProdi)
                  ->orWhere('b.cardnumber', 'like', $upperProdi . '%')
                  ->orWhere('av.lib', 'like', '%' . $prodi . '%');
            });
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('b.cardnumber', 'like', '%' . $keyword . '%')
                  ->orWhere('b.firstname', 'like', '%' . $keyword . '%')
                  ->orWhere('b.surname', 'like', '%' . $keyword . '%');
            });
        }

        $today = date('Y-m-d');
        if ($status === 'active') {
            $query->where('b.dateexpiry', '>=', $today);
        } elseif ($status === 'expired') {
            $query->where('b.dateexpiry', '<', $today);
        }

        $totalCount = (clone $query)->count();

        $sortMap = [
            'NIM_atau_Kartu' => 'b.cardnumber',
            'Nama'           => 'b.firstname',
            'Kategori'       => 'b.categorycode',
            'Kode_Prodi'     => 'ba.attribute',
            'Nama_Prodi'     => 'av.lib',
            'Tgl_Daftar'     => 'b.dateenrolled',
            'Tgl_Kadaluarsa' => 'b.dateexpiry',
        ];
        $this->applySafeOrder($query, $sortMap, $sortBy, $sortDir, 'b.borrowernumber', 'desc');

        $rows = $query->select([
            'b.cardnumber as NIM_atau_Kartu',
            DB::raw("TRIM(CONCAT(COALESCE(b.firstname, ''), ' ', COALESCE(b.surname, ''))) as Nama"),
            'b.categorycode as Kategori',
            DB::raw("COALESCE(NULLIF(TRIM(ba.attribute), ''), '-') as Kode_Prodi"),
            DB::raw("COALESCE(NULLIF(TRIM(av.lib), ''), '-') as Nama_Prodi"),
            'b.dateenrolled as Tgl_Daftar',
            'b.dateexpiry as Tgl_Kadaluarsa',
        ])
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn($r) => (array) $r)
            ->all();

        return [
            'contains_pii'  => true,
            'total_count'   => $totalCount,
            'columns'       => ['NIM_atau_Kartu', 'Nama', 'Kategori', 'Kode_Prodi', 'Nama_Prodi', 'Tgl_Daftar', 'Tgl_Kadaluarsa'],
            'rows'          => $rows,
            'query_summary' => "Tool find_members (type={$memberType}, prodi=" . ($prodi ?: '-') . ", keyword=" . ($keyword ?: '-') . ", status={$status})",
        ];
    }

    public function searchCatalog(array $args): array
    {
        $keyword    = trim((string) ($args['keyword'] ?? ''));
        $prodi      = trim((string) ($args['prodi'] ?? ''));
        $category   = strtolower(trim((string) ($args['category'] ?? 'all')));
        $itemStatus = strtolower(trim((string) ($args['item_status'] ?? 'available')));
        $yearFrom   = isset($args['year_from']) ? (int) $args['year_from'] : null;
        [$page, $perPage, $offset, $sortBy, $sortDir] = $this->paginationArgs($args, $itemStatus === 'available' ? 30 : 50);

        $query = DB::connection('mysql2')->table('items')
            ->join('biblio as b', 'b.biblionumber', '=', 'items.biblionumber')
            ->leftJoin('biblioitems as bi', 'bi.biblionumber', '=', 'items.biblionumber');

        match ($itemStatus) {
            'withdrawn' => $query->where('items.withdrawn', '!=', 0),
            'lost'      => $query->where('items.itemlost', '!=', 0),
            'damaged'   => $query->where('items.damaged', '!=', 0),
            'all'       => null,
            default     => $query->where('items.itemlost', 0)->where('items.withdrawn', 0),
        };

        match ($category) {
            'textbook', 'buku' => $query->whereIn('items.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC'])->where('items.ccode', 'not like', 'R%'),
            'referensi'        => $query->whereIn('items.itype', ['BKS', 'BKSA', 'BKSCA', 'BKSC'])->where('items.ccode', 'like', 'R%'),
            'ebook'            => $query->where('items.itype', 'EB'),
            'jurnal'           => $query->whereIn('items.itype', ['JR', 'JRA', 'JRT', 'EJ']),
            'prosiding'        => $query->whereIn('items.itype', ['PR', 'EPR']),
            default            => null,
        };

        $resolvedProdi = $prodi;
        if ($prodi !== '' && strtoupper($prodi) !== 'ALL') {
            $rules = $this->resolveProdiCnRules($prodi, $resolvedProdi);
            if (!empty($rules)) {
                QueryHelper::applyCnClassRules($query, $rules);
            }
        }

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('b.title', 'like', '%' . $keyword . '%')
                  ->orWhere('b.author', 'like', '%' . $keyword . '%')
                  ->orWhere('bi.publishercode', 'like', '%' . $keyword . '%')
                  ->orWhere('items.itemcallnumber', 'like', '%' . $keyword . '%');
            });
        }

        if ($yearFrom) {
            $query->whereRaw('CAST(bi.publicationyear AS UNSIGNED) >= ?', [$yearFrom]);
        }

        $summary = "Tool search_catalog (status={$itemStatus}, category={$category}, prodi=" . ($resolvedProdi ?: 'all') . ", keyword=" . ($keyword ?: '-') . ')';

        if ($itemStatus !== 'available') {
            $totalItems = (clone $query)->count('items.itemnumber');
            $itemSortMap = [
                'Biblionumber' => 'items.biblionumber',
                'Judul'        => 'b.title',
                'Pengarang'    => 'b.author',
                'Barcode'      => 'items.barcode',
                'No_Panggil'   => 'items.itemcallnumber',
                'Withdrawn'    => 'items.withdrawn',
                'Withdrawn_On' => 'items.withdrawn_on',
            ];
            $this->applySafeOrder($query, $itemSortMap, $sortBy, $sortDir, 'items.biblionumber', 'asc');

            $rows = $query->select([
                'items.biblionumber as Biblionumber',
                'b.title as Judul',
                DB::raw("COALESCE(NULLIF(TRIM(b.author), ''), '-') as Pengarang"),
                DB::raw("COALESCE(NULLIF(TRIM(items.barcode), ''), '-') as Barcode"),
                DB::raw("COALESCE(NULLIF(TRIM(items.itemcallnumber), ''), '-') as No_Panggil"),
                'items.withdrawn as Withdrawn',
                DB::raw("COALESCE(CAST(items.withdrawn_on AS CHAR), '-') as Withdrawn_On"),
            ])
                ->offset($offset)
                ->limit($perPage)
                ->get()
                ->map(fn($r) => (array) $r)
                ->all();

            return [
                'contains_pii'  => false,
                'total_count'   => $totalItems,
                'columns'       => ['Biblionumber', 'Judul', 'Pengarang', 'Barcode', 'No_Panggil', 'Withdrawn', 'Withdrawn_On'],
                'rows'          => $rows,
                'query_summary' => $summary,
            ];
        }

        $totalTitles = (clone $query)->distinct()->count('items.biblionumber');
        $titleSortMap = [
            'Judul'      => 'b.title',
            'Pengarang'  => 'b.author',
            'Penerbit'   => 'bi.publishercode',
            'Tahun'      => 'bi.publicationyear',
            'No_Panggil' => 'No_Panggil',
            'Eksemplar'  => 'Eksemplar',
        ];
        $this->applySafeOrder($query, $titleSortMap, $sortBy, $sortDir, 'Eksemplar', 'desc');

        $rows = $query->select([
            'b.title as Judul',
            DB::raw("COALESCE(NULLIF(TRIM(b.author), ''), '-') as Pengarang"),
            DB::raw("COALESCE(NULLIF(TRIM(bi.publishercode), ''), '-') as Penerbit"),
            DB::raw("COALESCE(NULLIF(TRIM(bi.publicationyear), ''), '-') as Tahun"),
            DB::raw("MIN(items.itemcallnumber) as No_Panggil"),
            DB::raw('COUNT(items.itemnumber) as Eksemplar'),
        ])
            ->groupBy('b.biblionumber', 'b.title', 'b.author', 'bi.publishercode', 'bi.publicationyear')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn($r) => (array) $r)
            ->all();

        return [
            'contains_pii'  => false,
            'total_count'   => $totalTitles,
            'columns'       => ['Judul', 'Pengarang', 'Penerbit', 'Tahun', 'No_Panggil', 'Eksemplar'],
            'rows'          => $rows,
            'query_summary' => $summary,
        ];
    }

    public function visitSummary(array $args): array
    {
        [$startDate, $endDate, $start, $end] = $this->dateRange($args, date('Y-m-01'));
        $hasCompare = !empty($args['compare_start_date']) && !empty($args['compare_end_date']);
        $groupBy = $hasCompare ? 'total' : (in_array($args['group_by'] ?? 'day', ['day', 'month', 'total'], true) ? $args['group_by'] : 'day');

        if ($groupBy === 'total') {
            $fetchVisit = function (string $label, string $s, string $e): array {
                $h = DB::connection('mysql')->table('visitorhistory')->whereBetween('visittime', [$s, $e])->count();
                $c = DB::connection('mysql')->table('visitorcorner')->whereBetween('visittime', [$s, $e])->count();
                return ['Periode' => $label, 'Kunjungan_Utama' => $h, 'Kunjungan_Corner' => $c, 'Total_Kunjungan' => $h + $c];
            };

            $curr = $fetchVisit("{$startDate} s/d {$endDate}", $start, $end);
            if ($hasCompare) {
                [$cStart, $cEnd, $cs, $ce] = $this->dateRange(['start_date' => $args['compare_start_date'], 'end_date' => $args['compare_end_date']], date('Y-01-01', strtotime('-1 year')));
                $prev = $fetchVisit("{$cStart} s/d {$cEnd}", $cs, $ce);
                $rows = [$prev, $curr, $this->buildDeltaRow($prev, $curr, ['Kunjungan_Utama', 'Kunjungan_Corner', 'Total_Kunjungan'])];
                $summary = "Tool visit_summary (compare {$cStart}..{$cEnd} vs {$startDate}..{$endDate})";
            } else {
                $rows = [$curr];
                $summary = "Tool visit_summary ({$startDate} s/d {$endDate}, group=total)";
            }

            return [
                'contains_pii'  => false,
                'total_count'   => count($rows),
                'columns'       => ['Periode', 'Kunjungan_Utama', 'Kunjungan_Corner', 'Total_Kunjungan'],
                'rows'          => $rows,
                'query_summary' => $summary,
            ];
        }

        $fmt = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $merged = $this->visitCountsBy("DATE_FORMAT(visittime, '{$fmt}')", $start, $end);
        ksort($merged);

        $rows = [];
        foreach ($merged as $periode => $total) {
            $rows[] = ['Periode' => $periode, 'Jumlah_Kunjungan' => $total];
        }

        return [
            'contains_pii'  => false,
            'total_count'   => count($merged),
            'columns'       => ['Periode', 'Jumlah_Kunjungan'],
            'rows'          => array_slice($rows, -self::MAX_LIMIT),
            'query_summary' => "Tool visit_summary ({$startDate} s/d {$endDate}, group={$groupBy})",
        ];
    }

    /**
     * Tool 3b: Daftar pengunjung paling aktif lintas DB (mysql visitor + mysql2 borrowers).
     */
    public function topVisitors(array $args): array
    {
        $memberType = strtolower(trim((string) ($args['member_type'] ?? 'all')));
        $prodi      = trim((string) ($args['prodi'] ?? ''));
        [$startDate, $endDate, $start, $end] = $this->dateRange($args, date('Y-01-01'));
        [$page, $perPage, $offset, $sortBy, $sortDir] = $this->paginationArgs($args, 20);

        $isSmallCohort = in_array($memberType, ['dosen', 'tendik', 'pustakawan', 'alumni', 'luar_biasa', 'lb', 'ksp'], true)
            || $prodi !== '';

        $visitTotalsByCard = [];
        $borrowerMap = [];
        $memberCols = [
            'b.cardnumber',
            'b.categorycode',
            DB::raw("TRIM(CONCAT(COALESCE(b.firstname, ''), ' ', COALESCE(b.surname, ''))) as nama"),
            DB::raw("COALESCE(NULLIF(TRIM(ba.attribute), ''), '-') as kode_prodi"),
        ];

        if ($isSmallCohort) {
            // Ambil daftar kartu target dari mysql2 dulu agar dosen/alumni tidak tenggelam oleh mahasiswa
            $bQuery = $this->memberQuery()->select($memberCols);
            $this->applyMemberTypeFilter($bQuery, $memberType);

            if ($prodi !== '') {
                $upperProdi = strtoupper($prodi);
                $prodiCodes = array_values(array_unique(array_merge([$upperProdi], $this->resolveProdiCodes($prodi))));
                $bQuery->where(function ($q) use ($prodiCodes) {
                    $q->whereIn('ba.attribute', $prodiCodes);
                    foreach ($prodiCodes as $code) {
                        $q->orWhere('b.cardnumber', 'like', $code . '%');
                    }
                });
            }

            foreach ($bQuery->limit(5000)->get() as $c) {
                $key = strtoupper(trim((string) $c->cardnumber));
                if ($key !== '') {
                    $borrowerMap[$key] = $c;
                }
            }

            foreach (array_chunk(array_keys($borrowerMap), 1000) as $chunk) {
                foreach ($this->visitCountsBy('UPPER(TRIM(cardnumber))', $start, $end, $chunk) as $cn => $cnt) {
                    $visitTotalsByCard[$cn] = ($visitTotalsByCard[$cn] ?? 0) + $cnt;
                }
            }
        } else {
            $visitTotalsByCard = $this->visitCountsBy('UPPER(TRIM(cardnumber))', $start, $end, null, 2000);

            foreach (array_chunk(array_keys($visitTotalsByCard), 1000) as $chunk) {
                $bQuery = $this->memberQuery()->whereIn('b.cardnumber', $chunk)->select($memberCols);
                $this->applyMemberTypeFilter($bQuery, $memberType);
                foreach ($bQuery->get() as $c) {
                    $borrowerMap[strtoupper(trim((string) $c->cardnumber))] = $c;
                }
            }
        }

        arsort($visitTotalsByCard);
        $allRows = [];
        foreach ($visitTotalsByCard as $cn => $total) {
            if (!isset($borrowerMap[$cn])) {
                continue;
            }
            $info = $borrowerMap[$cn];
            $allRows[] = [
                'NIM_atau_Kartu'   => $info->cardnumber,
                'Nama'             => $info->nama ?: '-',
                'Kategori'         => $info->categorycode ?: '-',
                'Kode_Prodi'       => $info->kode_prodi ?: '-',
                'Jumlah_Kunjungan' => $total,
            ];
        }

        $matchedTotal = count($allRows);
        if (in_array($sortBy, ['NIM_atau_Kartu', 'Nama', 'Kategori', 'Kode_Prodi', 'Jumlah_Kunjungan'], true)) {
            usort($allRows, function ($a, $b) use ($sortBy, $sortDir) {
                $cmp = $sortBy === 'Jumlah_Kunjungan'
                    ? ((int) $a[$sortBy] <=> (int) $b[$sortBy])
                    : strnatcasecmp((string) $a[$sortBy], (string) $b[$sortBy]);
                return $sortDir === 'asc' ? $cmp : -$cmp;
            });
        }

        return [
            'contains_pii'  => true,
            'total_count'   => $matchedTotal,
            'columns'       => ['NIM_atau_Kartu', 'Nama', 'Kategori', 'Kode_Prodi', 'Jumlah_Kunjungan'],
            'rows'          => array_slice($allRows, $offset, $perPage),
            'query_summary' => "Tool top_visitors (type={$memberType}, prodi=" . ($prodi ?: '-') . ", {$startDate} s/d {$endDate}, limit={$perPage})",
        ];
    }

    public function borrowingSummary(array $args): array
    {
        [$startDate, $endDate, $start, $end] = $this->dateRange($args, date('Y-m-01'));
        $mode = strtolower(trim((string) ($args['mode'] ?? 'summary')));

        if ($mode === 'ongoing') {
            $totalOngoing = DB::connection('mysql2')->table('issues')->count();
            $overdueCount = DB::connection('mysql2')->table('issues')->where('date_due', '<', date('Y-m-d H:i:s'))->count();
            return [
                'contains_pii'  => false,
                'total_count'   => 1,
                'columns'       => ['Total_Sedang_Dipinjam', 'Terlambat_Jatuh_Tempo'],
                'rows'          => [[
                    'Total_Sedang_Dipinjam' => $totalOngoing,
                    'Terlambat_Jatuh_Tempo' => $overdueCount,
                ]],
                'query_summary' => 'Tool borrowing_summary (mode=ongoing)',
            ];
        }

        if ($mode === 'top_books') {
            $rows = DB::connection('mysql2')->table('statistics as s')
                ->join('items as i', 'i.itemnumber', '=', 's.itemnumber')
                ->join('biblio as b', 'b.biblionumber', '=', 'i.biblionumber')
                ->whereIn('s.type', ['issue', 'renew'])
                ->whereBetween('s.datetime', [$start, $end])
                ->selectRaw('b.title as Judul, COALESCE(b.author, "-") as Pengarang, COUNT(*) as Jumlah_Dipinjam')
                ->groupBy('b.biblionumber', 'b.title', 'b.author')
                ->orderByDesc('Jumlah_Dipinjam')
                ->limit(25)
                ->get()
                ->map(fn($r) => (array) $r)
                ->all();

            return [
                'contains_pii'  => false,
                'total_count'   => count($rows),
                'columns'       => ['Judul', 'Pengarang', 'Jumlah_Dipinjam'],
                'rows'          => $rows,
                'query_summary' => "Tool borrowing_summary (mode=top_books, {$startDate} s/d {$endDate})",
            ];
        }

        $fetchSummary = function (string $label, string $s, string $e): array {
            $stats = DB::connection('mysql2')->table('statistics')
                ->whereIn('type', ['issue', 'renew', 'return'])
                ->whereBetween('datetime', [$s, $e])
                ->selectRaw("
                    SUM(CASE WHEN type = 'issue' THEN 1 ELSE 0 END) as Pinjam_Baru,
                    SUM(CASE WHEN type = 'renew' THEN 1 ELSE 0 END) as Perpanjang,
                    SUM(CASE WHEN type = 'return' THEN 1 ELSE 0 END) as Kembali,
                    COUNT(DISTINCT CASE WHEN type IN ('issue', 'renew') THEN borrowernumber END) as Peminjam_Unik
                ")
                ->first();

            return [
                'Periode'       => $label,
                'Pinjam_Baru'   => (int) ($stats->Pinjam_Baru ?? 0),
                'Perpanjang'    => (int) ($stats->Perpanjang ?? 0),
                'Kembali'       => (int) ($stats->Kembali ?? 0),
                'Peminjam_Unik' => (int) ($stats->Peminjam_Unik ?? 0),
            ];
        };

        $curr = $fetchSummary("{$startDate} s/d {$endDate}", $start, $end);
        if (!empty($args['compare_start_date']) && !empty($args['compare_end_date'])) {
            [$cStart, $cEnd, $cs, $ce] = $this->dateRange(['start_date' => $args['compare_start_date'], 'end_date' => $args['compare_end_date']], date('Y-01-01', strtotime('-1 year')));
            $prev = $fetchSummary("{$cStart} s/d {$cEnd}", $cs, $ce);
            $rows = [$prev, $curr, $this->buildDeltaRow($prev, $curr, ['Pinjam_Baru', 'Perpanjang', 'Kembali', 'Peminjam_Unik'])];
            $summary = "Tool borrowing_summary (compare {$cStart}..{$cEnd} vs {$startDate}..{$endDate})";
        } else {
            $rows = [$curr];
            $summary = "Tool borrowing_summary (mode=summary, {$startDate} s/d {$endDate})";
        }

        return [
            'contains_pii'  => false,
            'total_count'   => count($rows),
            'columns'       => ['Periode', 'Pinjam_Baru', 'Perpanjang', 'Kembali', 'Peminjam_Unik'],
            'rows'          => $rows,
            'query_summary' => $summary,
        ];
    }

    public function executeSafeSelect(string $connection, string $sql): array
    {
        if (!in_array($connection, ['mysql', 'mysql2'], true)) {
            throw new InvalidArgumentException("Koneksi database '{$connection}' tidak diizinkan.");
        }

        $validatedSql = $this->validateReadOnlySql($connection, $sql);
        $containsPii  = (bool) preg_match('/\b(borrowers|cardnumber|firstname|surname)\b/i', $validatedSql);

        try {
            DB::connection($connection)->statement('SET SESSION MAX_EXECUTION_TIME = 5000');
        } catch (\Throwable $e) {
        }

        $rows = array_map(fn($r) => (array) $r, DB::connection($connection)->select($validatedSql));

        return [
            'contains_pii'  => $containsPii,
            'total_count'   => count($rows),
            'columns'       => !empty($rows) ? array_keys($rows[0]) : [],
            'rows'          => $rows,
            'query_summary' => "[{$connection}] {$validatedSql}",
        ];
    }

    public function validateReadOnlySql(string $connection, string $sql): string
    {
        $sql = rtrim(trim($sql), "; \t\n\r");
        if ($sql === '') {
            throw new InvalidArgumentException('Query SQL kosong.');
        }
        if (str_contains($sql, ';')) {
            throw new InvalidArgumentException('Multi-statement SQL tidak diizinkan.');
        }
        if (preg_match('/(--|\/\*|\*\/|#)/', $sql)) {
            throw new InvalidArgumentException('Komentar SQL tidak diizinkan.');
        }
        if (!preg_match('/^SELECT\b/i', $sql)) {
            throw new InvalidArgumentException('Hanya query SELECT yang diizinkan.');
        }

        $stripped = preg_replace("/'(?:\\\\.|[^'\\\\])*'|\"(?:\\\\.|[^\"\\\\])*\"/", "''", $sql);

        $forbiddenPattern = '/\b(INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|TRUNCATE|REPLACE|GRANT|REVOKE|CALL|EXECUTE|EXEC|PREPARE|DEALLOCATE|OUTFILE|DUMPFILE|LOAD_FILE|SLEEP|BENCHMARK|GET_LOCK|INTO|SET|SHOW|DESCRIBE|EXPLAIN|USE|HANDLER|LOCK|UNLOCK)\b|\b(?:DATABASE|VERSION|SCHEMA|CURRENT_USER|SESSION_USER|SYSTEM_USER)\s*\(/i';
        if (preg_match($forbiddenPattern, $stripped, $m)) {
            $token = trim($m[0]);
            throw new InvalidArgumentException("Kata kunci/fungsi '{$token}' dilarang dalam query read-only.");
        }

        $forbiddenColsRegex = '/\b(' . implode('|', self::FORBIDDEN_COLUMNS) . ')\b/i';
        if (preg_match($forbiddenColsRegex, $stripped, $m)) {
            throw new InvalidArgumentException("Akses ke kolom sensitif '{$m[1]}' tidak diizinkan.");
        }

        $allowedTables = self::ALLOWED_TABLES[$connection] ?? [];
        if (!preg_match_all('/\b(?:FROM|JOIN)\s+([`a-zA-Z0-9_.]+)/i', $stripped, $matches) || empty($matches[1])) {
            throw new InvalidArgumentException('Query harus menyebutkan tabel sumber secara eksplisit.');
        }

        foreach ($matches[1] as $rawTable) {
            $table = strtolower(str_replace('`', '', trim($rawTable)));
            if (str_contains($table, '.')) {
                throw new InvalidArgumentException("Referensi lintas-database '{$rawTable}' tidak diizinkan.");
            }
            if (!in_array($table, $allowedTables, true)) {
                throw new InvalidArgumentException("Tabel '{$table}' tidak termasuk dalam daftar akses AI.");
            }
        }

        if (preg_match('/\bLIMIT\s+(\d+)(?:\s*,\s*(\d+))?\s*$/i', $stripped, $limitMatch)) {
            $requested = isset($limitMatch[2]) && $limitMatch[2] !== '' ? (int) $limitMatch[2] : (int) $limitMatch[1];
            if ($requested > self::MAX_LIMIT) {
                $sql = preg_replace('/\bLIMIT\s+\d+(?:\s*,\s*\d+)?\s*$/i', 'LIMIT ' . self::MAX_LIMIT, $sql);
            }
        } else {
            $sql .= ' LIMIT ' . self::MAX_LIMIT;
        }

        return $sql;
    }

    public static function getSchemaPrompt(): string
    {
        return <<<'SCHEMA'
mysql2(Koha): borrowers(borrowernumber,cardnumber,firstname,surname,categorycode,dateenrolled,dateexpiry) [Alumni:XA*/%ALUM%; Dosen:TC%; Tendik:STAF*/LIBRARIAN; LB:LB*; Mhs:^[A-Z][0-9]{3}], borrower_attributes(borrowernumber,code,attribute[PRODI]), authorised_values(category[PRODI/CCODE],authorised_value,lib), items(itemnumber,biblionumber,barcode,dateaccessioned,homebranch,itype,ccode,itemcallnumber,itemlost,withdrawn,withdrawn_on,damaged), biblio(biblionumber,title,author), biblioitems(biblioitemnumber,biblionumber,publishercode,publicationyear,cn_class), issues/old_issues(borrowernumber,itemnumber,issuedate,date_due,returndate), statistics(datetime,type[issue/renew/return/localuse],itemnumber,borrowernumber), itemtypes(itemtype,description).
mysql(Kunjungan): visitorhistory(id,cardnumber,visittime,location), visitorcorner(id,cardnumber,visittime,notes).
Catatan: Kunjungan(mysql) & Anggota(mysql2) terpisah; ranking pengunjung per kategori wajib pakai tool top_visitors.
SCHEMA;
    }

    private function resolveProdiCodes(string $prodi): array
    {
        $upper = strtoupper(trim($prodi));
        if ($upper === '') {
            return [];
        }

        return DB::connection('mysql2')->table('authorised_values')
            ->where('category', 'PRODI')
            ->where(function ($q) use ($prodi, $upper) {
                $q->where('authorised_value', $upper)
                  ->orWhere('lib', 'like', '%' . trim($prodi) . '%');
            })
            ->orderByRaw('LENGTH(lib) ASC')
            ->pluck('authorised_value')
            ->map(fn($c) => strtoupper((string) $c))
            ->all();
    }

    private function resolveProdiCnRules(string $prodi, string &$resolvedCode): array
    {
        $upper = strtoupper($prodi);
        $rules = CnClassHelperr::getCnClassByProdi($upper);
        if (!empty($rules)) {
            $resolvedCode = $upper;
            return $rules;
        }

        foreach ($this->resolveProdiCodes($prodi) as $code) {
            $rules = CnClassHelperr::getCnClassByProdi($code);
            if (!empty($rules)) {
                $resolvedCode = $code;
                return $rules;
            }
        }

        return [];
    }

    private function memberQuery()
    {
        return DB::connection('mysql2')->table('borrowers as b')
            ->leftJoin('borrower_attributes as ba', function ($join) {
                $join->on('ba.borrowernumber', '=', 'b.borrowernumber')
                    ->where('ba.code', '=', 'PRODI');
            });
    }

    private function visitCountsBy(string $groupExpr, string $start, string $end, ?array $cards = null, ?int $limit = null): array
    {
        $sub = function (string $table) use ($groupExpr, $start, $end, $cards) {
            return DB::connection('mysql')->table($table)
                ->selectRaw("{$groupExpr} as grp, COUNT(*) as cnt")
                ->whereBetween('visittime', [$start, $end])
                ->when($cards !== null, fn($q) => $q->whereIn('cardnumber', $cards))
                ->groupBy('grp');
        };

        $query = DB::connection('mysql')->query()
            ->fromSub($sub('visitorhistory')->unionAll($sub('visitorcorner')), 'v')
            ->selectRaw('grp, SUM(cnt) as total')
            ->groupBy('grp');

        if ($limit !== null) {
            $query->orderByDesc('total')->limit($limit);
        }

        $counts = [];
        foreach ($query->get() as $row) {
            if ((string) $row->grp !== '') {
                $counts[(string) $row->grp] = (int) $row->total;
            }
        }
        return $counts;
    }

    private function buildDeltaRow(array $prev, array $curr, array $metrics): array
    {
        $row = ['Periode' => 'Perubahan (Tren)'];
        foreach ($metrics as $m) {
            $p = (int) ($prev[$m] ?? 0);
            $c = (int) ($curr[$m] ?? 0);
            $diff = $c - $p;
            $sign = $diff > 0 ? '+' : '';
            $pct  = $p > 0 ? sprintf(' (%s%.1f%%)', $sign, ($diff / $p) * 100) : '';
            $row[$m] = "{$sign}{$diff}{$pct}";
        }
        return $row;
    }

    private function paginationArgs(array $args, int $defaultPerPage): array
    {
        $page    = max(1, (int) ($args['_page'] ?? 1));
        $cap     = !empty($args['_for_export']) ? 5000 : self::MAX_LIMIT;
        $perPage = max(1, min($cap, (int) ($args['_per_page'] ?? ($args['limit'] ?? $defaultPerPage))));
        $sortBy  = trim((string) ($args['_sort_by'] ?? ''));
        $sortDir = strtolower((string) ($args['_sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return [$page, $perPage, ($page - 1) * $perPage, $sortBy, $sortDir];
    }

    private function applySafeOrder($query, array $sortMap, string $sortBy, string $sortDir, string $defaultCol, string $defaultDir = 'desc'): void
    {
        if (isset($sortMap[$sortBy])) {
            $query->orderBy($sortMap[$sortBy], $sortDir);
            return;
        }
        $query->orderBy($defaultCol, $defaultDir);
    }

    private function dateRange(array $args, string $defaultStart): array
    {
        $startDate = $this->normalizeDate($args['start_date'] ?? null, $defaultStart);
        $endDate   = $this->normalizeDate($args['end_date'] ?? null, date('Y-m-d'));
        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }
        return [$startDate, $endDate, "{$startDate} 00:00:00", "{$endDate} 23:59:59"];
    }

    private function applyMemberTypeFilter($query, string $memberType): void
    {
        match ($memberType) {
            'alumni' => $query->where(function ($q) {
                $q->where('b.cardnumber', 'like', 'XA%')
                  ->orWhere('b.categorycode', 'like', '%ALUM%')
                  ->orWhere('b.categorycode', '=', 'XA');
            }),
            'mahasiswa' => $query->where(function ($q) {
                $q->whereRaw("UPPER(TRIM(b.cardnumber)) REGEXP '^[A-Z][0-9]{3}'")
                  ->where('b.categorycode', 'not like', 'TC%')
                  ->where('b.categorycode', 'not like', 'STAF%')
                  ->where('b.categorycode', '!=', 'LIBRARIAN');
            }),
            'dosen' => $query->where(function ($q) {
                $q->where('b.categorycode', 'like', 'TC%')
                  ->orWhere('b.categorycode', 'like', 'DOSEN%');
            }),
            'tendik' => $query->where('b.categorycode', 'like', 'STAF%'),
            'pustakawan' => $query->where('b.categorycode', '=', 'LIBRARIAN'),
            'luar_biasa', 'lb' => $query->where('b.cardnumber', 'like', 'LB%'),
            'ksp' => $query->where('b.cardnumber', 'like', 'KSP%'),
            default => null,
        };
    }

    private function normalizeDate(?string $input, string $fallback): string
    {
        if (is_string($input) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($input)) && strtotime($input) !== false) {
            return trim($input);
        }
        return $fallback;
    }
}
