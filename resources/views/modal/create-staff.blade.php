<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addStaffModalLabel">Tambah Data Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('staff.store') }}" method="POST" id="formStaff"> {{-- Hapus enctype="multipart/form-data" jika tidak ada upload file --}}
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_staf_create" class="form-label">UNI ID Staff</label> {{-- Tambah ID unik --}}
                        <input type="text" class="form-control @error('id_staf') is-invalid @enderror"
                            id="id_staf_create" name="id_staf" value="{{ old('id_staf') }}" required>
                        @error('id_staf')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="nama_staff_create" class="form-label">Nama Staff</label> {{-- Tambah ID unik --}}
                        <input type="text" class="form-control @error('nama_staff') is-invalid @enderror"
                            id="nama_staff_create" name="nama_staff" value="{{ old('nama_staff') }}" required>
                        @error('nama_staff')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="posisi_create" class="form-label">Posisi Staff</label> {{-- Tambah ID unik --}}
                        <select name="posisi" id="posisi_create"
                            class="form-select @error('posisi') is-invalid @enderror" required> {{-- Tambah class form-select dan error handling --}}
                            <option value="">-- Pilih Posisi --</option> {{-- Opsi default --}}
                            <option value="Pustakawan" {{ old('posisi') == 'Pustakawan' ? 'selected' : '' }}>Pustakawan
                            </option>
                            <option value="Staff IT" {{ old('posisi') == 'Staff IT' ? 'selected' : '' }}>Staff IT
                            </option>
                            <option value="Teknik Pustakawan"
                                {{ old('posisi') == 'Teknik Pustakawan' ? 'selected' : '' }}>Teknik Pustakawan</option>
                        </select>
                        @error('posisi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">Clear</button>
                    {{-- Ganti onclick dengan data-bs-dismiss --}}
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
