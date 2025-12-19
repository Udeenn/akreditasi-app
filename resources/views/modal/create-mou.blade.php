<div class="modal fade" id="mouModal" tabindex="-1" aria-labelledby="exampleModalLabel" >
<div class="modal-dialog">
    <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="exampleModalLabel">Tambah Data Ijazah</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <!-- Form -->
        <form action="{{ route('mou.store') }}" method="POST" enctype="multipart/form-data" id="formIjazah">
        @csrf
        <div class="modal-body">
            <!-- Nama Ijazah -->
            <div class="mb-3">
            <label for="judul_mou" class="form-label">Nama Mou</label>
            <input type="text" class="form-control" id="judul_mou" name="judul_mou" required>
            </div>
            <!-- File Dokumen -->
            <div class="mb-3">
            <label for="file_dokumen" class="form-label">File Ijazah</label>
            <input type="file" class="form-control" id="file_dokumen" name="file_dokumen" value="{{ old('file_dokumen') }}" required>
            </div>
            <!-- Tahun -->
            <div class="mb-3">
            <label for="tahun" class="form-label">Tahun</label>
            <input type="text" class="form-control" id="tahun" name="tahun" required>
            </div>
        </div>
        <!-- Modal Footer -->
        <div class="modal-footer">
            <button type="reset" class="btn btn-secondary" onclick="document.getElementById('mouModal').querySelector('.btn-close').click()">Clear</button>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
        </form>
    </div>
    </div>
</div>
