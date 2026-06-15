<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * BorrowerService — Lookup data peminjam dari database Koha.
 *
 * Menggantikan pola query borrower yang terduplikasi di:
 * - VisitHistory::kunjunganFakultasTable()  (cardnumber → categorycode + prodi)
 * - VisitHistory::kunjunganProdiTable()     (cardnumber → categorycode)
 * - DashboardController::totalStatistik()   (cardnumber → categorycode + prodi)
 * - PeminjamanController                    (borrowernumber → categorycode + prodi)
 */
class BorrowerService
{
    public function __construct(
        private \App\Repositories\BorrowerRepository $borrowerRepository
    ) {}

    /**
     * Batch fetch informasi borrower berdasarkan cardnumber.
     * Return: array[UPPERCASE_CARDNUMBER => object{cardnumber, categorycode, prodi_code}]
     *
     * Gunakan chunking untuk menghindari query terlalu besar.
     */
    public function getBorrowerInfoByCardnumbers(Collection $cardnumbers): array
    {
        $uniqueCards = $cardnumbers
            ->map(fn($id) => strtoupper(trim($id)))
            ->unique()
            ->values();

        if ($uniqueCards->isEmpty()) {
            return [];
        }

        return $this->borrowerRepository->getBorrowerInfoByCardnumbers($uniqueCards);
    }

    /**
     * Batch fetch categorycode saja (tanpa prodi attribute).
     * Lebih ringan, digunakan ketika hanya butuh categorycode.
     * Return: Collection[UPPERCASE_CARDNUMBER => categorycode_string]
     */
    public function getCategoryCodesByCardnumbers(Collection $cardnumbers): Collection
    {
        $uniqueCards = $cardnumbers
            ->map(fn($id) => strtoupper(trim($id)))
            ->unique()
            ->values();

        if ($uniqueCards->isEmpty()) {
            return collect();
        }

        return $this->borrowerRepository->getCategoryCodesByCardnumbers($uniqueCards);
    }

    /**
     * Fetch detail satu borrower berdasarkan cardnumber.
     */
    public function getBorrowerByCardnumber(string $cardnumber): ?object
    {
        return $this->borrowerRepository->getBorrowerByCardnumber($cardnumber);
    }
}
