<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Ijazah;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class IjazahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Ijazah::query();
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where('id_staf', 'like', '%' . $searchTerm . '%')
                ->orWhere('judul_ijazah', 'like', '%' . $searchTerm . '%')
                ->orWhere('tahun', 'like', '%' . $searchTerm . '%');
        }

        $ijazah = $query->paginate(10);
        $staffs = Staff::all();
        return view('pages.staff.ijazah', compact('ijazah', 'staffs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_staf' => 'required',
            'judul_ijazah' => 'required',
            'file_dokumen' => 'mimes:pdf',
            'tahun' => 'required|integer'
        ]);

        $id = $request->input('id_staf');
        $dokumen = $request->file('file_dokumen');
        $nama_dok = 'ijazah_' . $id . '.' . $dokumen->getClientOriginalExtension();
        $dokumen->move('dokumen/', $nama_dok);

        Ijazah::create([
            'id_staf' => $request->id_staf,
            'judul_ijazah' => $request->judul_ijazah,
            'file_dokumen' => $nama_dok,
            'tahun' => $request->tahun
        ]);

        return redirect()->back()->with('success', 'Ijazah berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ijazah $ijazah)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ijazah $ijazah)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ijazah $ijazah)
    {
        $request->validate([
            'id_staf' => 'required',
            'judul_ijazah' => 'required',
            'file_dokumen' => 'nullable|mimes:pdf',
            'tahun' => 'required|integer'
        ]);

        $nama_dok = $ijazah->file_dokumen;

        if ($request->hasFile('file_dokumen')) {
            $id = $request->input('id_staf');
            $dokumen = $request->file('file_dokumen');
            $nama_dok = 'ijazah_' . $id . '.' . $dokumen->getClientOriginalExtension();

            $file_lama = public_path('dokumen/' . $ijazah->file_dokumen);
            if (file_exists($file_lama)) {
                unlink($file_lama);
            }
            $dokumen->move(public_path('dokumen'), $nama_dok);
        }

        $ijazah->update([
            'id_staf' => $request->id_staf,
            'judul_ijazah' => $request->judul_ijazah,
            'file_dokumen' => $nama_dok,
            'tahun' => $request->tahun
        ]);

        return redirect()->back()->with('success', 'Transkrip berhasil diedit.');
    }

    public function destroy($id)
    {
        $ijazah = Ijazah::findOrFail($id);
        $file_lama = public_path('dokumen/' . $ijazah->file_dokumen);
        if (file_exists($file_lama)) {
            unlink($file_lama);
        }
        $ijazah->delete();
        return redirect()->back()->with('success', 'Ijazah berhasil dihapus.');
    }


    public function testkoneksi()
    {
        $staffs = DB::connection('mysql')->table('tb_staff')->get();
        dd($staffs);
    }
}
