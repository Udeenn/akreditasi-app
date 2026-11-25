{{-- Shared Filter Card untuk halaman koleksi per prodi --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card unified-card border-0 shadow-sm filter-card mb-4">
            <div class="card-header border-bottom-0 pt-4 pb-0 px-4">
                <h5 class="mb-0 fw-bold"><i class="fas fa-filter text-primary me-2"></i> Filter Data</h5>
            </div>
            <div class="card-body px-4 pb-4 pt-3">
                <form method="GET" action="{{ route($route) }}" class="row g-3 align-items-end" id="{{ $formId }}">
                    <div class="col-12 col-md-4">
                        <label for="prodi" class="form-label small text-muted text-uppercase">Pilih Prodi</label>
                        <select name="prodi" id="prodi" class="form-select" required>
                            <option value="" disabled {{ empty($prodi) || $prodi === 'initial' ? 'selected' : '' }}>-- Pilih Program Studi --</option>
                            @foreach ($listprodi as $p)
                                <option value="{{ $p->authorised_value }}" {{ $prodi == $p->authorised_value ? 'selected' : '' }}>
                                    @if($showCodeInLabel ?? true)
                                        ({{ $p->authorised_value }}) - {{ $p->lib }}
                                    @else
                                        {{ $p->lib }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="tahun" class="form-label small text-muted text-uppercase">Tahun Terbit</label>
                        <select name="tahun" id="tahun" class="form-select">
                            <option value="all" {{ $tahunTerakhir == 'all' ? 'selected' : '' }}>Semua Tahun</option>
                            @for ($i = ($yearStart ?? 1); $i <= 10; $i++)
                                <option value="{{ $i }}" {{ $tahunTerakhir == $i ? 'selected' : '' }}>
                                    {{ $i }} Tahun Terakhir
                                </option>
                            @endfor
                        </select>
                    </div>
                    @if($showWithdrawn ?? false)
                        <div class="col-12 col-md-3">
                            <label for="withdrawn" class="form-label small text-muted text-uppercase">Status Withdrawn</label>
                            <select name="withdrawn" id="withdrawn" class="form-select">
                                <option value="0" {{ ($withdrawn ?? '0') === '0' ? 'selected' : '' }}>Tidak (Aktif)</option>
                                <option value="1" {{ ($withdrawn ?? '0') === '1' ? 'selected' : '' }}>Ya (Withdrawn)</option>
                                <option value="all" {{ ($withdrawn ?? '0') === 'all' ? 'selected' : '' }}>Semua</option>
                            </select>
                        </div>
                    @endif
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
