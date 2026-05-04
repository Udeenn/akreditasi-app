<?php

namespace App\Traits;

trait CsvExportable
{
    /**
     * Helper sentral untuk mengekspor data ke file CSV.
     * Secara otomatis menangani BOM UTF-8 dan header standar.
     *
     * @param iterable|array $data Data yang akan di-loop
     * @param string $filename Nama file output
     * @param array $headers Array judul kolom tabel (misal: ['No', 'Nama', 'Kategori'])
     * @param callable $rowMapper Fungsi map untuk setiap baris data. Return array 1D atau array of arrays (jika 1 baris di data menghasilkan beberapa baris CSV).
     * @param array $titles Baris judul di atas tabel (opsional)
     * @param array $footerRow Baris total/footer di bawah tabel (opsional)
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    protected function streamCsvExport($data, $filename, $headers, $rowMapper, $titles = [], $footerRow = [])
    {
        $callback = function () use ($data, $headers, $rowMapper, $titles, $footerRow) {
            if (ob_get_level()) {
                ob_end_clean();
            }
            $file = fopen('php://output', 'w');

            // BOM untuk UTF-8 agar bisa dibaca Excel tanpa karakter aneh
            fputs($file, "\xEF\xBB\xBF");
            $delimiter = ';';

            // Judul opsional
            foreach ($titles as $title) {
                fputcsv($file, [$title], $delimiter);
            }
            if (!empty($titles)) {
                fputcsv($file, [], $delimiter); // Baris kosong pemisah
            }

            // Header kolom tabel
            fputcsv($file, $headers, $delimiter);

            $index = 1;
            foreach ($data as $row) {
                $rowData = $rowMapper($row, $index);
                if (!empty($rowData)) {
                    // Cek apakah $rowData merupakan array 2 dimensi (mengembalikan multi-baris)
                    if (isset($rowData[0]) && is_array($rowData[0])) {
                        foreach ($rowData as $r) {
                            fputcsv($file, $r, $delimiter);
                        }
                    } else {
                        // Single row
                        fputcsv($file, $rowData, $delimiter);
                    }
                }
            }

            // Footer opsional
            if (!empty($footerRow)) {
                fputcsv($file, [], $delimiter);
                fputcsv($file, $footerRow, $delimiter);
            }

            fclose($file);
        };

        $responseHeaders = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->stream($callback, 200, $responseHeaders);
    }
}
