<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;
use App\Models\FacultyProdiMapping;
use Illuminate\Support\Facades\DB;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        FacultyProdiMapping::truncate();
        Faculty::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = [
            'FKIP - Fakultas Keguruan dan Ilmu Pendidikan' => [
                'A510', 'A610', 'KIP/PSKGJ PAUD', 'Q100', 'S400', 'Q200', 'Q300', 'S200',
                'A921', 'A922', 'A931', 'A932', 'A941', 'A942', 'A951', 'A952', 'A961', 'A971', 'A981',
                'A*'
            ],
            'FEB - Fakultas Ekonomi dan Bisnis' => [
                'W100', 'P100', 'B*'
            ],
            'FT - Fakultas Teknik' => [
                'U200', 'U100', 'S100', 'D100', 'D200', 'D400', 'D*'
            ],
            'FPsi - Fakultas Psikologi' => [
                'S300', 'T100', 'F100', 'F*'
            ],
            'FAI - Fakultas Agama Islam' => [
                'I000', 'O100', 'O300', 'O200', 'O000', 'G000', 'G100', 'G108', 'H100',
                'I*', 'O*', 'H*', 'G*'
            ],
            'FHIP - Fakultas Hukum dan Ilmu Politik' => [
                'R100', 'R200', 'C100', 'C*'
            ],
            'FF - Fakultas Farmasi' => [
                'V100', 'K100', 'K*'
            ],
            'FG - Fakultas Geografi' => [
                'E*'
            ],
            'FKI - Fakultas Komunikasi dan Informatika' => [
                'L*'
            ],
            'FKG - Fakultas Kedokteran Gigi' => [
                'J53*', 'J52*'
            ],
            'FK - Fakultas Kedokteran' => [
                'J5*'
            ],
            'FIK - Fakultas Ilmu Kesehatan' => [
                'J*'
            ],
            'Lainnya' => [
                'KSP*', 'BIPA*'
            ],
        ];

        foreach ($data as $facultyName => $mappings) {
            $faculty = Faculty::create(['name' => $facultyName]);

            foreach ($mappings as $code) {
                FacultyProdiMapping::create([
                    'faculty_id' => $faculty->id,
                    'prodi_code' => strtoupper(trim($code)),
                ]);
            }
        }

        $this->command->info('Successfully seeded Faculty and Prodi Mappings data!');
    }
}
