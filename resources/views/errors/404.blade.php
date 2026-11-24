@extends('errors.layout')

@section('code', '404')
@section('title', 'Halaman Tidak Ditemukan')
@section('icon', 'search')
@section('accent', '#b45309')
@section('soft', 'rgba(180, 83, 9, 0.1)')
@section('accent_dark', '#fbbf24')
@section('soft_dark', 'rgba(251, 191, 36, 0.14)')

@section('message')
    Halaman atau tautan yang Anda cari mungkin telah dipindahkan, diubah alamatnya, atau sudah tidak tersedia.
@endsection

@section('secondary_action')
    <button type="button" onclick="window.history.back()" class="btn err-btn err-btn-secondary d-inline-flex align-items-center gap-2">
        <x-icon name="arrow-left" />
        <span>Halaman Sebelumnya</span>
    </button>
@endsection
