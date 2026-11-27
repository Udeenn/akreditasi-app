<?php

use App\Services\AiDataAssistantService;
use App\Services\AiSafeQueryService;

it('allows valid read-only SELECT queries and enforces LIMIT 100', function () {
    $svc = new AiSafeQueryService();

    $q1 = $svc->validateReadOnlySql('mysql2', "SELECT cardnumber, firstname FROM borrowers WHERE cardnumber LIKE 'XA%'");
    expect($q1)->toEndWith('LIMIT 100');

    $q2 = $svc->validateReadOnlySql('mysql', 'SELECT COUNT(*) as total FROM visitorhistory LIMIT 500');
    expect($q2)->toEndWith('LIMIT 100');
});

it('rejects destructive SQL, unapproved tables, sensitive columns, and MySQL metadata functions', function () {
    $svc = new AiSafeQueryService();

    $blockedQueries = [
        ['mysql2', 'DELETE FROM borrowers'],
        ['mysql2', 'SELECT * FROM users'],
        ['mysql',  'SELECT * FROM activity_logs'],
        ['mysql2', 'SELECT email, cardnumber FROM borrowers'],
        ['mysql2', 'SELECT cardnumber FROM borrowers; DROP TABLE borrowers'],
        ['mysql2', 'SELECT cardnumber FROM borrowers -- comment'],
        ['mysql2', 'SELECT * FROM db_data.users'],
        ['mysql2', 'SELECT cardnumber FROM borrowers UNION SELECT id FROM users'],
        ['mysql2', 'SELECT DATABASE() FROM borrowers'],
        ['mysql',  'SELECT VERSION() FROM visitorhistory'],
    ];

    foreach ($blockedQueries as [$conn, $sql]) {
        expect(fn() => $svc->validateReadOnlySql($conn, $sql))
            ->toThrow(InvalidArgumentException::class);
    }
});

it('strips PII rows before sending tool metadata to the LLM', function () {
    $assistant = new AiDataAssistantService(new AiSafeQueryService());
    $ref = new ReflectionMethod($assistant, 'buildPrivacySafeToolResponse');
    $ref->setAccessible(true);

    $summary = $ref->invoke($assistant, [
        'contains_pii' => true,
        'total_count'  => 12,
        'columns'      => ['NIM_atau_Kartu', 'Nama'],
        'rows'         => [['NIM_atau_Kartu' => 'XA001', 'Nama' => 'RAHASIA MAHASISWA']],
    ]);

    expect($summary)->not->toHaveKey('sample_rows');
    expect(json_encode($summary))
        ->not->toContain('RAHASIA MAHASISWA')
        ->not->toContain('XA001');
});

it('merges multi-tool period results and formats Indonesian comparison narrative', function () {
    $assistant = new AiDataAssistantService(new AiSafeQueryService());
    $mergeRef = new ReflectionMethod($assistant, 'mergeToolResults');
    $mergeRef->setAccessible(true);
    $narrRef = new ReflectionMethod($assistant, 'buildComparisonNarrative');
    $narrRef->setAccessible(true);

    $merged = $mergeRef->invoke($assistant, [
        [
            'contains_pii'  => false,
            'columns'       => ['Periode', 'Pinjam_Baru'],
            'rows'          => [
                ['Periode' => '2025', 'Pinjam_Baru' => 100],
                ['Periode' => '2026', 'Pinjam_Baru' => 120],
                ['Periode' => 'Perubahan (Tren)', 'Pinjam_Baru' => '+20 (+20.0%)'],
            ],
            'query_summary' => 'borrowing_summary',
        ],
        [
            'contains_pii'  => false,
            'columns'       => ['Periode', 'Total_Kunjungan'],
            'rows'          => [
                ['Periode' => '2025', 'Total_Kunjungan' => 500],
                ['Periode' => '2026', 'Total_Kunjungan' => 450],
                ['Periode' => 'Perubahan (Tren)', 'Total_Kunjungan' => '-50 (-10.0%)'],
            ],
            'query_summary' => 'visit_summary',
        ],
    ]);

    expect($merged['columns'])->toBe(['Periode', 'Pinjam_Baru', 'Total_Kunjungan']);
    expect($merged['rows'])->toHaveCount(3);
    expect($merged['query_summary'])->toBe('borrowing_summary | visit_summary');

    $text = $narrRef->invoke($assistant, $merged['rows']);
    expect($text)
        ->toContain('Peminjaman Baru (Issue)')
        ->toContain('mengalami kenaikan')
        ->toContain('Total Kunjungan')
        ->toContain('mengalami penurunan')
        ->toContain('Analisis Tren');
});
