<?php

namespace App\Helpers;

class FacultyHelper
{
    /**
     * Standard mapping for Prodi prefixes to Faculty names
     *
     * @var array
     */
    private static $facultyMapping = [
        'A' => 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan',
        'B' => 'FEB - Fakultas Ekonomi dan Bisnis',
        'C' => 'FHIP - Fakultas Hukum dan Ilmu Politik',
        'D' => 'FT - Fakultas Teknik',
        'E' => 'FG - Fakultas Geografi',
        'F' => 'FPsi - Fakultas Psikologi',
        'G' => 'FAI - Fakultas Agama Islam',
        'H' => 'FAI - Fakultas Agama Islam',
        'K' => 'FF - Fakultas Farmasi',
        'L' => 'FKI - Fakultas Komunikasi dan Informatika',
    ];

    /**
     * Map a Prodi Code to its Parent Faculty Name
     * Supports old, new, and merged Prodi codes formats across the university.
     *
     * @param string $prodiCode
     * @return string
     */
    public static function mapCodeToFaculty($prodiCode)
    {
        $prodiCode = strtoupper(trim($prodiCode));
        $firstLetter = substr($prodiCode, 0, 1);
        $firstTwoLetters = substr($prodiCode, 0, 2);
        $firstThreeLetters = substr($prodiCode, 0, 3);

        // 1. CEK KODE SPESIFIK (Gabungan Kode Lama, Baru & PPG)
        // FKIP
        if (in_array($prodiCode, ['A510', 'A610', 'KIP/PSKGJ PAUD', 'Q100', 'S400', 'Q200', 'Q300', 'S200', 'A921', 'A922', 'A931', 'A932', 'A941', 'A942', 'A951', 'A952', 'A961', 'A971', 'A981'])) {
            return 'FKIP - Fakultas Keguruan dan Ilmu Pendidikan';
        }

        // FEB
        if (in_array($prodiCode, ['W100', 'P100'])) {
            return 'FEB - Fakultas Ekonomi dan Bisnis';
        }

        // FT (Teknik) - Gabungan U, S, dan D
        if (in_array($prodiCode, ['U200', 'U100', 'S100', 'D100', 'D200', 'D400'])) {
            return 'FT - Fakultas Teknik';
        }

        // FPsi (Psikologi) - Gabungan S, T, dan F
        if (in_array($prodiCode, ['S300', 'T100', 'F100'])) {
            return 'FPsi - Fakultas Psikologi';
        }

        // FAI (Agama Islam)
        if (in_array($prodiCode, ['I000', 'O100', 'O300', 'O200', 'O000'])) {
            return 'FAI - Fakultas Agama Islam';
        }

        // FHIP (Hukum) - Gabungan R dan C
        if (in_array($prodiCode, ['R100', 'R200', 'C100'])) {
            return 'FHIP - Fakultas Hukum dan Ilmu Politik';
        }

        // FF (Farmasi) - Gabungan V dan K
        if (in_array($prodiCode, ['V100', 'K100'])) {
            return 'FF - Fakultas Farmasi';
        }

        // KHUSUS: KSP (Kartu Sekali Kunjung) agar tidak masuk ke Farmasi (Prefix K)
        // Dan BIPA agar tidak masuk ke FEB (Prefix B)
        if (str_starts_with($prodiCode, 'KSP') || $prodiCode === 'KSP' || str_contains($prodiCode, 'BIPA')) {
            return 'Lainnya'; 
        }

        // 2. CEK BERDASARKAN PREFIX (HURUF DEPAN TERKHUSUS)
        // FIK / FK / FKG
        if (in_array($firstThreeLetters, ['J53', 'J52'])) return 'FKG - Fakultas Kedokteran Gigi';
        if ($firstTwoLetters === 'J5') return 'FK - Fakultas Kedokteran';
        if ($firstLetter === 'J' || $firstLetter === 'G') return 'FIK - Fakultas Ilmu Kesehatan';

        // FAI (Prefix Umum)
        if (in_array($firstLetter, ['I', 'O', 'H'])) return 'FAI - Fakultas Agama Islam';

        // 3. MAPPING STANDAR DARI ARRAY PROPERTY
        if (isset(self::$facultyMapping[$firstLetter])) {
            return self::$facultyMapping[$firstLetter];
        }

        return 'Lainnya';
    }
}
