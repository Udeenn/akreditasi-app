@extends('layouts.app')

@section('title', 'Pengaturan Kelas Koleksi')

@push('styles')
<style>
    .cn-rules-cell {
        max-width: 380px;
    }
    .cn-badges-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
        max-height: 60px;
        overflow: hidden;
        position: relative;
        transition: max-height 0.3s ease;
    }
    .cn-badges-wrap.expanded {
        max-height: 2000px;
    }
    .cn-show-more {
        font-size: 0.7rem;
        cursor: pointer;
        color: var(--bs-primary);
        border: none;
        background: none;
        padding: 2px 4px;
        margin-top: 2px;
        white-space: nowrap;
    }
    .cn-show-more:hover {
        text-decoration: underline;
    }

    /* Badge base (light mode) */
    .cn-badge-primary {
        background: rgba(74,105,255,0.08);
        color: #4a69ff;
        border-color: rgba(74,105,255,0.2) !important;
    }
    .cn-badge-secondary {
        background: rgba(100,116,139,0.1);
        color: #475569;
        border-color: rgba(100,116,139,0.2) !important;
    }
    .cn-badge-teal {
        background: rgba(20,184,166,0.1);
        color: #0d9488;
        border-color: rgba(20,184,166,0.2) !important;
    }
    .cn-ruleset-badge {
        background: rgba(74,105,255,0.12);
        color: #4a69ff;
    }

    /* Dark mode overrides */
    body.dark-mode .cn-badge-primary {
        background: rgba(74,105,255,0.25) !important;
        color: #a5b4fc !important;
        border-color: rgba(74,105,255,0.35) !important;
    }
    body.dark-mode .cn-badge-secondary {
        background: rgba(148,163,184,0.15) !important;
        color: #94a3b8 !important;
        border-color: rgba(148,163,184,0.2) !important;
    }
    body.dark-mode .cn-badge-teal {
        background: rgba(20,184,166,0.2) !important;
        color: #5eead4 !important;
        border-color: rgba(20,184,166,0.3) !important;
    }
    body.dark-mode .cn-ruleset-badge {
        background: rgba(74,105,255,0.25) !important;
        color: #a5b4fc !important;
    }
    body.dark-mode .form-text,
    body.dark-mode .form-label {
        color: #94a3b8 !important;
    }
    body.dark-mode .table > :not(caption) > * > * {
        color: #e2e8f0;
    }
    body.dark-mode .text-muted {
        color: #64748b !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 pt-2 pb-4">

    <x-breadcrumb title="Pengaturan Kelas Koleksi" icon="fas fa-tags">
        <x-slot name="subtitle">
            Kelola aturan klasifikasi (Call Number) koleksi berdasarkan Program Studi.
        </x-slot>
    </x-breadcrumb>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-3" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between py-3 px-4">
            <h6 class="fw-bold mb-0"><i class="fas fa-tags me-2 text-primary"></i>Daftar Ruleset Kelas Koleksi</h6>
            <div>
                <button type="button" class="btn btn-outline-info btn-sm rounded-3 px-3 fw-semibold shadow-sm me-2"
                    data-bs-toggle="modal" data-bs-target="#guideModal">
                    <i class="fas fa-info-circle me-1"></i> Panduan Pengisian
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fw-semibold shadow-sm"
                    data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fas fa-plus me-1"></i> Tambah Ruleset
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase small text-muted" style="font-size: 0.7rem; background: var(--bs-tertiary-bg);">
                        <tr>
                            <th class="ps-4 py-3" style="width: 40px;">No</th>
                            <th class="py-3" style="width: 130px;">Nama Ruleset</th>
                            <th class="py-3">Aturan Kelas Koleksi</th>
                            <th class="py-3" style="width: 180px;">Mapping Prodi</th>
                            <th class="py-3 text-center pe-4" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rulesets as $index => $ruleset)
                        <tr>
                            <td class="ps-4 text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <span class="badge rounded-3 fw-semibold cn-ruleset-badge" style="font-size: 0.8rem; padding: 5px 10px;">
                                    {{ $ruleset->name }}
                                </span>
                            </td>
                            <td class="cn-rules-cell">
                                <div class="cn-badges-wrap" id="rules-wrap-{{ $ruleset->id }}">
                                    @if(is_array($ruleset->rules))
                                        @foreach($ruleset->rules as $rule)
                                            @if(is_array($rule))
                                                <span class="badge rounded-2 border fw-normal cn-badge-secondary" style="font-size: 0.68rem;">{{ implode('..', $rule) }}</span>
                                            @else
                                                <span class="badge rounded-2 border fw-normal cn-badge-primary" style="font-size: 0.68rem;">{{ $rule }}</span>
                                            @endif
                                        @endforeach
                                    @endif
                                </div>
                                @if(is_array($ruleset->rules) && count($ruleset->rules) > 8)
                                    <button class="cn-show-more" onclick="toggleRules({{ $ruleset->id }}, this)">
                                        <i class="fas fa-chevron-down me-1" style="font-size:0.6rem;"></i>
                                        Tampilkan semua ({{ count($ruleset->rules) }} aturan)
                                    </button>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($ruleset->mappings as $mapping)
                                        <span class="badge rounded-2 border fw-normal cn-badge-teal" style="font-size: 0.68rem;">{{ $mapping->prodi_code }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-1">
                                    @php
                                        $flattened = [];
                                        if(is_array($ruleset->rules)){
                                            foreach($ruleset->rules as $rule) {
                                                $flattened[] = is_array($rule) ? implode('..', $rule) : $rule;
                                            }
                                        }
                                        $rulesStr = implode(', ', $flattened);
                                        $mappingsStr = implode(', ', $ruleset->mappings->pluck('prodi_code')->toArray());
                                    @endphp
                                    <button type="button"
                                        class="btn btn-outline-primary btn-sm rounded-3 btn-edit-ruleset"
                                        data-id="{{ $ruleset->id }}"
                                        data-name="{{ $ruleset->name }}"
                                        data-rules="{{ $rulesStr }}"
                                        data-mappings="{{ $mappingsStr }}"
                                        data-action="{{ route('cnclass.update', $ruleset->id) }}">
                                        <i class="fas fa-edit me-1"></i>
                                    </button>
                                    <form action="{{ route('cnclass.destroy', $ruleset->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus ruleset \'{{ $ruleset->name }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm rounded-3">
                                            <i class="fas fa-trash me-1"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-tags fa-2x mb-2 d-block opacity-25"></i>
                                Belum ada data ruleset. Tambahkan ruleset pertama Anda.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Shared Edit Modal (satu modal, diisi via JS — menghindari modal di dalam tr yang menyebabkan flickering) --}}
