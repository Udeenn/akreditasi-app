@extends('errors.layout')

@section('code', '500')
@section('title', 'Terjadi Kesalahan Server')
@section('icon', 'exclamation-triangle')
@section('accent', '#3249b3')
@section('soft', 'rgba(74, 105, 255, 0.1)')
@section('accent_dark', '#93c5fd')
@section('soft_dark', 'rgba(147, 197, 253, 0.14)')

@section('message')
    Sistem sedang mengalami kendala teknis saat memproses permintaan ini. Silakan muat ulang halaman beberapa saat lagi.
@endsection

@section('extra')
    @if(config('app.debug') && isset($exception) && $exception->getMessage())
        <div class="err-debug p-3 mb-4 rounded-3 text-start font-monospace">
            <strong>Debug Info:</strong> {{ $exception->getMessage() }}
        </div>
    @endif
@endsection

@section('actions')
    <button type="button" onclick="window.location.reload()" class="btn btn-primary err-btn d-inline-flex align-items-center gap-2">
        <x-icon name="refresh" />
        <span>Muat Ulang Halaman</span>
    </button>
@endsection
