<?php

namespace App\Http\Controllers;

use App\Models\M_Auv;
use Illuminate\Http\Request;

class ProdiWhitelistController extends Controller
{
    /**
     * Tampilkan halaman pengaturan whitelist kode PRODI dari Koha authorised_values.
     */
    public function index()
    {
        $kohaProdis = M_Auv::where('category', 'PRODI')
            ->orderBy('authorised_value', 'asc')
            ->get();

        $activeCodes = M_Auv::getWhitelistedCodes();
        $activeLookup = array_flip(array_map('strtoupper', $activeCodes));

        $kohaLookup = $kohaProdis->pluck('authorised_value')
            ->map(fn($c) => strtoupper(trim((string) $c)))
            ->flip()
            ->all();

        $extraCodes = collect($activeCodes)
            ->filter(fn($c) => !isset($kohaLookup[strtoupper($c)]))
            ->values()
            ->all();

        $isCustom = is_file(M_Auv::getWhitelistStoragePath());
        $defaultCount = count(M_Auv::getDefaultWhitelistedCodes());

        return view('pages.pengaturan.prodi-whitelist.index', compact(
            'kohaProdis',
            'activeCodes',
            'activeLookup',
            'extraCodes',
            'isCustom',
            'defaultCount'
        ));
    }

    /**
     * Simpan daftar kode prodi yang diizinkan tampil ke storage/app/prodi_whitelist.json.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'codes' => 'nullable|array',
            'codes.*' => 'string|max:30',
            'extra_codes' => 'nullable|string|max:1000',
        ]);

        $selected = $validated['codes'] ?? [];

        if (!empty($validated['extra_codes'])) {
            $manual = array_filter(array_map('trim', explode(',', $validated['extra_codes'])));
            $selected = array_merge($selected, $manual);
        }

        M_Auv::saveWhitelistedCodes($selected);
        $count = count(M_Auv::getWhitelistedCodes());

        return redirect()->route('prodi-whitelist.index')
            ->with('success', "Pengaturan Prodi Tampil disimpan ({$count} kode aktif).");
    }

    /**
     * Kembalikan whitelist prodi ke daftar default bawaan aplikasi.
     */
    public function reset()
    {
        M_Auv::resetWhitelistedCodes();

        return redirect()->route('prodi-whitelist.index')
            ->with('success', 'Pengaturan Prodi Tampil dikembalikan ke default.');
    }
}
