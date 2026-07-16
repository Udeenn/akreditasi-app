@extends('layouts.app')

@section('title', 'Transkrip')

@section('content')
    <div class="container-fluid px-3 px-md-4 pt-2 pb-4">

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

        <x-breadcrumb title="Data Transkrip" icon="fas fa-scroll">
            <x-slot name="subtitle">
                Kelola data transkrip staff perpustakaan
            </x-slot>
        </x-breadcrumb>

        {{-- 2. SEARCH & ACTION --}}
        <div class="card unified-card border-0 shadow-sm filter-card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    @can('admin-action')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                            data-bs-target="#transkripModal">
                            <i class="fas fa-plus me-2"></i> Tambah Data Transkrip
                        </button>
                        @include('modal.create-transkrip')
                    @endcan

                    <form action="{{ route('transkrip.index') }}" method="GET" class="d-flex ms-auto">
                        <input type="text" name="search" class="form-control me-2"
                            placeholder="Cari ID, Nama Transkrip, Tahun..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        @if (request('search'))
                            <a href="{{ route('transkrip.index') }}" class="btn btn-outline-secondary btn-sm px-4 fw-bold shadow-sm"><i class="fas fa-undo me-2"></i> Reset</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- 3. DATA TABLE --}}
        <div class="card unified-card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 unified-table text-center"
                        id="data-table-transkrip">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>ID Staff</th>
                                <th>Nama Transkrip</th>
                                <th>File Transkrip</th>
                                <th>Tahun</th>
                                @can('admin-action')
                                    <th>Aksi</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transkrip as $no => $item)
                                <tr>
                                    <td>{{ $no + $transkrip->firstItem() }}</td>
                                    <td>{{ $item->id_staf }}</td>
                                    <td>{{ $item->judul_transkrip }}</td>
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
                                                data-bs-target="#editTranskrip{{ $item->id }}">Edit
                                            </button>
                                            <form action="{{ route('transkrip.destroy', $item->id) }}" method="POST"
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
                                @include('modal.edit-transkrip', ['transkrip' => $item])
                            @empty
                                <tr>
                                    <td colspan="{{ Auth::user()->can('admin-action') ? '6' : '5' }}" class="text-center">
                                        Tidak ada data transkrip ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $transkrip->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

