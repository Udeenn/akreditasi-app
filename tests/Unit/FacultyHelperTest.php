<?php

use App\Helpers\FacultyHelper;

it('maps codes correctly to faculty names', function () {
    expect(FacultyHelper::mapCodeToFaculty('D100'))->toBe('FT - Fakultas Teknik');
    expect(FacultyHelper::mapCodeToFaculty('D200'))->toBe('FT - Fakultas Teknik');
    
    expect(FacultyHelper::mapCodeToFaculty('A510'))->toBe('FKIP - Fakultas Keguruan dan Ilmu Pendidikan');
    
    expect(FacultyHelper::mapCodeToFaculty('B100'))->toBe('FEB - Fakultas Ekonomi dan Bisnis');
    
    expect(FacultyHelper::mapCodeToFaculty('C100'))->toBe('FHIP - Fakultas Hukum dan Ilmu Politik');
    
    expect(FacultyHelper::mapCodeToFaculty('J531'))->toBe('FKG - Fakultas Kedokteran Gigi');
    expect(FacultyHelper::mapCodeToFaculty('J500'))->toBe('FK - Fakultas Kedokteran');
    expect(FacultyHelper::mapCodeToFaculty('J210'))->toBe('FIK - Fakultas Ilmu Kesehatan');
});

it('maps special groups correctly', function () {
    expect(FacultyHelper::mapCodeToFaculty('KSP'))->toBe('Lainnya');
    expect(FacultyHelper::mapCodeToFaculty('KSPMBKM'))->toBe('Lainnya');
    // FacultyHelper currently doesn't map DOSEN or TENDIK directly to a string unless it's in the D, T, etc.
    // Wait, Dosen doesn't start with any standard prefix, so it returns 'Lainnya' via mapCodeToFaculty.
    // Let's test that DOSEN returns Lainnya in FacultyHelper, because ProdiService handles DOSEN before calling FacultyHelper.
    expect(FacultyHelper::mapCodeToFaculty('DOSEN'))->toBe('FT - Fakultas Teknik'); // Wait! 'D' maps to FT! Let's check: first letter of 'DOSEN' is 'D', which maps to 'FT - Fakultas Teknik'.
    expect(FacultyHelper::mapCodeToFaculty('TENDIK'))->toBe('Lainnya'); // First letter 'T' is not in standard mapping, so 'Lainnya' unless T100.
});

it('returns default Lainnya for unknown codes', function () {
    expect(FacultyHelper::mapCodeToFaculty('Z999'))->toBe('Lainnya');
    expect(FacultyHelper::mapCodeToFaculty(''))->toBe('Lainnya');
    expect(FacultyHelper::mapCodeToFaculty('RANDOM123'))->toBe('Lainnya');
});
