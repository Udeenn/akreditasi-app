@extends('layouts.app')

@section('title', 'Staff')

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

        <x-breadcrumb title="Data Staff" icon="fas fa-users">
            <x-slot name="subtitle">
                Kelola data staff perpustakaan
            </x-slot>
        </x-breadcrumb>

        {{-- 2. SEARCH & ACTION --}}
        <div class="card unified-card border-0 shadow-sm filter-card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    @can('admin-action')
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                            data-bs-target="#addStaffModal">
                            <i class="fas fa-plus me-2"></i> Tambah Data Staff
                        </button>
                        @include('modal.create-staff')
                    @endcan

                    <form action="{{ route('staff.index') }}" method="GET" class="d-flex ms-auto">
                        <input type="text" name="search" class="form-control me-2"
                            placeholder="Cari ID, Nama, Posisi..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">Cari</button>
                        @if (request('search'))
                            <a href="{{ route('staff.index') }}" class="btn btn-outline-secondary btn-sm px-4 fw-bold shadow-sm"><i class="fas fa-undo me-2"></i> Reset</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- 3. DATA TABLE --}}
        <div class="card unified-card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 unified-table text-center" id="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>UNI ID</th>
                                <th>Nama</th>
                                <th>Posisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staff as $no => $item)
                                <tr>
                                    <td>{{ $no + $staff->firstItem() }}</td>
                                    <td>{{ $item->id_staf }}</td>
                                    <td>{{ $item->nama_staff }}</td>
                                    <td>{{ $item->posisi }}</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#editStaff{{ $item->id }}">Edit
                                        </button>
                                        <form action="{{ route('staff.destroy', $item->id) }}" method="POST"
                                            style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus?')">Delete</button>
                                        </form>
                                    </td>
                                    @include('modal.edit-staff', ['staff' => $item])
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data staff ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $staff->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

