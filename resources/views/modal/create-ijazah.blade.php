<div class="modal fade" id="ijazahModal" tabindex="-1" aria-labelledby="exampleModalLabel" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="exampleModalLabel">Tambah Data Ijazah</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('ijazah.store') }}" method="POST" enctype="multipart/form-data" id="formIjazah">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_staf" class="form-label">Pilih Staff</label>
                        <select name="id_staf" id="id_staf" class="form-select" required>
                            <option value="">-- Pilih Staff --</option>
                            @foreach ($staffs as $staf)
                                <option value="{{ $staf->id_staf }}"
                                    {{ old('id_staf') == $staf->id_staf ? 'selected' : '' }}>
                                    {{ $staf->nama_staff }} - {{ $staf->id_staf }}
                                </option>
                            @endforeach
                        </select>
                        @error('id_staf')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="judul_ijazah" class="form-label">Nama Ijazah</label>
                        <input type="text" class="form-control" id="judul_ijazah" name="judul_ijazah"
                            value="{{ old('judul_ijazah') }}" required>
                        @error('judul_ijazah')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="file_dokumen" class="form-label">File Ijazah (PDF)</label>
                        {{-- Hapus value="{{ old('file_dokumen') }}" dari sini --}}
                        <input type="file" class="form-control" id="file_dokumen" name="file_dokumen"
                            accept="application/pdf" required>
                        @error('file_dokumen')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="tahun" class="form-label">Tahun</label>
                        {{-- Ubah type menjadi "number" dan tambahkan placeholder --}}
                        <input type="number" class="form-control" id="tahun" name="tahun"
                            value="{{ old('tahun') }}" placeholder="Contoh: 2023" required min="1900"
                            max="{{ date('Y') + 5 }}">
                        @error('tahun')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    {{-- Tombol "Clear" bisa memicu penutupan modal --}}
                    <button type="reset" class="btn btn-secondary">Clear</button>
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
