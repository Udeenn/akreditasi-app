@extends('layouts.app')

@section('title', 'Pengaturan Prodi Tampil')

@section('content')
<div class="container-fluid px-3 px-md-4 pt-2 pb-4">

    <x-breadcrumb title="Pengaturan Prodi Tampil" icon="fas fa-sliders-h">
        <x-slot name="subtitle">
            Atur daftar kode Program Studi dari Koha yang ditampilkan pada filter dropdown &amp; laporan koleksi (tersimpan di <code>storage/app/prodi_whitelist.json</code>).
        </x-slot>
    </x-breadcrumb>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form id="whitelistForm" method="POST" action="{{ route('prodi-whitelist.update') }}">
        @csrf

        <div class="card unified-card border-0 shadow-sm mb-4">
            <div class="card-header border-bottom py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <h6 class="fw-bold mb-0"><i class="fas fa-list-check me-2 text-primary"></i>Daftar Kode PRODI Koha</h6>
                    <span class="badge bg-primary-soft rounded-pill px-3" id="activeCounterBadge">
                        Aktif: <span id="selectedCount">{{ count($activeCodes) }}</span> / {{ $kohaProdis->count() }}
                    </span>
                    @if($isCustom)
                        <span class="badge bg-info-soft rounded-pill px-3">Mode: Kustom</span>
                    @else
                        <span class="badge bg-success-soft rounded-pill px-3">Mode: Default ({{ $defaultCount }})</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" form="resetWhitelistForm" class="btn btn-outline-secondary btn-sm fw-bold px-3"
                        onclick="return confirm('Kembalikan daftar prodi tampil ke default ({{ $defaultCount }} kode)?')">
                        <i class="fas fa-undo me-1"></i> Reset Default
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm">
                        <i class="fas fa-save me-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row g-2 align-items-center mb-3">
                    <div class="col-md-5">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="searchProdiInput" class="form-control" placeholder="Cari kode atau nama prodi...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active filter-status-btn" data-status="all">Semua</button>
                            <button type="button" class="btn btn-outline-primary filter-status-btn" data-status="active">Aktif</button>
                            <button type="button" class="btn btn-outline-primary filter-status-btn" data-status="inactive">Nonaktif</button>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex justify-content-md-end gap-2">
                        <button type="button" id="btnCheckVisible" class="btn btn-sm btn-outline-success">Pilih Terlihat</button>
                        <button type="button" id="btnUncheckVisible" class="btn btn-sm btn-outline-danger">Kosongkan Terlihat</button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="extra_codes" class="form-label small text-muted text-uppercase fw-bold mb-1">
                        Kode Tambahan Manual (Opsional, pisahkan dengan koma)
                    </label>
                    <input type="text" name="extra_codes" id="extra_codes" class="form-control form-control-sm"
                        value="{{ old('extra_codes', implode(', ', $extraCodes)) }}"
                        placeholder="Contoh: H000, O000">
                </div>

                <div class="table-responsive" style="max-height: 540px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 unified-table" id="prodiWhitelistTable">
                        <thead class="sticky-top">
                            <tr>
                                <th class="text-center py-2 px-3" width="7%">Tampil</th>
                                <th class="text-center py-2 px-3" width="15%">Kode</th>
                                <th class="py-2 px-3">Nama Prodi (Koha authorised_values)</th>
                                <th class="text-center py-2 px-3" width="16%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kohaProdis as $prodi)
                                @php
                                    $code = trim((string) $prodi->authorised_value);
                                    $isChecked = isset($activeLookup[strtoupper($code)]);
                                @endphp
                                <tr class="prodi-row" data-code="{{ strtolower($code) }}" data-name="{{ strtolower(trim((string) $prodi->lib)) }}">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input prodi-checkbox"
                                            name="codes[]" value="{{ $code }}"
                                            id="code_{{ $loop->index }}"
                                            {{ $isChecked ? 'checked' : '' }}>
                                    </td>
                                    <td class="text-center">
                                        <label for="code_{{ $loop->index }}" class="badge border text-body mb-0" style="cursor:pointer;">{{ $code }}</label>
                                    </td>
                                    <td>
                                        <label for="code_{{ $loop->index }}" class="fw-medium mb-0 w-100" style="cursor:pointer;">
                                            {{ trim((string) $prodi->lib) }}
                                        </label>
                                    </td>
                                    <td class="text-center status-cell">
                                        @if($isChecked)
                                            <span class="badge bg-success-soft rounded-pill px-3">Tampil</span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">Disembunyikan</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Tidak ada data PRODI ditemukan di Koha.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>

    <form id="resetWhitelistForm" method="POST" action="{{ route('prodi-whitelist.reset') }}" class="d-none">
        @csrf
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchProdiInput');
    const statusButtons = document.querySelectorAll('.filter-status-btn');
    const rows = Array.from(document.querySelectorAll('#prodiWhitelistTable tbody tr.prodi-row'));
    const selectedCountEl = document.getElementById('selectedCount');
    let currentStatus = 'all';

    function updateCounter() {
        const checked = document.querySelectorAll('.prodi-checkbox:checked').length;
        if (selectedCountEl) selectedCountEl.textContent = checked;
    }

    function applyFilter() {
        const q = (searchInput?.value || '').trim().toLowerCase();
        rows.forEach(row => {
            const cb = row.querySelector('.prodi-checkbox');
            const matchesSearch = !q || row.dataset.code.includes(q) || row.dataset.name.includes(q);
            const matchesStatus = currentStatus === 'all'
                || (currentStatus === 'active' && cb.checked)
                || (currentStatus === 'inactive' && !cb.checked);
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }

    function updateRowBadge(cb) {
        const cell = cb.closest('tr')?.querySelector('.status-cell');
        if (!cell) return;
        cell.innerHTML = cb.checked
            ? '<span class="badge bg-success-soft rounded-pill px-3">Tampil</span>'
            : '<span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">Disembunyikan</span>';
    }

    rows.forEach(row => {
        const cb = row.querySelector('.prodi-checkbox');
        cb?.addEventListener('change', () => {
            updateRowBadge(cb);
            updateCounter();
            applyFilter();
        });
    });

    searchInput?.addEventListener('input', applyFilter);

    statusButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            statusButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.dataset.status;
            applyFilter();
        });
    });

    document.getElementById('btnCheckVisible')?.addEventListener('click', () => {
        rows.filter(r => r.style.display !== 'none').forEach(r => {
            const cb = r.querySelector('.prodi-checkbox');
            if (cb && !cb.checked) {
                cb.checked = true;
                updateRowBadge(cb);
            }
        });
        updateCounter();
    });

    document.getElementById('btnUncheckVisible')?.addEventListener('click', () => {
        rows.filter(r => r.style.display !== 'none').forEach(r => {
            const cb = r.querySelector('.prodi-checkbox');
            if (cb && cb.checked) {
                cb.checked = false;
                updateRowBadge(cb);
            }
        });
        updateCounter();
    });
});
</script>
@endpush
