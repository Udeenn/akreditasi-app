<?php

namespace App\Services;

use App\Helpers\FacultyHelper;
use App\Models\M_Auv;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * ProdiService — Sumber kebenaran tunggal untuk identifikasi & mapping program studi.
 *
 * Menggantikan duplikasi logika yang sebelumnya tersebar di:
 * - VisitHistory::identifyProdiCode()
 * - VisitHistory::getProdiToFacultyMap()
 * - VisitHistory::getFullProdiList()
 * - PeminjamanController::getProdiListWithStaticOptions()
 * - DashboardController (inline logic)
 * - StatistikKoleksi::getProdiToFacultyMap()
 */
class ProdiService
{
    /**
     * Nama-nama manual yang tidak ada di database prodi.
     */
    protected array $manualNames = [
        'DOSEN'   => 'Dosen & Pengajar',
        'TENDIK'  => 'Tenaga Kependidikan',
        'KSP'     => 'Kartu Sekali Kunjung',
        'KSPMBKM' => 'MBKM',
        'KSPBIPA' => 'BIPA',
        'XA'      => 'Alumni Universitas',
        'LB'      => 'Anggota Luar Biasa',
        'XC'      => 'Anggota Luar Biasa (Exchange)',
    ];

    /**
     * Identifikasi kode prodi berdasarkan cardnumber, categorycode, dan atribut prodi.
     *
     * Logika:
     * 1. Cek categorycode (DOSEN, TENDIK)
     * 2. Cek atribut prodi dari borrower_attributes
     * 3. Cek pola cardnumber (KSP, VIP, Alumni, dll)
     * 4. Fallback: 4 karakter pertama cardnumber
     */
    public function identifyProdiCode(string $cardnumber, string $catCode, ?string $prodiAttr = null): string
    {
        $cat = strtoupper(trim($catCode));
        $card = strtoupper(trim($cardnumber));
        $prodi = strtoupper(trim($prodiAttr ?? ''));

        // 1. Prioritas tertinggi: categorycode
        if ($cat) {
            if (str_starts_with($cat, 'TC') || str_starts_with($cat, 'DOSEN')) return 'DOSEN';
            if (str_starts_with($cat, 'STAF') || str_contains($cat, 'LIB') || $cat === 'LIBRARIAN') return 'TENDIK';
        }

        // 2. Atribut prodi dari borrower_attributes
        if (!empty($prodi)) {
            return $prodi;
        }

        // 3. Pola cardnumber manual
        if (str_starts_with($card, 'KSPMBKM')) return 'KSPMBKM';
        if (str_starts_with($card, 'KSPBIPA')) return 'KSPBIPA';
        if (str_starts_with($card, 'VIP')) return 'DOSEN';
        if (in_array(substr($card, 0, 2), ['XA', 'XC', 'LB'])) return substr($card, 0, 2);
        if (substr($card, 0, 3) === 'KSP') return 'KSP';

        // 4. Tendik (ID Pendek & Bukan Mahasiswa A123...)
        if (strlen($card) <= 9 && !preg_match('/^[A-Z]\d{3}/', $card)) return 'TENDIK';

        // 5. Default Regex (Fakultas/Prodi)
        if (preg_match('/^([A-Z]\d{3})/', $card, $matches)) return $matches[1];

        // 6. Fallback: 4 karakter pertama
        if (strlen($card) >= 4) return substr($card, 0, 4);

        return 'UMUM';
    }

    /**
     * Mapping kode prodi ke nama fakultas.
     * Menggunakan FacultyHelper sebagai sumber kebenaran.
     */
    public function getProdiToFacultyMap(?Collection $listprodi = null): array
    {
        if ($listprodi === null) {
            $listprodi = M_Auv::getCachedProdiList();
        }

        $map = [];
        foreach ($listprodi as $prodi) {
            $map[$prodi->authorised_value] = FacultyHelper::mapCodeToFaculty($prodi->authorised_value);
        }

        // Tambahan manual dari $this->manualNames
        foreach ($this->manualNames as $code => $name) {
            $map[$code] = FacultyHelper::mapCodeToFaculty($code);
        }

        // Overrides untuk kategori khusus yang bukan fakultas
        $map['DOSEN']  = 'Dosen';
        $map['TENDIK'] = 'Tendik';

        return $map;
    }

    /**
     * Daftar lengkap prodi (kode => nama) termasuk kode manual.
     * Digunakan untuk tabel kunjungan, dropdown, dll.
     */
    public function getFullProdiList(): array
    {
        return Cache::remember('prodi_full_list', 3600, function () {
            $allProdiListObj = M_Auv::where('category', 'PRODI')->get();
            $facultyMap = $this->getProdiToFacultyMap($allProdiListObj);
            $prodiNameMap = $allProdiListObj->pluck('lib', 'authorised_value')->toArray();

            $list = [];
            foreach ($prodiNameMap as $code => $name) {
                $facultyString = $facultyMap[$code] ?? '';
                $parts = explode(' - ', $facultyString);
                $acronym = isset($parts[0]) ? trim($parts[0]) : '';

                $cleanName = $name;
                if (!empty($acronym) && str_starts_with(strtoupper($name), $acronym)) {
                    $cleanName = ltrim(substr($name, strlen($acronym)), "/- ");
                }

                if (!empty($acronym) && $acronym !== 'Lainnya') {
                    $list[$code] = $acronym . ' / ' . $cleanName;
                } else {
                    $list[$code] = $name;
                }
            }

            // Tambahkan kode manual
            foreach ($this->manualNames as $code => $name) {
                $list[$code] = $name;
            }

            return $list;
        });
    }

    /**
     * Daftar prodi untuk dropdown (dengan opsi statis Dosen & Tendik di atas).
     * Digunakan di peminjaman prodi chart dropdown.
     */
    public function getProdiDropdownOptions(): Collection
    {
        $prodiFromDb = M_Auv::getCachedProdiList()
            ->sortBy('authorised_value')
            ->map(function ($prodi) {
                return (object) ['authorised_value' => $prodi->authorised_value, 'lib' => trim($prodi->lib)];
            });

        $staticOptions = collect([
            (object) ['authorised_value' => 'DOSEN', 'lib' => 'Dosen'],
            (object) ['authorised_value' => 'STAFF', 'lib' => 'Tenaga Kependidikan (Staff)'],
        ]);

        return $staticOptions->concat($prodiFromDb);
    }

    /**
     * Daftar fakultas unik (untuk dropdown filter).
     * Exclude kategori non-fakultas (Dosen, Tendik, Lainnya).
     */
    public function getListFakultas(): array
    {
        $allProdiListObj = M_Auv::getCachedProdiList();

        return collect($this->getProdiToFacultyMap($allProdiListObj))
            ->unique()
            ->filter(fn($v) => !in_array($v, [
                'Lainnya', 'Dosen', 'Dosen & Pengajar',
                'Tendik', 'Tenaga Kependidikan',
            ]))
            ->sort()->values()->all();
    }
}
