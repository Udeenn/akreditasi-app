<?php

namespace App\Http\Controllers;

use App\Services\AiDataAssistantService;
use App\Services\AiSafeQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    use \App\Traits\CsvExportable;

    private const PAGINATABLE_TOOLS = ['find_members', 'search_catalog', 'top_visitors'];

    public function __construct(
        private AiDataAssistantService $assistant,
        private AiSafeQueryService $queryService
    ) {}

    public function index()
    {
        $isConfigured = !empty(config('ai.api_key'));
        $model = (string) config('ai.model', 'gemini-3.5-flash-lite');

        return view('pages.pengaturan.ai-assistant.index', compact('isConfigured', 'model'));
    }

    public function query(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:1000',
            'history'  => 'nullable|array|max:12',
        ]);

        try {
            $result = $this->assistant->ask(
                trim($validated['question']),
                $validated['history'] ?? []
            );

            $exportUrl = null;
            $pageUrl   = null;
            $calls     = $result['calls'] ?? [];

            if (!empty($result['rows']) && !empty($result['columns'])) {
                $exportId = Str::random(24);
                $exports = session('ai_exports', []);
                // Simpan maksimal 15 riwayat agar sinkron dengan sessionStorage UI
                if (count($exports) >= 15) {
                    $exports = array_slice($exports, -14, 14, true);
                }
                $exports[$exportId] = [
                    'question' => trim($validated['question']),
                    'columns'  => $result['columns'],
                    'rows'     => $result['rows'],
                    'calls'    => $calls,
                ];
                session(['ai_exports' => $exports]);
                $exportUrl = route('admin.ai-assistant.export', ['id' => $exportId]);

                if (count($calls) === 1 && in_array($calls[0]['name'] ?? '', self::PAGINATABLE_TOOLS, true)) {
                    $pageUrl = route('admin.ai-assistant.page', ['id' => $exportId]);
                }
            }

            return response()->json([
                'status'        => 'success',
                'answer'        => $result['answer'],
                'columns'       => $result['columns'],
                'rows'          => $result['rows'],
                'total_count'   => $result['total_count'],
                'query_summary' => $result['query_summary'],
                'contains_pii'  => $result['contains_pii'],
                'cached'        => $result['cached'] ?? false,
                'tokens_used'   => $result['tokens_used'] ?? 0,
                'export_url'    => $exportUrl,
                'page_url'      => $pageUrl,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function paginate(Request $request, string $id): JsonResponse
    {
        $exportData = session("ai_exports.{$id}");
        $calls = $exportData['calls'] ?? [];
        if (!$exportData || count($calls) !== 1 || !in_array($calls[0]['name'] ?? '', self::PAGINATABLE_TOOLS, true)) {
            return response()->json(['status' => 'error', 'message' => 'Sesi tabel sudah kadaluarsa. Silakan kirim ulang pertanyaan.'], 404);
        }

        $page    = max(1, (int) $request->query('page', 1));
        $perPage = max(10, min(100, (int) $request->query('per_page', 50)));
        $sortBy  = trim((string) $request->query('sort_by', ''));
        $sortDir = strtolower((string) $request->query('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $res = $this->queryService->runPaginatedTool($calls[0]['name'], $calls[0]['args'] ?? [], $page, $perPage, $sortBy, $sortDir);
        $totalCount = (int) ($res['total_count'] ?? count($res['rows']));

        return response()->json([
            'status'      => 'success',
            'rows'        => $res['rows'],
            'total_count' => $totalCount,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => max(1, (int) ceil($totalCount / $perPage)),
            'sort_by'     => $sortBy,
            'sort_dir'    => $sortDir,
        ]);
    }

    public function exportCsv(string $id)
    {
        $exportData = session("ai_exports.{$id}");
        if (!$exportData || empty($exportData['columns'])) {
            abort(404, 'Data export sudah kadaluarsa. Silakan ajukan ulang pertanyaan di chat AI.');
        }

        $columns = $exportData['columns'];
        $rows    = $exportData['rows'];
        $calls   = $exportData['calls'] ?? [];

        if (count($calls) === 1 && in_array($calls[0]['name'] ?? '', self::PAGINATABLE_TOOLS, true)) {
            $full = $this->queryService->runPaginatedTool($calls[0]['name'], $calls[0]['args'] ?? [], 1, 5000, '', 'desc', true);
            if (!empty($full['rows'])) {
                $rows = $full['rows'];
            }
        }

        $headers = array_merge(['No'], $columns);
        $filename = 'Asisten_AI_' . date('Ymd_His') . '.csv';

        return $this->streamCsvExport(
            $rows,
            $filename,
            $headers,
            function ($row, $index) use ($columns) {
                $line = [$index];
                foreach ($columns as $col) {
                    $line[] = $row[$col] ?? '';
                }
                return $line;
            },
            ['Hasil Query Asisten Data AI', 'Pertanyaan: ' . ($exportData['question'] ?? '-')]
        );
    }
}
