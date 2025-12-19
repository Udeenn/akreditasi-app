<div class="modal fade" id="editMou{{ $item->id }}" tabindex="-1" aria-labelledby="editStaffModalLabel" >
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editStaffModalLabel">Edit Data Mou</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Form -->
            <form action="{{ route('mou.update', $item->id) }}" method="POST" enctype="multipart/form-data" id="formStaff">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <!-- Nama Staff -->
                    <div class="mb-3">
                        <label for="judul_mou" class="form-label">Nama mou</label>
                        <input type="text" class="form-control" id="judul_mou" name="judul_mou" value="{{ $item->judul_mou }}" required>
                    </div>
                    <!-- File Dokumen -->
                    <div class="mb-3">
                        <label for="file_dokumen" class="form-label">File Transkrip</label>
                        <input type="file" class="form-control" id="file_dokumen" name="file_dokumen" value="{{ $item->file_dokumen }}">
                    </div>
                    <!-- Tahun -->
                    <div class="mb-3">
                        <label for="tahun" class="form-label">Tahun</label>
                        <input type="text" class="form-control" id="tahun" name="tahun" value="{{ $item->tahun }}" required>
                    </div>
                </div>
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" onclick="document.getElementById('editMou').querySelector('.btn-close').click()">Clear</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>
