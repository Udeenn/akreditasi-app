@extends('layouts.app')

@section('title', 'Asisten Data AI')

@push('styles')
<style>
    .ai-chat-card {
        border: 1px solid var(--border-color, #e2e8f0) !important;
        box-shadow: var(--card-shadow, 0 4px 6px -1px rgb(0 0 0 / 0.08)) !important;
    }

    .ai-chat-header {
        background: var(--sidebar-bg, #ffffff);
        border-bottom: 1px solid var(--border-color, #e2e8f0) !important;
    }

    .ai-chat-body {
        height: clamp(320px, calc(100vh - 305px), 560px);
        overflow-y: auto;
        background: linear-gradient(180deg, rgba(74, 105, 255, 0.015) 0%, transparent 100%);
    }

    .ai-chat-footer {
        background: var(--sidebar-bg, #ffffff);
        border-top: 1px solid var(--border-color, #e2e8f0) !important;
    }

    .ai-badge-chip {
        font-size: 0.74rem;
        font-weight: 600;
        padding: 0.28rem 0.65rem;
        border-radius: 999px;
        line-height: 1.2;
    }

    .ai-prompt-card {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        width: 100%;
        text-align: left;
        padding: 0.6rem 0.75rem;
        border-radius: 10px;
        border: 1px solid var(--border-color, #e2e8f0);
        background: var(--sidebar-bg, #ffffff);
        color: var(--text-dark, #1e293b);
        transition: border-color 0.16s ease, background-color 0.16s ease, transform 0.16s ease;
    }

    .ai-prompt-card:hover {
        border-color: var(--primary-color, #4A69FF);
        background: var(--primary-light, #eef2ff);
        transform: translateY(-1px);
    }

    .ai-prompt-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .ai-prompt-title {
        font-size: 0.8rem;
        font-weight: 600;
        line-height: 1.2;
        color: var(--text-dark, #1e293b);
    }

    .ai-prompt-sub {
        font-size: 0.72rem;
        color: var(--text-light, #64748b);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .ai-bubble-user {
        max-width: 78%;
        background: var(--primary-color, #4A69FF);
        color: #ffffff;
        border-radius: 14px 14px 4px 14px;
        padding: 0.55rem 0.9rem;
        font-size: 0.875rem;
        line-height: 1.45;
    }

    .ai-bot-avatar {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .ai-bubble-bot {
        flex: 1 1 auto;
        min-width: 0;
        max-width: calc(100% - 38px);
        background: #f8fafc;
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 4px 14px 14px 14px;
        padding: 0.7rem 0.9rem;
        color: var(--text-dark, #1e293b);
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .ai-table-wrap {
        border: 1px solid var(--border-color, #e2e8f0);
        border-radius: 10px;
        overflow: hidden;
        background: var(--sidebar-bg, #ffffff);
    }

    .ai-table-toolbar {
        background: #f8fafc;
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        font-size: 0.78rem;
        color: var(--text-light, #64748b);
    }

    .ai-table-wrap .table {
        font-size: 0.8rem;
    }

    .ai-table-wrap .table th {
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        font-weight: 600;
        padding: 0.45rem 0.65rem;
    }

    .ai-sort-th {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }

    .ai-sort-th:hover {
        color: var(--primary-color, #4A69FF);
    }

    .ai-table-pager {
        background: #f8fafc;
        border-top: 1px solid var(--border-color, #e2e8f0);
        font-size: 0.76rem;
        color: var(--text-light, #64748b);
    }

    body.dark-mode .ai-table-pager {
        background: #0f172a;
        border-color: #334155;
        color: #94a3b8;
    }

    .ai-table-wrap .table td {
        padding: 0.42rem 0.65rem;
        border-color: var(--border-color, #e2e8f0);
    }

    .ai-query-pre {
        background: rgba(15, 23, 42, 0.04);
        border: 1px solid var(--border-color, #e2e8f0);
        color: var(--text-dark, #1e293b);
        font-size: 0.75rem;
        white-space: pre-wrap;
    }

    .ai-composer {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.3rem 0.35rem 0.3rem 0.85rem;
        border-radius: 12px;
        border: 1px solid var(--border-color, #e2e8f0);
        background: #f8fafc;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .ai-composer:focus-within {
        border-color: var(--primary-color, #4A69FF);
        box-shadow: 0 0 0 3px rgba(74, 105, 255, 0.12);
        background: var(--sidebar-bg, #ffffff);
    }

    .ai-composer .form-control {
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        padding: 0.35rem 0;
        font-size: 0.875rem;
        color: var(--text-dark, #1e293b);
    }

    body.dark-mode .ai-chat-card,
    body.dark-mode .ai-chat-header,
    body.dark-mode .ai-chat-footer,
    body.dark-mode .ai-prompt-card,
    body.dark-mode .ai-table-wrap {
        background: #1e293b;
        border-color: #334155 !important;
    }

    body.dark-mode .ai-prompt-card:hover {
        background: rgba(74, 105, 255, 0.14);
        border-color: #4A69FF !important;
    }

    body.dark-mode .ai-bubble-bot,
    body.dark-mode .ai-table-toolbar,
    body.dark-mode .ai-composer {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    body.dark-mode .ai-composer:focus-within {
        background: #0f172a;
    }

    body.dark-mode .ai-table-wrap .table {
        --bs-table-bg: #1e293b;
        --bs-table-color: #e2e8f0;
        --bs-table-hover-bg: rgba(74, 105, 255, 0.1);
        --bs-table-hover-color: #ffffff;
    }

    body.dark-mode .ai-query-pre {
        background: #0f172a;
        border-color: #334155;
        color: #cbd5e1;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-3 px-md-4 pt-2 pb-3">

    <x-breadcrumb title="Asisten Data AI" icon="fas fa-rocket">
        <x-slot name="subtitle">
            Tanyakan data anggota, dosen, kunjungan, koleksi, atau sirkulasi dalam bahasa sehari-hari.
        </x-slot>
    </x-breadcrumb>

    @if (!$isConfigured)
        <div class="alert alert-warning border-0 shadow-sm rounded-4 d-flex align-items-start gap-2 py-2 px-3 mb-3" role="alert">
            <x-icon name="lock" class="mt-1 flex-shrink-0" size="18" />
            <div class="small mb-0">
                <span class="fw-bold">API Key Gemini belum terbaca oleh server aplikasi.</span>
                Pastikan <code>GEMINI_API_KEY=your_key</code> sudah disimpan di <code>.env</code>, lalu recreate container (<code>docker compose up -d --force-recreate app</code>) dan jalankan <code>php artisan config:clear</code>. (Contoh model: <code>AI_MODEL=gemini-3.5-flash-lite</code>)
            </div>
        </div>
    @endif

    <div class="card unified-card ai-chat-card rounded-4">
        {{-- Header Ringkas --}}
        <div class="card-header ai-chat-header px-3 py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="ai-badge-chip bg-primary-soft d-inline-flex align-items-center gap-1">
                    <x-icon name="database" size="13" /> GEMINI &middot; {{ $model }}
                </span>
                <span class="ai-badge-chip bg-success-soft d-inline-flex align-items-center gap-1" title="Nama & NIM tidak dikirim ke cloud LLM">
                    <x-icon name="shield-check" size="13" /> Privacy-Safe
                </span>
            </div>
            <button type="button" id="btnResetChat" class="btn btn-sm btn-outline-secondary l px-3 py-1 d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                <x-icon name="refresh" size="13" /> Percakapan Baru
            </button>
        </div>

        {{-- Area Chat --}}
        <div class="card-body ai-chat-body p-3" id="chatContainer">
            <div id="emptyPromptState" class="mx-auto py-2" style="max-width: 780px;">
                <div class="text-center mb-3">
                    <div class="bg-primary-soft rounded-3 d-inline-flex p-2 mb-2">
                        <x-icon name="search" size="20" />
                    </div>
                    <h6 class="fw-bold mb-1 text-body-title">Mau cari data apa hari ini?</h6>
                    <p class="text-muted small mb-0" style="font-size: 0.8rem;">Pilih contoh pertanyaan cepat atau ketik pertanyaan Anda di bawah.</p>
                </div>

                @php
                    $quickPrompts = [
                        [
                            'icon' => 'user-graduate',
                            'title' => 'Mahasiswa Alumni & Prodi',
                            'sub' => 'Daftar alumni beserta prodi dan masa aktif kartu',
                            'prompt' => 'Carikan daftar mahasiswa alumni beserta prodi dan tanggal kadaluarsa kartunya',
                        ],
                        [
                            'icon' => 'users',
                            'title' => 'Dosen Paling Sering Berkunjung',
                            'sub' => '5 dosen dengan kunjungan terbanyak tahun ini',
                            'prompt' => 'Dosen yang paling sering berkunjung ke perpustakaan tahun ini 5 teratas',
                        ],
                        [
                            'icon' => 'calendar-check',
                            'title' => 'Kunjungan Bulan Ini',
                            'sub' => 'Rekap jumlah kunjungan per hari bulan berjalan',
                            'prompt' => 'Berapa total kunjungan perpustakaan bulan ini per hari?',
                        ],
                        [
                            'icon' => 'trophy',
                            'title' => 'Top 10 Buku Terlaris',
                            'sub' => 'Judul koleksi paling sering dipinjam tahun ini',
                            'prompt' => 'Tampilkan 10 buku yang paling sering dipinjam tahun ini',
                        ],
                        [
                            'icon' => 'book-open',
                            'title' => 'Cari Koleksi Prodi PGSD',
                            'sub' => 'Ebook dan buku referensi program studi PGSD',
                            'prompt' => 'Cari koleksi ebook atau buku untuk prodi PGSD',
                        ],
                        [
                            'icon' => 'chart-bar',
                            'title' => 'Ringkasan Peminjaman',
                            'sub' => 'Tren transaksi peminjaman buku tahun ini',
                            'prompt' => 'Ringkasan peminjaman buku tahun ini per bulan',
                        ],
                    ];
                @endphp

                <div class="row row-cols-1 row-cols-md-2 g-2">
                    @foreach ($quickPrompts as $item)
                        <div class="col">
                            <button type="button" class="ai-prompt-card quick-prompt-btn" data-prompt="{{ $item['prompt'] }}">
                                <span class="ai-prompt-icon bg-primary-soft">
                                    <x-icon :name="$item['icon']" size="15" />
                                </span>
                                <span class="min-w-0 flex-grow-1">
                                    <span class="ai-prompt-title d-block">{{ $item['title'] }}</span>
                                    <span class="ai-prompt-sub d-block">{{ $item['sub'] }}</span>
                                </span>
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Form Input --}}
        <div class="card-footer ai-chat-footer px-3 py-2">
            <form id="aiChatForm" class="ai-composer">
                <input type="text"
                    id="aiQuestionInput"
                    class="form-control"
                    placeholder="Ketik pertanyaan, misal: Dosen yang sering ke perpustakaan tahun ini..."
                    maxlength="1000"
                    required
                    autocomplete="off">
                <button type="submit" id="btnSendAi" class="btn btn-primary btn-sm rounded-3 px-3 py-2 text-nowrap d-inline-flex align-items-center gap-1" {{ !$isConfigured ? 'disabled' : '' }}>
                    <span id="sendIcon" class="d-inline-flex align-items-center"><x-icon name="arrow-right" size="14" /></span>
                    <span id="sendSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    <span>Kirim</span>
                </button>
            </form>
        </div>
    </div>

</div>

{{-- Template Ikon SVG Konsisten untuk Pesan Dinamis --}}
<template id="tplIconBot"><x-icon name="rocket" size="14" /></template>
<template id="tplIconCsv"><x-icon name="file-csv" size="13" /></template>
<template id="tplIconErr"><x-icon name="exclamation-circle" size="14" /></template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chatContainer = document.getElementById('chatContainer');
    const emptyState = document.getElementById('emptyPromptState');
    const form = document.getElementById('aiChatForm');
    const input = document.getElementById('aiQuestionInput');
    const btnSend = document.getElementById('btnSendAi');
    const sendIcon = document.getElementById('sendIcon');
    const sendSpinner = document.getElementById('sendSpinner');
    const btnReset = document.getElementById('btnResetChat');
    const isConfigured = {{ $isConfigured ? 'true' : 'false' }};

    const botIconHtml = document.getElementById('tplIconBot')?.innerHTML || '';
    const csvIconHtml = document.getElementById('tplIconCsv')?.innerHTML || '';
    const errIconHtml = document.getElementById('tplIconErr')?.innerHTML || '';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const storageKey = 'akreditasi_ai_chat_{{ auth()->id() ?? "guest" }}';
    let history = [];
    let chatItems = [];

    function saveChatState() {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify({
                history: history.slice(-12),
                items: chatItems.slice(-15),
            }));
        } catch (e) {}
    }

    function restoreChatState() {
        try {
            const raw = sessionStorage.getItem(storageKey);
            if (!raw) return;
            const saved = JSON.parse(raw);
            if (!Array.isArray(saved.items) || saved.items.length === 0) return;
            history = Array.isArray(saved.history) ? saved.history : [];
            chatItems = saved.items;
            chatItems.forEach(item => {
                if (item.role === 'user') appendUserBubble(item.text, false);
                else if (item.role === 'assistant' && item.data) appendAssistantBubble(item.data, false);
            });
        } catch (e) {}
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatSimpleMarkdown(text) {
        let safe = escapeHtml(text);
        safe = safe.replace(/^#{1,4}\s+(.+)$/gm, '<strong class="d-block mt-2 mb-1">$1</strong>');
        safe = safe.replace(/^-{3,}$/gm, '<hr class="my-2 opacity-25">');
        safe = safe.replace(/^\s*[\*-]\s+(.+)$/gm, '<div class="ps-2 mb-1">&bull; $1</div>');
        safe = safe.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        safe = safe.replace(/`([^`]+)`/g, '<code>$1</code>');
        return safe.replace(/(<\/(?:strong|div)>|<hr[^>]*>)\n/g, '$1').replace(/\n/g, '<br>');
    }

    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    function appendUserBubble(text, persist = true) {
        if (emptyState) emptyState.style.display = 'none';
        const wrap = document.createElement('div');
        wrap.className = 'd-flex justify-content-end mb-2';
        wrap.innerHTML = `<div class="ai-bubble-user shadow-sm">${escapeHtml(text)}</div>`;
        chatContainer.appendChild(wrap);
        if (persist) {
            chatItems.push({ role: 'user', text });
            saveChatState();
        }
        scrollToBottom();
    }

    function appendLoadingBubble() {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex align-items-start gap-2 mb-2';
        wrap.id = 'aiLoadingBubble';
        wrap.innerHTML = `
            <span class="ai-bot-avatar bg-primary-soft">${botIconHtml}</span>
            <div class="ai-bubble-bot shadow-sm d-inline-flex align-items-center gap-2" style="flex: 0 1 auto;">
                <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                <span class="small text-muted">Menyusun query & mengambil data...</span>
            </div>
        `;
        chatContainer.appendChild(wrap);
        scrollToBottom();
        return wrap;
    }

    function appendAssistantBubble(payload, persist = true) {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex align-items-start gap-2 mb-3';

        let tableHtml = '';
        const cols = Array.isArray(payload.columns) ? payload.columns : [];
        const rows = Array.isArray(payload.rows) ? payload.rows : [];

        if (cols.length > 0 && rows.length > 0) {
            const ths = cols.map(c =>
                `<th class="ai-sort-th" data-col="${escapeHtml(c)}">${escapeHtml(c.replace(/_/g, ' '))} <span class="ai-sort-ind text-muted opacity-50">&updownarrow;</span></th>`
            ).join('');

            const exportBtn = payload.export_url
                ? `<a href="${escapeHtml(payload.export_url)}" class="btn btn-success btn-sm l px-2 py-1 d-inline-flex align-items-center gap-1 text-nowrap" style="font-size: 0.74rem;">${csvIconHtml}<span>Export CSV</span></a>`
                : '';

            tableHtml = `
                <div class="ai-table-wrap mt-2">
                    <div class="ai-table-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 py-1">
                        <span class="ai-tbl-info"></span>
                        ${exportBtn}
                    </div>
                    <div class="table-responsive" style="max-height: 300px;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-header-theme sticky-top">
                                <tr><th class="text-center">#</th>${ths}</tr>
                            </thead>
                            <tbody class="ai-tbl-body"></tbody>
                        </table>
                    </div>
                    <div class="ai-table-pager d-none flex-wrap justify-content-between align-items-center gap-2 px-3 py-1"></div>
                </div>
            `;
        }

        const cachedPill = payload.cached
            ? `<span class="badge bg-success-soft text-success ms-1" style="font-size:0.68rem;">Cache &middot; 0 token</span>`
            : '';

        const queryToggleHtml = payload.query_summary
            ? `<details class="mt-2 small text-muted">
                    <summary style="cursor:pointer; font-size: 0.76rem;">Sumber Query ${cachedPill}</summary>
                    <pre class="ai-query-pre mb-0 mt-1 p-2 rounded-3">${escapeHtml(payload.query_summary)}</pre>
               </details>`
            : (cachedPill ? `<div class="mt-1">${cachedPill}</div>` : '');

        wrap.innerHTML = `
            <span class="ai-bot-avatar bg-primary-soft">${botIconHtml}</span>
            <div class="ai-bubble-bot shadow-sm">
                <div>${formatSimpleMarkdown(payload.answer || '')}</div>
                ${tableHtml}
                ${queryToggleHtml}
            </div>
        `;
        chatContainer.appendChild(wrap);
        if (cols.length > 0 && rows.length > 0) {
            initInteractiveTable(wrap, cols, rows, payload);
        }
        if (persist) {
            chatItems.push({ role: 'assistant', data: payload });
            saveChatState();
        }
        scrollToBottom();
    }

    function initInteractiveTable(wrap, cols, initialRows, payload) {
        const tbody = wrap.querySelector('.ai-tbl-body');
        const infoEl = wrap.querySelector('.ai-tbl-info');
        const pagerEl = wrap.querySelector('.ai-table-pager');
        const total = Number(payload.total_count ?? initialRows.length);
        const state = {
            rows: [...initialRows],
            page: 1,
            perPage: [25, 50, 100].includes(initialRows.length) ? initialRows.length : Math.min(50, Math.max(25, initialRows.length)),
            totalCount: total,
            sortBy: '',
            sortDir: 'asc',
            loading: false,
        };

        const canServerPage = Boolean(payload.page_url) && total > initialRows.length;

        function renderRows() {
            const offset = (state.page - 1) * state.perPage;
            tbody.innerHTML = state.rows.map((row, idx) => {
                const tds = cols.map(c => `<td>${escapeHtml(row[c] ?? '-')}</td>`).join('');
                return `<tr><td class="text-muted text-center">${offset + idx + 1}</td>${tds}</tr>`;
            }).join('');

            const from = state.rows.length > 0 ? offset + 1 : 0;
            const to   = offset + state.rows.length;
            infoEl.innerHTML = state.totalCount > state.rows.length
                ? `<strong>${from}&ndash;${to}</strong> dari <strong>${state.totalCount}</strong> data`
                : `<strong>${state.rows.length}</strong> ditampilkan &middot; Total cocok: <strong>${state.totalCount}</strong>`;

            wrap.querySelectorAll('.ai-sort-th').forEach(th => {
                const ind = th.querySelector('.ai-sort-ind');
                if (th.dataset.col === state.sortBy) {
                    ind.innerHTML = state.sortDir === 'asc' ? '&#9650;' : '&#9660;';
                    ind.classList.remove('opacity-50');
                } else {
                    ind.innerHTML = '&updownarrow;';
                    ind.classList.add('opacity-50');
                }
            });

            if (payload.page_url && state.totalCount > 25) {
                const totalPages = Math.max(1, Math.ceil(state.totalCount / state.perPage));
                pagerEl.classList.remove('d-none');
                pagerEl.classList.add('d-flex');
                pagerEl.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <span>Hal <strong>${state.page}</strong> / ${totalPages}</span>
                        <select class="form-select form-select-sm py-0 px-2 ai-per-page" style="width:auto; font-size:0.74rem;">
                            ${[25, 50, 100].map(n => `<option value="${n}" ${n === state.perPage ? 'selected' : ''}>${n}/hal</option>`).join('')}
                        </select>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary ai-pg-prev py-0 px-2" ${state.page <= 1 || state.loading ? 'disabled' : ''}>&laquo; Prev</button>
                        <button type="button" class="btn btn-outline-secondary ai-pg-next py-0 px-2" ${state.page >= totalPages || state.loading ? 'disabled' : ''}>Next &raquo;</button>
                    </div>
                `;
            }
        }

        async function loadServerPage(targetPage) {
            if (!payload.page_url || state.loading) return;
            state.loading = true;
            renderRows();
            try {
                const qs = new URLSearchParams({
                    page: String(targetPage),
                    per_page: String(state.perPage),
                    sort_by: state.sortBy,
                    sort_dir: state.sortDir,
                });
                const res = await fetch(`${payload.page_url}?${qs}`, { headers: { 'Accept': 'application/json' } });
                const json = await res.json();
                if (res.ok && json.status === 'success') {
                    state.rows = json.rows;
                    state.page = json.page;
                    state.totalCount = json.total_count;
                } else if (res.status === 404) {
                    infoEl.textContent = 'Sesi halaman kadaluarsa — kirim ulang pertanyaan.';
                }
            } catch (e) {} finally {
                state.loading = false;
                renderRows();
            }
        }

        wrap.querySelectorAll('.ai-sort-th').forEach(th => {
            th.addEventListener('click', () => {
                const col = th.dataset.col;
                state.sortDir = (state.sortBy === col && state.sortDir === 'asc') ? 'desc' : 'asc';
                state.sortBy = col;

                if (canServerPage || state.totalCount > state.rows.length) {
                    loadServerPage(1);
                } else {
                    const coll = new Intl.Collator('id', { numeric: true, sensitivity: 'base' });
                    state.rows.sort((a, b) => {
                        const r = coll.compare(String(a[col] ?? ''), String(b[col] ?? ''));
                        return state.sortDir === 'asc' ? r : -r;
                    });
                    renderRows();
                }
            });
        });

        pagerEl.addEventListener('click', (e) => {
            if (e.target.closest('.ai-pg-prev') && state.page > 1) loadServerPage(state.page - 1);
            if (e.target.closest('.ai-pg-next')) loadServerPage(state.page + 1);
        });

        pagerEl.addEventListener('change', (e) => {
            if (e.target.classList.contains('ai-per-page')) {
                state.perPage = Number(e.target.value) || 50;
                loadServerPage(1);
            }
        });

        renderRows();
    }

    function appendErrorBubble(message) {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex align-items-start gap-2 mb-2';
        wrap.innerHTML = `
            <span class="ai-bot-avatar bg-danger-soft">${errIconHtml}</span>
            <div class="alert alert-danger border-0 rounded-4 mb-0 py-2 px-3 small">
                ${escapeHtml(message)}
            </div>
        `;
        chatContainer.appendChild(wrap);
        scrollToBottom();
    }

    async function sendQuestion(question) {
        if (!question.trim()) return;
        appendUserBubble(question);
        input.value = '';
        input.disabled = true;
        btnSend.disabled = true;
        sendIcon?.classList.add('d-none');
        sendSpinner?.classList.remove('d-none');

        const loader = appendLoadingBubble();

        try {
            const res = await fetch('{{ route("admin.ai-assistant.query") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    question: question,
                    history: history.slice(-6),
                }),
            });

            const data = await res.json();
            loader.remove();

            if (!res.ok || data.status !== 'success') {
                appendErrorBubble(data.message || 'Gagal memproses pertanyaan AI.');
                return;
            }

            appendAssistantBubble(data, false);
            history.push({ role: 'user', text: question });
            history.push({ role: 'assistant', text: data.answer });
            chatItems.push({ role: 'assistant', data });
            saveChatState();
        } catch (err) {
            loader.remove();
            appendErrorBubble('Terjadi gangguan koneksi ke server.');
        } finally {
            input.disabled = false;
            btnSend.disabled = !isConfigured;
            sendSpinner?.classList.add('d-none');
            sendIcon?.classList.remove('d-none');
            input.focus();
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendQuestion(input.value);
    });

    document.querySelectorAll('.quick-prompt-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!isConfigured) {
                input.value = this.dataset.prompt;
                return;
            }
            sendQuestion(this.dataset.prompt);
        });
    });

    btnReset.addEventListener('click', function () {
        history = [];
        chatItems = [];
        try { sessionStorage.removeItem(storageKey); } catch (e) {}
        chatContainer.querySelectorAll(':scope > div:not(#emptyPromptState)').forEach(el => el.remove());
        if (emptyState) emptyState.style.display = '';
    });

    restoreChatState();
});
</script>
@endpush
