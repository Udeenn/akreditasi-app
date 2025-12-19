<div class="modal fade" id="editStaff{{ $item->id }}" tabindex="-1"
    aria-labelledby="editStaffModalLabel{{ $item->id }}" > {{-- Pastikan aria-labelledby unik --}}
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editStaffModalLabel{{ $item->id }}">Edit Data Staff -
                    {{ $item->nama_staff }}</h5> {{-- Sesuaikan ID unik dan tambahkan nama staff --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('staff.update', $item->id) }}" method="POST" id="formStaffEdit{{ $item->id }}">
                {{-- Hapus enctype jika tidak ada upload file, tambah ID unik --}}
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_staf_edit_{{ $item->id }}" class="form-label">ID Staf</label>
                        {{-- Tambah ID unik --}}
                        <input type="text" class="form-control @error('id_staf') is-invalid @enderror"
                            id="id_staf_edit_{{ $item->id }}" name="id_staf"
                            value="{{ old('id_staf', $item->id_staf) }}" required>
                        @error('id_staf')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="nama_staff_edit_{{ $item->id }}" class="form-label">Nama Staff</label>
                        {{-- Tambah ID unik --}}
                        <input type="text" class="form-control @error('nama_staff') is-invalid @enderror"
                            id="nama_staff_edit_{{ $item->id }}" name="nama_staff"
                            value="{{ old('nama_staff', $item->nama_staff) }}" required>
                        @error('nama_staff')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="posisi_edit_{{ $item->id }}" class="form-label">Posisi Staff</label>
                        {{-- Tambah ID unik --}}
                        <select name="posisi" id="posisi_edit_{{ $item->id }}"
                            class="form-select @error('posisi') is-invalid @enderror" required> {{-- Tambah class form-select dan error handling --}}
                            {{-- Gunakan $item->posisi untuk nilai terpilih --}}
                            <option value="Pustakawan"
                                {{ old('posisi', $item->posisi) == 'Pustakawan' ? 'selected' : '' }}>Pustakawan
                            </option>
                            <option value="Staff IT"
                                {{ old('posisi', $item->posisi) == 'Staff IT' ? 'selected' : '' }}>Staff IT</option>
                            <option value="Teknik Pustakawan"
                                {{ old('posisi', $item->posisi) == 'Teknik Pustakawan' ? 'selected' : '' }}>Teknik
                                Pustakawan</option>
                        </select>
                        @error('posisi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal">Clear</button>
                    {{-- Ganti onclick dengan data-bs-dismiss --}}
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
