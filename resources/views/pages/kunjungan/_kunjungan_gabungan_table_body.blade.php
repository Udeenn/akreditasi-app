{{-- Simpan sebagai: resources/views/pages/kunjungan/_kunjungan_gabungan_table_body.blade.php --}}

@forelse ($semuaKunjungan as $kunjungan)
    <tr>
        <td class="ps-3">{{ $semuaKunjungan->firstItem() + $loop->index }}</td>
        <td>{{ \Carbon\Carbon::parse($kunjungan->visittime)->format('d M Y, H:i:s') }}</td>
        <td>
            <strong>{{ $kunjungan->cardnumber }}</strong>
            <br>
            <small class="text-muted">{{ $namaPeminjamMap[$kunjungan->cardnumber] ?? 'Nama tidak ditemukan' }}</small>
        </td>
        <td>
            @php $displayName = $lokasiMapping[$kunjungan->lokasi_kunjungan] ?? $kunjungan->lokasi_kunjungan; @endphp
            @if ($kunjungan->lokasi_kunjungan == 'Manual Komputer')
                <span class="badge bg-secondary">{{ $displayName }}</span>
            @elseif ($kunjungan->lokasi_kunjungan == 'pusat')
                <span class="badge bg-success">{{ $displayName }}</span>
            @elseif ($kunjungan->lokasi_kunjungan == 'fk')
                <span class="badge bg-primary">{{ $displayName }}</span>
            @elseif ($kunjungan->lokasi_kunjungan == 'pasca')
                <span class="badge bg-warning">{{ $displayName }}</span>
            @else
                <span class="badge bg-info">{{ $displayName }}</span>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="text-center">
            Tidak ada data kunjungan yang cocok dengan filter yang dipilih.
        </td>
    </tr>
@endforelse
