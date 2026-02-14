@extends('layouts.app')

@section('title', 'MoU')

@section('content')
    <div class="container-fluid px-3 px-md-4 py-4">

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- 1. HEADER BANNER --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card unified-card border-0 shadow-sm page-header-banner">
                    <div
                        class="card-body p-4 bg-primary bg-gradient text-white d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                        <div class="mb-3 mb-md-0">
                            <h3 class="fw-bold mb-1">
                                <i class="fas fa-handshake me-2"></i>Data MoU
                            </h3>
                            <p class="mb-0 opacity-75">Kelola data Memorandum of Understanding</p>
                        </div>
                        <div class="d-none d-md-block opacity-50">
                            <i class="fas fa-handshake fa-4x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. SEARCH & ACTION --}}
        <div class="card unified-card border-0 shadow-sm filter-card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    @can('admin-action')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#mouModal">
                            <i class="fas fa-plus me-2"></i> Tambah Data MoU
                        </button>
                        @include('modal.create-mou')
                    @endcan

                    <form action="{{ route('mou.index') }}" method="GET" class="d-flex ms-auto">
                        <input type="text" name="search" class="form-control me-2"
                            placeholder="Cari Nama MoU, Tahun..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        @if (request('search'))
                            <a href="{{ route('mou.index') }}" class="btn btn-secondary ms-2">Reset</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- 3. DATA TABLE --}}
        <div class="card unified-card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 unified-table text-center" id="data-table-mou">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama MoU</th>
                                <th>File MoU</th>
                                <th>Tahun</th>
                                @can('admin-action')
                                    <th>Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($mou as $no => $item)
                                <tr>
                                    <td>{{ $no + $mou->firstItem() }}</td>
                                    <td>{{ $item->judul_mou }}</td>
                                    <td>
                                        <button class="btn btn-success btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#view-pdf-{{ $item->id }}">
                                            <i class="fas fa-eye me-1"></i> View Dokumen
                                        </button>
                                    </td>
                                    <td>{{ $item->tahun }}</td>
                                    @can('admin-action')
                                        <td>
                                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#editMou{{ $item->id }}">Edit
                                            </button>
                                            <form action="{{ route('mou.destroy', $item->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus?')">Delete</button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                                @include('modal.view-pdf', ['item' => $item])
                                @include('modal.edit-mou', ['mou' => $item])
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->can('admin-action') ? '5' : '4' }}" class="text-center">
                                        Tidak ada data MoU ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $mou->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
