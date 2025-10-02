<div class="card shadow-sm border-0">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Rekapitulasi Kunjungan per Bulan</h6>
        <button id="exportCsvBtn" class="btn btn-success btn-sm">
            <i class="fas fa-file-csv me-2"></i>Export CSV
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-vcenter mb-0">
                <thead class="">
                    <tr>
                        <th style='width: 10px;'>No</th>
                        <th class="ps-3 text-center" style="width: 180px;">Bulan</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Visualisasi Kunjungan</th>
                    </tr>
                </thead>
                <tbody class="py-5">
                    @php
                        $max = !empty($maxKunjunganBulanan) && $maxKunjunganBulanan > 0 ? $maxKunjunganBulanan : 1;
                    @endphp

                    @forelse ($dataHasil as $rekap)
                        @php
                            $persentase = ($rekap->jumlah / $max) * 100;
                        @endphp
                        <tr class="">
                            <td>{{ $loop->iteration }}</td>
                            <td class="ps-3 fw-medium">
                                <i class="fas fa-calendar-alt text-muted me-2"></i>
                                {{ \Carbon\Carbon::parse($rekap->bulan)->format('F Y') }}
                            </td>
                            <td class="text-center">{{ number_format($rekap->jumlah) }}</td>
                            <td>
                                <div class="progress" style="height: 22px;">
                                    <div class="progress-bar fw-bold" role="progressbar"
                                        style="width: {{ $persentase }}%;" aria-valuenow="{{ $rekap->jumlah }}"
                                        aria-valuemin="0" aria-valuemax="{{ $max }}">
                                        @if ($persentase > 15)
                                            {{ round($persentase) }}%
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center py-4">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="">
                    <tr>
                        <th colspan="2" class="ps-3 fs-5 text-center">Total</th>
                        <th colspan="2" class="text-center fs-5">{{ number_format($dataHasil->sum('jumlah')) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
