<?php

$projectRoot = dirname(__DIR__, 2);

/**
 * Scan PHP & Blade files for every view('pages.*'), loadView('pages.*'), and @include('pages.*') call.
 */
function collectReferencedPageViews(string $root): array
{
    $targets = [$root . '/app/Http/Controllers', $root . '/resources/views'];
    $refs = [];

    foreach ($targets as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            if (preg_match_all('/(?:view|loadView|@include)\(\s*[\'"](pages\.[^\'"]+)[\'"]/', $content, $matches)) {
                foreach ($matches[1] as $viewName) {
                    $refs[] = [
                        'file' => str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                        'view' => $viewName,
                    ];
                }
            }
        }
    }

    return $refs;
}

it('resolves every referenced pages.* view file on disk without touching the database', function () use ($projectRoot) {
    $refs = collectReferencedPageViews($projectRoot);
    // Pre-existing unused resource stubs in CnClassController (CRUD uses modals in index.blade.php)
    $knownMissing = [
        'pages.pengaturan.cnclass.create',
        'pages.pengaturan.cnclass.edit',
    ];

    expect($refs)->not->toBeEmpty();

    foreach ($refs as $ref) {
        if (in_array($ref['view'], $knownMissing, true)) {
            continue;
        }
        $relativePath = 'resources/views/' . str_replace('.', '/', $ref['view']) . '.blade.php';
        $fullPath = $projectRoot . '/' . $relativePath;

        expect(file_exists($fullPath))
            ->toBeTrue("Missing view [{$ref['view']}] referenced in {$ref['file']} (expected {$relativePath})");
    }
});

it('contains no leftover references to legacy dapus or pre-rename view names', function () use ($projectRoot) {
    $refs = collectReferencedPageViews($projectRoot);
    $legacyPrefixes = ['pages.dapus.'];
    $legacyExact = [
        'pages.kunjungan.fakultasTable',
        'pages.kunjungan.prodiTable',
        'pages.kunjungan.prodiChart',
        'pages.kunjungan.tanggalTable',
        'pages.kunjungan.kunjungan_gabungan',
        'pages.kunjungan.cekKehadiran',
        'pages.kunjungan.laporan_kehadiran_pdf',
        'pages.kunjungan.pdf.kunjunganGabungan',
        'pages.peminjaman.peminjamanRentangTanggal',
        'pages.peminjaman.prodiChart',
        'pages.peminjaman.peminjamanFakultas',
        'pages.peminjaman.peminjamanBerlangsung',
        'pages.peminjaman.cekPeminjaman',
        'pages.peminjaman.bukuTerlarisProdi',
        'pages.peminjaman.pdf.peminjamanKeseluruhan',
        'pages.peminjaman.pdf.bukuTerlarisProdi',
        'pages.penggunaan.keterpakaian',
        'pages.penggunaan.cekBuku',
        'pages.penggunaan.sering_dibaca',
        'pages.penggunaan.pdf.keterpakaianKoleksi',
        'pages.reward.pemustaka_teraktif',
    ];

    foreach ($refs as $ref) {
        foreach ($legacyPrefixes as $prefix) {
            expect(str_starts_with($ref['view'], $prefix))
                ->toBeFalse("Legacy view prefix [{$ref['view']}] still referenced in {$ref['file']}");
        }
        expect(in_array($ref['view'], $legacyExact, true))
            ->toBeFalse("Legacy view [{$ref['view']}] still referenced in {$ref['file']}");
    }
});

it('uses lowercase kebab-case filenames for all Blade views under resources/views/pages', function () use ($projectRoot) {
    $pagesDir = $projectRoot . '/resources/views/pages';
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pagesDir));

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $name = $file->getFilename();
        expect($name)->toMatch('/^[a-z0-9-]+\.blade\.php$/');
    }
});
