@extends('layouts.app')
@section('title', 'E-Resource - Scopus Search')

@section('content')
    <div x-data="scopusSearch()" class="container-fluid px-3 px-md-4 py-4">

        {{-- HEADER BANNER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unified-card border-0 shadow-sm page-header-banner">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-database me-2"></i>E-Resource (Scopus)
                            </h3>
                            <p class="mb-0 opacity-75">
                                Pencarian dokumen dan publikasi ilmiah terindeks Scopus
                            </p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-book-journal-whills fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SEARCH INTERFACE --}}
        <div class="card unified-card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0 text-primary">Form Pencarian</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="toggleAdvanced()">
                        <i class="fas" :class="isAdvanced ? 'fa-search-minus' : 'fa-sliders-h'"></i> 
                        <span x-text="isAdvanced ? 'Pencarian Sederhana' : 'Pencarian Spesifik (Advanced)'"></span>
                    </button>
                </div>

                {{-- Simple Search Form --}}
                <form @submit.prevent="search" x-show="!isAdvanced" x-transition>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" x-model="query" class="form-control" placeholder="Cari judul artikel, pengarang, atau keyword..." :required="!isAdvanced">
                        <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                            <span x-show="!isLoading">Cari Dokumen</span>
                            <span x-show="isLoading" style="display: none;"><i class="fas fa-spinner fa-spin me-2"></i>Mencari...</span>
                        </button>
                    </div>
                    <div class="form-text mt-2 text-muted">Contoh pencarian: <span class="fst-italic">"machine learning" OR AUTH(Smith)</span></div>
                </form>

                {{-- Advanced Search Form --}}
                <form @submit.prevent="searchAdvanced" x-show="isAdvanced" x-transition style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Judul Artikel (Title)</label>
                            <input type="text" x-model="advTitle" class="form-control" placeholder="Contoh: Artificial Intelligence">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Penulis (Author)</label>
                            <input type="text" x-model="advAuthor" class="form-control" placeholder="Contoh: Smith, J.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Kata Kunci (Keyword)</label>
                            <input type="text" x-model="advKeyword" class="form-control" placeholder="Contoh: deep learning">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Subjek (Subject)</label>
                            <select class="form-select" x-model="advSubject">
                                <option value="">Semua Subjek</option>
                                <option value="COMP">Computer Science</option>
                                <option value="ENGI">Engineering</option>
                                <option value="MEDI">Medicine</option>
                                <option value="SOCI">Social Sciences</option>
                                <option value="PHYS">Physics and Astronomy</option>
                                <option value="MATH">Mathematics</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold">Tahun Terbit (Year)</label>
                            <input type="number" x-model="advYear" class="form-control" placeholder="Contoh: 2023" min="1900" max="2099">
                        </div>
                        <div class="col-12 mt-4 text-end">
                            <button type="button" class="btn btn-light px-4 me-2" @click="resetAdvancedForm()" :disabled="isLoading">Reset</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="isLoading">
                                <span x-show="!isLoading"><i class="fas fa-search me-2"></i>Cari Spesifik</span>
                                <span x-show="isLoading" style="display: none;"><i class="fas fa-spinner fa-spin me-2"></i>Mencari...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ERROR ALERT --}}
        <div x-show="error" class="alert alert-danger shadow-sm border-0" style="display: none;">
            <i class="fas fa-exclamation-triangle me-2"></i> <span x-text="error"></span>
        </div>

        {{-- RESULTS TABLE --}}
        <div class="card unified-card border-0 shadow-sm" x-show="results.length > 0" x-transition.opacity style="display: none;">
            <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i>Hasil Pencarian</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%" class="text-center">No</th>
                                <th width="40%">Judul Artikel</th>
                                <th width="20%">Penulis Utama</th>
                                <th width="15%">Nama Jurnal/Sumber</th>
                                <th width="10%" class="text-center">Tahun</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(doc, index) in results" :key="index">
                                <tr>
                                    <td class="text-center" x-text="((currentPage - 1) * 25) + index + 1"></td>
                                    <td>
                                        <strong class="text-primary" x-text="doc['dc:title'] || 'Tidak ada judul'"></strong>
                                        <div class="text-muted small mt-1" x-show="doc['subtypeDescription']">
                                            <span class="badge bg-secondary opacity-75" x-text="doc['subtypeDescription']"></span>
                                        </div>
                                    </td>
                                    <td x-text="doc['dc:creator'] || '-'"></td>
                                    <td x-text="doc['prism:publicationName'] || '-'"></td>
                                    <td class="text-center" x-text="getYear(doc['prism:coverDate'])"></td>
                                    <td class="text-center">
                                        <a :href="getLink(doc)" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-3" x-show="getLink(doc)">
                                            <i class="fas fa-external-link-alt me-1"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            {{-- PAGINATION --}}
            <div class="card-footer bg-white py-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center" x-show="totalPages > 1">
                <span class="text-muted small mb-3 mb-md-0">
                    Menampilkan halaman <strong x-text="currentPage"></strong> dari <strong x-text="totalPages"></strong> 
                    (Total <span x-text="formatNumber(totalResults)"></span> dokumen)
                </span>
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <li class="page-item" :class="currentPage <= 1 ? 'disabled' : ''">
                            <button class="page-link" @click="goToPage(1)" :disabled="currentPage <= 1"><i class="fas fa-angle-double-left"></i></button>
                        </li>
                        <li class="page-item" :class="currentPage <= 1 ? 'disabled' : ''">
                            <button class="page-link" @click="goToPage(currentPage - 1)" :disabled="currentPage <= 1">Sebelumnya</button>
                        </li>
                        <li class="page-item" :class="currentPage >= totalPages ? 'disabled' : ''">
                            <button class="page-link" @click="goToPage(currentPage + 1)" :disabled="currentPage >= totalPages">Selanjutnya</button>
                        </li>
                        <li class="page-item" :class="currentPage >= totalPages ? 'disabled' : ''">
                            <button class="page-link" @click="goToPage(totalPages)" :disabled="currentPage >= totalPages"><i class="fas fa-angle-double-right"></i></button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        
        {{-- NO RESULTS --}}
        <div x-show="hasSearched && results.length === 0 && !isLoading && !error" class="card unified-card border-0 shadow-sm bg-light" style="display: none;">
            <div class="card-body text-center py-5">
                <i class="fas fa-search-minus fa-4x text-muted mb-3 opacity-50"></i>
                <h5 class="fw-bold text-muted">Tidak ada dokumen ditemukan</h5>
                <p class="text-muted mb-0">Coba gunakan kata kunci pencarian yang berbeda.</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
{{-- Include Alpine.js if it's not already in layouts.app (fallback) --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('scopusSearch', () => ({
            isAdvanced: false,
            query: '',
            
            // Advanced fields
            advTitle: '',
            advAuthor: '',
            advKeyword: '',
            advSubject: '',
            advYear: '',

            results: [],
            isLoading: false,
            error: null,
            hasSearched: false,
            
            // Pagination
            currentPage: 1,
            totalResults: 0,
            totalPages: 0,

            toggleAdvanced() {
                this.isAdvanced = !this.isAdvanced;
                this.error = null;
            },

            resetAdvancedForm() {
                this.advTitle = '';
                this.advAuthor = '';
                this.advKeyword = '';
                this.advSubject = '';
                this.advYear = '';
                this.error = null;
            },

            searchAdvanced() {
                let parts = [];
                
                if (this.advTitle.trim()) {
                    parts.push(`TITLE("${this.advTitle.trim()}")`);
                }
                if (this.advAuthor.trim()) {
                    parts.push(`AUTH("${this.advAuthor.trim()}")`);
                }
                if (this.advKeyword.trim()) {
                    parts.push(`KEY("${this.advKeyword.trim()}")`);
                }
                if (this.advSubject) {
                    parts.push(`SUBJAREA(${this.advSubject})`);
                }
                if (this.advYear) {
                    parts.push(`PUBYEAR = ${this.advYear}`);
                }

                if (parts.length === 0) {
                    this.error = "Harap isi setidaknya satu kolom pencarian spesifik.";
                    return;
                }

                this.query = parts.join(' AND ');
                this.executeSearch(1);
            },

            async search() {
                if (!this.query.trim()) return;
                this.executeSearch(1);
            },

            goToPage(page) {
                if (page >= 1 && page <= this.totalPages) {
                    this.executeSearch(page);
                }
            },

            async executeSearch(page = 1) {
                this.isLoading = true;
                this.error = null;
                this.hasSearched = true;
                this.currentPage = page;

                // Hitung index 'start' untuk API Scopus (dimulai dari 0)
                const start = (this.currentPage - 1) * 25;

                try {
                    const apiKey = '{{ config('services.scopus.api_key') ?: env('SCOPUS_API_KEY', '084a902b2b13bcebed5e401e22585d7e') }}';
                    const response = await fetch(`https://api.elsevier.com/content/search/scopus?query=${encodeURIComponent(this.query)}&count=25&start=${start}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-ELS-APIKey': apiKey
                        }
                    });
                    
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || 'Terjadi kesalahan saat mengambil data dari Scopus.');
                    }

                    if (data['search-results']) {
                        this.results = data['search-results']['entry'] || [];
                        this.totalResults = parseInt(data['search-results']['opensearch:totalResults']) || 0;
                        this.totalPages = Math.ceil(this.totalResults / 25);
                    } else {
                        this.results = [];
                        this.totalResults = 0;
                        this.totalPages = 0;
                    }
                } catch (err) {
                    this.error = err.message;
                    this.results = [];
                    this.totalResults = 0;
                    this.totalPages = 0;
                } finally {
                    this.isLoading = false;
                }
            },

            getYear(dateString) {
                if (!dateString) return '-';
                return dateString.split('-')[0];
            },

            getLink(doc) {
                if (doc.link && Array.isArray(doc.link)) {
                    const scopusLink = doc.link.find(l => l['@ref'] === 'scopus');
                    if (scopusLink) return scopusLink['@href'];
                }
                return null;
            },
            
            formatNumber(num) {
                return new Intl.NumberFormat('id-ID').format(num);
            }
        }));
    });
</script>
@endpush
