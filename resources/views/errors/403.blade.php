@extends('errors.layout')

@section('code', '403')
@section('title', 'Akses Ditolak')
@section('icon', 'shield-lock-fill')
@section('accent', '#b91c1c')
@section('soft', 'rgba(185, 28, 28, 0.1)')
@section('accent_dark', '#fca5a5')
@section('soft_dark', 'rgba(252, 165, 165, 0.14)')

@section('message')
    {{ (isset($exception) && $exception->getMessage()) ? $exception->getMessage() : 'Anda tidak memiliki hak akses untuk membuka halaman ini. Halaman ini khusus untuk staf Pustakawan atau Administrator.' }}
@endsection

@section('secondary_action')
    @if(!($isLoggedIn ?? false))
        <a href="{{ route('cas.login') }}" class="btn err-btn err-btn-secondary d-inline-flex align-items-center gap-2">
            <x-icon name="sign-in-alt" />
            <span>Login Akun</span>
        </a>
    @else
        <button type="button" onclick="window.history.back()" class="btn err-btn err-btn-secondary d-inline-flex align-items-center gap-2">
            <x-icon name="arrow-left" />
            <span>Halaman Sebelumnya</span>
        </button>
    @endif
@endsection
