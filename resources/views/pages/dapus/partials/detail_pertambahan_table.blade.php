<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-header-theme">
            <tr>
                <th class="text-center" width="5%">No</th>
                <th width="35%">Judul Buku / Terbitan</th>
                <th width="22%">Pengarang</th>
                <th width="18%">Penerbit & Th. Terbit</th>
                <th width="10%">No. Panggil</th>
                <th class="text-center" width="10%">Jumlah Eksemplar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $index => $row)
            <tr>
                <td class="text-center">{{ $details->firstItem() + $index }}</td>
                <td>
                    <div class="fw-bold text-body-title">{{ $row->title ?? '-' }}</div>
                </td>
                <td>
                    @if(!empty(trim($row->author)))
                        <span class="fw-medium text-body-title"><i class="fas fa-user-edit text-primary me-1"></i> {{ $row->author }}</span>
                    @elseif(!empty(trim($row->publishercode)))
                        <span class="text-muted small fst-italic"><i class="fas fa-building text-secondary me-1"></i> {{ $row->publishercode }} <span class="badge bg-secondary bg-opacity-10 text-secondary border-0">Penerbit</span></span>
                    @else
                        <span class="text-muted small fst-italic"><i class="fas fa-minus-circle text-muted me-1"></i> Terbitan Berkala / Anonim</span>
                    @endif
                </td>
                <td class="small">
                    <div>{{ $row->publishercode ?? '-' }}</div>
                    @if($row->publicationyear)
                        <span class="badge bg-secondary bg-opacity-10 text-secondary mt-1">{{ $row->publicationyear }}</span>
                    @endif
                </td>
                <td class="small font-monospace">{{ $row->itemcallnumber ?? '-' }}</td>
                <td class="text-center">
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill fw-bold">
                        <i class="fas fa-copy me-1"></i> {{ number_format($row->total_eksemplar, 0, ',', '.') }} Eks
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fs-3 mb-2 d-block text-secondary"></i>
                    Tidak ada data detail pertambahan buku ditemukan.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="d-flex justify-content-center justify-content-md-end align-items-center mt-3">
    <div>
        {{ $details->links() }}
    </div>
</div>
