<?php

namespace App\Http\Controllers;

use App\Models\CnclassRuleset;
use App\Models\CnclassProdiMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CnClassController extends Controller
{
    public function index()
    {
        $rulesets = CnclassRuleset::with('mappings')->get();
        return view('pages.pengaturan.cnclass.index', compact('rulesets'));
    }

    public function create()
    {
        return view('pages.pengaturan.cnclass.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:cnclass_rulesets,name',
            'rules' => 'nullable|string',
            'mappings' => 'nullable|string'
        ]);

        $rulesArray = $this->parseRules($request->rules);

        $ruleset = CnclassRuleset::create([
            'name' => $request->name,
            'rules' => $rulesArray,
        ]);

        if ($request->mappings) {
            $prodiCodes = array_map('trim', explode(',', $request->mappings));
            foreach ($prodiCodes as $code) {
                if (!empty($code)) {
                    CnclassProdiMapping::create([
                        'prodi_code' => $code,
                        'ruleset_id' => $ruleset->id
                    ]);
                }
            }
        }

        Cache::forget('cnclass_all_prodi_data');

        return redirect()->route('cnclass.index')->with('success', 'Ruleset berhasil ditambahkan.');
    }

    public function edit(CnclassRuleset $cnclass)
    {
        $cnclass->load('mappings');
        return view('pages.pengaturan.cnclass.edit', compact('cnclass'));
    }

    public function update(Request $request, CnclassRuleset $cnclass)
    {
        $request->validate([
            'name' => 'required|string|unique:cnclass_rulesets,name,' . $cnclass->id,
            'rules' => 'nullable|string',
            'mappings' => 'nullable|string'
        ]);

        $rulesArray = $this->parseRules($request->rules);

        $cnclass->update([
            'name' => $request->name,
            'rules' => $rulesArray,
        ]);

        // Sync mappings
        $cnclass->mappings()->delete();
        if ($request->mappings) {
            $prodiCodes = array_map('trim', explode(',', $request->mappings));
            foreach ($prodiCodes as $code) {
                if (!empty($code)) {
                    CnclassProdiMapping::create([
                        'prodi_code' => $code,
                        'ruleset_id' => $cnclass->id
                    ]);
                }
            }
        }

        Cache::forget('cnclass_all_prodi_data');

        return redirect()->route('cnclass.index')->with('success', 'Ruleset berhasil diperbarui.');
    }

    public function destroy(CnclassRuleset $cnclass)
    {
        $cnclass->delete();
        Cache::forget('cnclass_all_prodi_data');
        return redirect()->route('cnclass.index')->with('success', 'Ruleset berhasil dihapus.');
    }

    /**
     * Parse input string into rules array.
     * Supports:
     *   - single:   "001.4"   => '001.4'
     *   - wildcard: "005*"    => '005*'
     *   - range:    "100..102" => ['100', '102']
     */
    private function parseRules(?string $input): array
    {
        if (empty($input)) return [];

        $rules = [];
        foreach (array_map('trim', explode(',', $input)) as $item) {
            if ($item === '') continue;
            if (str_contains($item, '..')) {
                $parts = array_map('trim', explode('..', $item, 2));
                if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    $rules[] = $parts; // range
                }
            } else {
                $rules[] = $item; // single or wildcard
            }
        }
        return $rules;
    }
}
