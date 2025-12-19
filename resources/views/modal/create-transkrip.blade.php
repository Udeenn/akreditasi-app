<div class="modal fade" id="transkripModal" tabindex="-1" aria-labelledby="exampleModalLabel" >
    <div class="modal-dialog">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="exampleModalLabel">Tambah Data Transkrip</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <!-- Form -->
        <form action="{{ route('transkrip.store') }}" method="POST" enctype="multipart/form-data" id="formTranskrip">
          @csrf
          <div class="modal-body">
            <!-- ID Staf -->
            <div class="mb-3">
              <label for="id_staf" class="form-label">Pilih Staff</label>
              <select name="id_staf" id="id_staf" class="form-select" required>
                <option value="">-- Pilih Staff --</option>
                @foreach ($staffs as $staf)
                  <option value="{{ $staf->id_staf }}">{{ $staf->nama_staff }} - {{ $staf->id_staf }}</option>
                @endforeach
              </select>
            </div>
            <!-- Nama Ijazah -->
            <div class="mb-3">
              <label for="judul_ijazah" class="form-label">Nama Transkrip</label>
              <input type="text" class="form-control" id="judul_transkrip" name="judul_transkrip" required>
            </div>
            <!-- File Dokumen -->
            <div class="mb-3">
              <label for="file_dokumen" class="form-label">File Transkrip</label>
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
            <button type="reset" class="btn btn-secondary" onclick="document.getElementById('transkripModal').querySelector('.btn-close').click()">Clear</button>
            <button type="submit" class="btn btn-primary">Submit</button>
        </div>
        </form>
      </div>
    </div>
  </div>