<div class="modal fade" id="editModalShared" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Edit Ruleset: <span class="text-primary" id="editModalRulesetName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editModalForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Ruleset</label>
                        <input type="text" name="name" id="editModalName" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Aturan Kelas Koleksi</label>
                        <textarea name="rules" id="editModalRules" class="form-control rounded-3" rows="4" style="font-family: monospace; font-size: 0.8rem;"></textarea>
                        <div class="form-text small">Pisahkan dengan koma. Format: <code>001.4</code> (tunggal), <code>005*</code> (awalan), <code>100..102</code> (rentang)</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-muted">Mapping Prodi <span class="fw-normal text-muted">(Alias)</span></label>
                        <input type="text" name="mappings" id="editModalMappings" class="form-control rounded-3">
                        <div class="form-text small">Kode prodi dipisahkan koma. Contoh: <code>D100, S100</code></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm shadow-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Guide Modal --}}
<div class="modal fade" id="guideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold text-info"><i class="fas fa-info-circle me-2"></i>Panduan Pengisian Aturan Kelas Koleksi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-light border shadow-sm rounded-3 text-sm">
                    <p class="mb-2 fw-semibold">Cara mengisi kolom Aturan Kelas Koleksi:</p>
                    <ul class="mb-0 ps-3">
                        <li class="mb-2">
                            <strong>Nomor Tunggal:</strong> Ketik nomor spesifik secara penuh. <br>
                            <span class="text-muted">Contoh: <code>100</code> atau <code>330.1</code></span>
                        </li>
                        <li class="mb-2">
                            <strong>Awalan (Wildcard <code>*</code>):</strong> Gunakan tanda bintang <code>*</code> untuk mencakup semua nomor yang diawali angka tersebut. <br>
                            <span class="text-muted">Contoh: <code>005*</code> (akan mencakup 005, 005.1, 005.4, dst)</span>
                        </li>
                        <li class="mb-1">
                            <strong>Rentang (Tanda <code>-</code> atau <code>..</code>):</strong> Gunakan tanda hubung atau titik dua untuk menentukan jarak antara dua nomor (dari sampai dengan). <br>
                            <span class="text-muted">Contoh: <code>100-102</code> atau <code>100..102</code></span>
                        </li>
                    </ul>
                </div>
                <p class="text-muted small mt-3 mb-0"><strong>Catatan:</strong> Jika Anda memiliki beberapa aturan sekaligus untuk satu Prodi, pisahkan masing-masing aturan dengan tanda koma (<code>,</code>).<br>Contoh: <code>005*, 100-102, 330.1</code></p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Tambah Ruleset Kelas Koleksi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('cnclass.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Ruleset</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: FT-SPL atau D100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Aturan Kelas Koleksi</label>
                        <textarea name="rules" class="form-control rounded-3" rows="4" style="font-family: monospace; font-size: 0.8rem;" placeholder="Contoh: 005*, 100, 330.1"></textarea>
                        <div class="form-text small">Format: <code>001.4</code> (tunggal), <code>005*</code> (awalan), <code>100..102</code> (rentang). Pisahkan dengan koma.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-muted">Mapping Prodi <span class="fw-normal text-muted">(Alias)</span></label>
                        <input type="text" name="mappings" class="form-control rounded-3" placeholder="Contoh: D100, S100, D10A">
                        <div class="form-text small">Kode prodi yang diarahkan ke ruleset ini (pisahkan dengan koma).</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm shadow-sm">Simpan Ruleset</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleRules(id, btn) {
    const wrap = document.getElementById('rules-wrap-' + id);
    const isExpanded = wrap.classList.toggle('expanded');
    if (isExpanded) {
        btn.innerHTML = '<i class="fas fa-chevron-up me-1" style="font-size:0.6rem;"></i> Sembunyikan';
    } else {
        const total = wrap.querySelectorAll('.badge').length;
        btn.innerHTML = `<i class="fas fa-chevron-down me-1" style="font-size:0.6rem;"></i> Tampilkan semua (${total} aturan)`;
    }
}

// Shared edit modal — isi data dari data-* attribute tombol, hindari modal di dalam tr
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-ruleset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById('editModalShared');
            modal.querySelector('#editModalRulesetName').textContent = btn.dataset.name;
            modal.querySelector('#editModalName').value = btn.dataset.name;
            modal.querySelector('#editModalRules').value = btn.dataset.rules;
            modal.querySelector('#editModalMappings').value = btn.dataset.mappings;
            modal.querySelector('#editModalForm').action = btn.dataset.action;
            var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            bsModal.show();
        });
    });
});
</script>
@endpush
