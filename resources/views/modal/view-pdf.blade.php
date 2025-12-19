<div class="modal fade" id="view-pdf-{{ $item->id }}" tabindex="-1" aria-labelledby="pdfModalLabel{{ $no }}" >
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel{{ $no }}">{{ $item->id_staf }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe src="{{ asset('dokumen/' . $item->file_dokumen) }}" width="100%" height="500px" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>
