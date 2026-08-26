@extends('layouts.app')

@section('title', 'Pengaturan Fakultas')

@push('styles')
<style>
    .faculty-mappings-cell {
        max-width: 450px;
    }
    .faculty-badges-wrap {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
    }
    /* Badge base (light mode) */
    .cn-badge-primary {
        background: rgba(74,105,255,0.08);
        color: #4a69ff;
        border-color: rgba(74,105,255,0.2) !important;
    }
    .cn-badge-teal {
        background: rgba(20,184,166,0.1);
        color: #0d9488;
        border-color: rgba(20,184,166,0.2) !important;
    }

    /* Dark mode overrides */
    body.dark-mode .cn-badge-primary {
        background: rgba(74,105,255,0.25) !important;
        color: #a5b4fc !important;
        border-color: rgba(74,105,255,0.35) !important;
    }
    body.dark-mode .cn-badge-teal {
        background: rgba(20,184,166,0.2) !important;
        color: #5eead4 !important;
        border-color: rgba(20,184,166,0.3) !important;
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

    <x-breadcrumb title="Pengaturan Fakultas" icon="fas fa-building">
        <x-slot name="subtitle">
            Kelola data Fakultas dan pemetaan Kode Program Studi (Prodi).
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
            <h6 class="fw-bold mb-0"><i class="fas fa-building me-2 text-primary"></i>Daftar Fakultas & Mapping Kode Prodi</h6>
            <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 fw-semibold shadow-sm"
                data-bs-toggle="modal" data-bs-target="#createModal">
                <i class="fas fa-plus me-1"></i> Tambah Fakultas
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase small text-muted" style="font-size: 0.7rem; background: var(--bs-tertiary-bg);">
                        <tr>
                            <th class="ps-4 py-3" style="width: 40px;">No</th>
                            <th class="py-3" style="width: 250px;">Nama Fakultas</th>
                            <th class="py-3">Mapping Kode Prodi / Awalan</th>
                            <th class="py-3 text-center pe-4" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faculties as $index => $faculty)
                        <tr>
                            <td class="ps-4 text-muted small">{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-semibold" style="font-size: 0.88rem;">
                                    {{ $faculty->name }}
                                </span>
                            </td>
                            <td class="faculty-mappings-cell">
                                <div class="faculty-badges-wrap">
                                    @forelse($faculty->mappings as $mapping)
                                        @if(str_ends_with($mapping->prodi_code, '*'))
                                            <span class="badge rounded-2 border fw-normal cn-badge-primary" style="font-size: 0.72rem;">{{ $mapping->prodi_code }}</span>
                                        @else
                                            <span class="badge rounded-2 border fw-normal cn-badge-teal" style="font-size: 0.72rem;">{{ $mapping->prodi_code }}</span>
                                        @endif
                                    @empty
                                        <span class="text-muted small fst-italic">Belum ada kode prodi di-mapping.</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-1">
                                    @php
                                        $mappingsStr = implode(', ', $faculty->mappings->pluck('prodi_code')->toArray());
                                    @endphp
                                    <button type="button"
                                        class="btn btn-outline-primary btn-sm rounded-3 btn-edit-fakultas"
                                        data-id="{{ $faculty->id }}"
                                        data-name="{{ $faculty->name }}"
                                        data-mappings="{{ $mappingsStr }}"
                                        data-action="{{ route('fakultas.update', $faculty->id) }}">
                                        <i class="fas fa-edit me-1"></i>
                                    </button>
                                    <form action="{{ route('fakultas.destroy', $faculty->id) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus fakultas \'{{ $faculty->name }}\'?');">
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
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-building fa-2x mb-2 d-block opacity-25"></i>
                                Belum ada data fakultas. Tambahkan fakultas pertama Anda.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Tambah Fakultas Baru</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('fakultas.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Fakultas</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="Contoh: FT - Fakultas Teknik" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-muted">Mapping Kode Prodi / Awalan</label>
                        <textarea name="mappings" class="form-control rounded-3" rows="3" style="font-family: monospace; font-size: 0.8rem;" placeholder="Contoh: D100, D200, D400, D*"></textarea>
                        <div class="form-text small">Kode prodi dipisahkan koma. Gunakan <code>*</code> untuk awalan. Contoh: <code>D100, D*</code></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 btn-sm shadow-sm">Simpan Fakultas</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Shared Edit Modal --}}
<div class="modal fade" id="editModalShared" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Edit Fakultas: <span class="text-primary" id="editModalFacultyName"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editModalForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nama Fakultas</label>
                        <input type="text" name="name" id="editModalName" class="form-control rounded-3" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-semibold text-muted">Mapping Kode Prodi / Awalan</label>
                        <textarea name="mappings" id="editModalMappings" class="form-control rounded-3" rows="3" style="font-family: monospace; font-size: 0.8rem;"></textarea>
                        <div class="form-text small">Pisahkan dengan koma. Gunakan <code>*</code> untuk awalan. Contoh: <code>D100, D200, D*</code></div>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-fakultas').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var modal = document.getElementById('editModalShared');
            modal.querySelector('#editModalFacultyName').textContent = btn.dataset.name;
            modal.querySelector('#editModalName').value = btn.dataset.name;
            modal.querySelector('#editModalMappings').value = btn.dataset.mappings;
            modal.querySelector('#editModalForm').action = btn.dataset.action;
            var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            bsModal.show();
        });
    });
});
</script>
@endpush
