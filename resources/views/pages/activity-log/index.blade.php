@extends('layouts.app')

@section('title', 'Audit Trail — Activity Log')

@push('styles')
<style>
    /* ── Stats Cards ── */
    .stat-glass {
        background: var(--sidebar-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-glass:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(74,105,255,0.12);
    }
    .stat-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    .stat-value { font-size: 1.75rem; font-weight: 800; line-height: 1; color: var(--text-dark); }
    .stat-label { font-size: 0.78rem; color: var(--text-light); font-weight: 500; margin-top: 2px; }

    /* ── Filter card ── */
    .filter-card {
        background: var(--sidebar-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.25rem 1.5rem;
    }

    /* ── Table ── */
    .log-table thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: var(--text-light);
        border-bottom: 2px solid var(--border-color);
        white-space: nowrap;
        padding: 0.75rem 1rem;
    }
    .log-table tbody td {
        font-size: 0.85rem;
        padding: 0.65rem 1rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    .log-table tbody tr:hover {
        background: var(--primary-light);
    }
    .url-cell {
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ua-cell {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.75rem;
        color: var(--text-light);
    }

    /* ── Top pages bar ── */
    .top-page-bar {
        background: var(--primary-light);
        border-radius: 6px;
        height: 8px;
        transition: width 0.6s ease;
    }
    .top-page-item {
        border-bottom: 1px solid var(--border-color);
        padding: 0.6rem 0;
    }
    .top-page-item:last-child { border-bottom: none; }

    /* ── Role badge ── */
    .badge-librarian { background: rgba(74,105,255,0.15); color: #4A69FF; border: 1px solid rgba(74,105,255,0.3); }
    .badge-patron    { background: rgba(16,185,129,0.12); color: #059669; border: 1px solid rgba(16,185,129,0.3); }

    /* ── Page header ── */
    .page-hero {
        background: linear-gradient(135deg, rgba(74,105,255,0.08), rgba(129,140,248,0.05));
        border: 1px solid rgba(74,105,255,0.15);
        border-radius: 20px;
        padding: 1.75rem 2rem;
        margin-bottom: 1.5rem;
    }
    body.dark-mode .page-hero {
        background: linear-gradient(135deg, rgba(74,105,255,0.12), rgba(129,140,248,0.08));
    }

    /* ── Empty state ── */
    .empty-state { padding: 3rem; text-align: center; color: var(--text-light); }
    .empty-state i { font-size: 3rem; opacity: 0.3; margin-bottom: 1rem; }

    /* ── Row clickable ── */
    .log-row:hover { background: var(--primary-light) !important; }

    /* ── Detail modal fields ── */
    .detail-field {
        background: var(--sidebar-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.65rem 0.9rem;
    }
    .detail-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-light);
        margin-bottom: 3px;
    }
    .detail-value {
        font-size: 0.88rem;
        font-weight: 500;
        color: var(--text-dark);
    }
    body.dark-mode .detail-field {
        background: #1e293b;
        border-color: var(--border-color);
    }

    /* ── Mobile responsive ── */
    @media (max-width: 767.98px) {
        .page-hero {
            padding: 1.25rem 1rem;
            border-radius: 14px;
        }
        .page-hero h4 { font-size: 1.15rem; }
        .stat-glass {
            padding: 0.9rem 1rem;
            border-radius: 12px;
        }
        .stat-value { font-size: 1.35rem; }
        .stat-label { font-size: 0.7rem; }
        .stat-icon { width: 40px; height: 40px; font-size: 1rem; }
        .filter-card { padding: 1rem; border-radius: 12px; }

        /* Table → card layout on mobile */
        .log-table-desktop { display: none !important; }
        .log-cards-mobile { display: block !important; }
    }

    @media (min-width: 768px) {
        .log-cards-mobile { display: none !important; }
        .log-table-desktop { display: block !important; }
    }

    /* Mobile log cards */
    .log-card-item {
        background: var(--sidebar-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        margin-bottom: 0.5rem;
        transition: background 0.15s ease;
    }
    .log-card-item:active {
        background: var(--primary-light);
    }
    .log-card-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }
    .log-card-row + .log-card-row { margin-top: 0.4rem; }
    .log-card-url {
        font-size: 0.75rem;
        color: var(--text-light);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 100%;
        margin-top: 0.3rem;
    }
</style>
@endpush

@section('content')

{{-- ── Page Hero ── --}}
<div class="page-hero">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="stat-icon" style="background: rgba(74,105,255,0.15); color: #4A69FF; width:56px; height:56px; border-radius:14px; font-size:1.5rem;">
                <i class="fas fa-shield-halved"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-0" style="color: var(--text-dark);">Audit Trail</h4>
                <p class="mb-0 small" style="color: var(--text-light);">Riwayat aktivitas seluruh pengguna sistem</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                <i class="fas fa-lock me-1"></i> Khusus Pustakawan
            </span>
        </div>
    </div>
</div>

{{-- ── Stats Row ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-glass">
            <div class="stat-icon" style="background: rgba(74,105,255,0.12); color: #4A69FF;">
                <i class="fas fa-list-check"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($stats['total']) }}</div>
                <div class="stat-label">Total Aktivitas</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-glass">
            <div class="stat-icon" style="background: rgba(16,185,129,0.12); color: #059669;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-value">{{ $stats['users'] }}</div>
                <div class="stat-label">Pengguna Unik</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-glass">
            <div class="stat-icon" style="background: rgba(139,92,246,0.12); color: #7c3aed;">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <div class="stat-value">{{ $stats['librarians'] }}</div>
                <div class="stat-label">Sesi Pustakawan</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-glass">
            <div class="stat-icon" style="background: rgba(245,158,11,0.12); color: #d97706;">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div>
                <div class="stat-value">{{ $stats['patrons'] }}</div>
                <div class="stat-label">Sesi Pengguna</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ── Left: Filter + Table ── --}}
    <div class="col-lg-8">

        {{-- Filter Card --}}
        <div class="filter-card mb-3">
            <form method="GET" action="{{ route('admin.activity-log') }}" class="row g-2 align-items-end" id="filterForm">
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="{{ $dateFrom }}" max="{{ now()->toDateString() }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="{{ $dateTo }}" max="{{ now()->toDateString() }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Role</label>
                    <select name="role" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="librarian" {{ request('role') === 'librarian' ? 'selected' : '' }}>Pustakawan</option>
                        <option value="patron"    {{ request('role') === 'patron'    ? 'selected' : '' }}>Pengguna</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Username</label>
                    <input type="text" name="username" class="form-control form-control-sm"
                           placeholder="Cari username..." value="{{ request('username') }}">
                </div>
                <div class="col-5 col-md-2">
                    <label class="form-label small fw-semibold mb-1">Route / URL</label>
                    <input type="text" name="route" class="form-control form-control-sm"
                           placeholder="cth: koleksi" value="{{ request('route') }}">
                </div>
                <div class="col-3 col-md-1">
                    <label class="form-label small fw-semibold mb-1">Tampil</label>
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>
                </div>
                <div class="col-4 col-md-1 d-flex gap-1 align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" title="Filter">
                        <i class="fas fa-filter"></i>
                    </button>
                    <a href="{{ route('admin.activity-log') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
                        <i class="fas fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- Log Table --}}
        <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header border-0 d-flex align-items-center justify-content-between py-3 px-4"
                 style="background: var(--sidebar-bg);">
                <div>
                    <span class="fw-semibold" style="color: var(--text-dark);">Log Aktivitas</span>
                    <span class="badge bg-primary-subtle text-primary ms-2">{{ $logs->total() }} entri</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <small style="color: var(--text-light);">
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                        @if($dateFrom !== $dateTo)
                            — {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                        @endif
                    </small>
                    {{-- Export CSV Button --}}
                    <a href="{{ route('admin.activity-log.export', request()->query()) }}"
                       class="btn btn-sm btn-outline-success d-flex align-items-center gap-1"
                       title="Download log sebagai CSV (filter aktif ikut tersimpan)">
                        <i class="fas fa-file-csv"></i>
                        <span class="d-none d-md-inline">Export CSV</span>
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                @if($logs->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-inbox d-block"></i>
                        <p class="mb-0">Tidak ada aktivitas pada rentang tanggal ini.</p>
                    </div>
                @else
                    {{-- Desktop: table --}}
                    <div class="log-table-desktop">
                        <div class="table-responsive">
                            <table class="table log-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Pengguna</th>
                                        <th>Role</th>
                                        <th>Metode</th>
                                        <th>Halaman / URL</th>
                                        <th>IP</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($logs as $log)
                                    <tr class="log-row" role="button" style="cursor:pointer;"
                                        data-id="{{ $log->id }}"
                                        data-time="{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}"
                                        data-username="{{ $log->username ?? '-' }}"
                                        data-name="{{ $log->user_name ?? '-' }}"
                                        data-role="{{ $log->user_role ?? '-' }}"
                                        data-method="{{ $log->method }}"
                                        data-route="{{ $log->route_name ?? '-' }}"
                                        data-url="{{ $log->url }}"
                                        data-ip="{{ $log->ip_address ?? '-' }}"
                                        data-status="{{ $log->status_code ?? '-' }}"
                                        data-ua="{{ $log->user_agent ?? '-' }}">
                                        <td class="text-nowrap" style="color: var(--text-light);">
                                            <span title="{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y H:i:s') }}">
                                                {{ $log->created_at->timezone('Asia/Jakarta')->format('d/m H:i') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold" style="color: var(--text-dark); font-size:0.82rem;">
                                                {{ $log->username ?? '-' }}
                                            </div>
                                            <div style="color: var(--text-light); font-size:0.75rem;">
                                                {{ $log->user_name ?? '' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if($log->user_role === 'librarian')
                                                <span class="badge badge-librarian rounded-pill">Pustakawan</span>
                                            @else
                                                <span class="badge badge-patron rounded-pill">Pengguna</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $log->method_badge }}-subtle text-{{ $log->method_badge }} rounded-pill px-2">
                                                {{ $log->method }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($log->route_name)
                                                <div class="fw-medium" style="font-size:0.82rem; color: var(--primary-color);">
                                                    {{ $log->route_name }}
                                                </div>
                                            @endif
                                            <div class="url-cell" title="{{ $log->url }}" style="color: var(--text-light); font-size:0.75rem;">
                                                {{ $log->url }}
                                            </div>
                                        </td>
                                        <td style="font-size:0.8rem; color: var(--text-light);">
                                            {{ $log->ip_address ?? '-' }}
                                        </td>
                                        <td>
                                            @php
                                                $sc = $log->status_code;
                                                $scColor = $sc >= 500 ? 'danger' : ($sc >= 400 ? 'warning' : 'success');
                                            @endphp
                                            <span class="badge bg-{{ $scColor }}-subtle text-{{ $scColor }} rounded-pill">
                                                {{ $sc ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Mobile: card list --}}
                    <div class="log-cards-mobile px-3 py-2">
                        @foreach($logs as $log)
                        <div class="log-card-item log-row" role="button"
                            data-id="{{ $log->id }}"
                            data-time="{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i:s') }}"
                            data-username="{{ $log->username ?? '-' }}"
                            data-name="{{ $log->user_name ?? '-' }}"
                            data-role="{{ $log->user_role ?? '-' }}"
                            data-method="{{ $log->method }}"
                            data-route="{{ $log->route_name ?? '-' }}"
                            data-url="{{ $log->url }}"
                            data-ip="{{ $log->ip_address ?? '-' }}"
                            data-status="{{ $log->status_code ?? '-' }}"
                            data-ua="{{ $log->user_agent ?? '-' }}">
                            <div class="log-card-row">
                                <div>
                                    <span class="fw-semibold" style="font-size:0.85rem; color: var(--text-dark);">{{ $log->username ?? '-' }}</span>
                                    @if($log->user_role === 'librarian')
                                        <span class="badge badge-librarian rounded-pill ms-1" style="font-size:0.65rem;">Pustakawan</span>
                                    @else
                                        <span class="badge badge-patron rounded-pill ms-1" style="font-size:0.65rem;">Pengguna</span>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    @php
                                        $sc = $log->status_code;
                                        $scColor = $sc >= 500 ? 'danger' : ($sc >= 400 ? 'warning' : 'success');
                                    @endphp
                                    <span class="badge bg-{{ $log->method_badge }}-subtle text-{{ $log->method_badge }} rounded-pill" style="font-size:0.65rem;">{{ $log->method }}</span>
                                    <span class="badge bg-{{ $scColor }}-subtle text-{{ $scColor }} rounded-pill" style="font-size:0.65rem;">{{ $sc ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="log-card-row">
                                <span style="font-size:0.78rem; color: var(--primary-color); font-weight:500;">{{ $log->route_name ?? '-' }}</span>
                                <span style="font-size:0.72rem; color: var(--text-light);">{{ $log->created_at->timezone('Asia/Jakarta')->format('d/m H:i') }}</span>
                            </div>
                            <div class="log-card-url" title="{{ $log->url }}">{{ $log->url }}</div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($logs->hasPages())
                        <div class="px-4 py-3 border-top" style="border-color: var(--border-color) !important;">
                            {{ $logs->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- ── Right: Top Pages ── --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; overflow: hidden;">
            <div class="card-header border-0 py-3 px-4" style="background: var(--sidebar-bg);">
                <span class="fw-semibold" style="color: var(--text-dark);">
                    <i class="fas fa-fire-flame-curved text-danger me-2"></i>Halaman Terpopuler
                </span>
                <div class="small mt-1" style="color: var(--text-light);">Periode yang dipilih</div>
            </div>
            <div class="card-body px-4 py-3">
                @if($topPages->isEmpty())
                    <div class="empty-state py-4">
                        <i class="fas fa-chart-bar d-block"></i>
                        <p class="small mb-0">Belum ada data.</p>
                    </div>
                @else
                    @php $maxHits = $topPages->first()->hits; @endphp
                    @foreach($topPages as $page)
                        <div class="top-page-item">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium" style="font-size:0.82rem; color: var(--text-dark);">
                                    {{ $page->route_name ?? basename($page->url) }}
                                </span>
                                <span class="badge bg-primary-subtle text-primary rounded-pill ms-2">
                                    {{ $page->hits }}x
                                </span>
                            </div>
                            <div style="background: var(--border-color); border-radius: 4px; height: 6px; overflow:hidden;">
                                <div class="top-page-bar"
                                     style="width: {{ $maxHits > 0 ? round(($page->hits / $maxHits) * 100) : 0 }}%;
                                            background: linear-gradient(90deg, #4A69FF, #818cf8);">
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Info card --}}
            <div class="card-footer border-0 px-4 py-3"
                 style="background: rgba(74,105,255,0.05); border-top: 1px solid var(--border-color) !important;">
                <div class="d-flex align-items-start gap-2">
                    <i class="fas fa-circle-info text-primary mt-1" style="font-size:0.8rem;"></i>
                    <p class="mb-0 small" style="color: var(--text-light);">
                        Log disimpan otomatis setiap kali pengguna membuka halaman.
                        Request AJAX dan export file tidak dicatat.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
    const fields = {
        time:    document.getElementById('d-time'),
        user:    document.getElementById('d-user'),
        role:    document.getElementById('d-role'),
        method:  document.getElementById('d-method'),
        route:   document.getElementById('d-route'),
        url:     document.getElementById('d-url'),
        ip:      document.getElementById('d-ip'),
        status:  document.getElementById('d-status'),
        ua:      document.getElementById('d-ua'),
    };

    document.querySelectorAll('.log-row').forEach(function (row) {
        row.addEventListener('click', function () {
            fields.time.textContent   = row.dataset.time;
            fields.user.textContent   = row.dataset.username + (row.dataset.name !== '-' ? ' (' + row.dataset.name + ')' : '');
            fields.role.textContent   = row.dataset.role;
            fields.method.textContent = row.dataset.method;
            fields.route.textContent  = row.dataset.route;
            fields.url.textContent    = row.dataset.url;
            fields.ip.textContent     = row.dataset.ip;
            fields.status.textContent = row.dataset.status;
            fields.ua.textContent     = row.dataset.ua;
            modal.show();
        });
    });
});
</script>
@endpush

{{-- ── Log Detail Modal ── --}}
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0" style="background: linear-gradient(135deg, rgba(74,105,255,0.08), transparent); border-radius: 16px 16px 0 0;">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:36px;height:36px;border-radius:10px;background:rgba(74,105,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-file-lines text-primary" style="font-size:0.9rem;"></i>
                    </div>
                    <h6 class="modal-title fw-bold mb-0" id="logDetailModalLabel" style="color:var(--text-dark);">Detail Aktivitas</h6>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body pt-3 px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-clock me-1"></i>Waktu</div>
                            <div id="d-time" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-user me-1"></i>Pengguna</div>
                            <div id="d-user" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-id-badge me-1"></i>Role</div>
                            <div id="d-role" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-code me-1"></i>Metode</div>
                            <div id="d-method" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-network-wired me-1"></i>IP Address</div>
                            <div id="d-ip" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-circle-check me-1"></i>Status</div>
                            <div id="d-status" class="detail-value"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-route me-1"></i>Route Name</div>
                            <div id="d-route" class="detail-value font-monospace" style="font-size:0.82rem;"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-link me-1"></i>URL</div>
                            <div id="d-url" class="detail-value" style="word-break:break-all; font-size:0.82rem;"></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="detail-field">
                            <div class="detail-label"><i class="fas fa-browser me-1"></i>User Agent</div>
                            <div id="d-ua" class="detail-value" style="word-break:break-all; font-size:0.78rem; color:var(--text-light);"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
