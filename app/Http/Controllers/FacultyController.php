<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\FacultyProdiMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::with('mappings')->orderBy('name', 'asc')->get();
        return view('pages.pengaturan.fakultas.index', compact('faculties'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|unique:faculties,name',
            'mappings' => 'nullable|string',
        ]);

        $faculty = Faculty::create([
            'name' => $request->name,
        ]);

        if (!empty($request->mappings)) {
            $codes = array_map('trim', explode(',', $request->mappings));
            foreach ($codes as $code) {
                if ($code !== '') {
                    FacultyProdiMapping::create([
                        'faculty_id' => $faculty->id,
                        'prodi_code' => strtoupper($code),
                    ]);
                }
            }
        }

        Cache::forget('faculty_prodi_mapping_data');

        return redirect()->route('fakultas.index')->with('success', 'Fakultas berhasil ditambahkan.');
    }

    public function update(Request $request, Faculty $fakulta)
    {
        $request->validate([
            'name'     => 'required|string|unique:faculties,name,' . $fakulta->id,
            'mappings' => 'nullable|string',
        ]);

        $fakulta->update([
            'name' => $request->name,
        ]);

        $fakulta->mappings()->delete();

        if (!empty($request->mappings)) {
            $codes = array_map('trim', explode(',', $request->mappings));
            foreach ($codes as $code) {
                if ($code !== '') {
                    FacultyProdiMapping::create([
                        'faculty_id' => $fakulta->id,
                        'prodi_code' => strtoupper($code),
                    ]);
                }
            }
        }

        Cache::forget('faculty_prodi_mapping_data');

        return redirect()->route('fakultas.index')->with('success', 'Fakultas berhasil diperbarui.');
    }

    public function destroy(Faculty $fakulta)
    {
        $fakulta->delete();
        Cache::forget('faculty_prodi_mapping_data');

        return redirect()->route('fakultas.index')->with('success', 'Fakultas berhasil dihapus.');
    }
}
