@extends('dashboard.owner.layout')
@section('monitoring')
<p class="admin-intro">Mahasiswa dihitung satu kali; jika semester dipilih, hanya mahasiswa dengan KRS pada semester itu. Kelas mengikuti program studi mata kuliah.</p>
<div class="admin-table-wrap" role="region" aria-label="Laporan per program studi" tabindex="0"><table class="admin-table owner-report-table"><thead><tr><th>Program studi</th><th>Mahasiswa</th><th>Kelas</th><th>KRS disetujui</th><th>Nilai final</th><th>Rincian</th></tr></thead><tbody>
@forelse(($records ?? []) as $item)
<tr><td><strong>{{ $item->nama }}</strong><small class="owner-code">{{ $item->kode }}</small></td><td>{{ $item->students_count }}</td><td>{{ $item->classes_count }}</td><td>{{ $item->disetujui_count }}</td><td>{{ $item->final_count ?? 'Belum tersedia' }}</td><td>
<details class="owner-report-detail"><summary>Rincian program</summary><dl class="owner-report-metrics">
@foreach(['Mahasiswa dengan KRS disetujui' => $item->approved_count, 'KRS menunggu' => $item->menunggu_count, 'KRS ditolak' => $item->ditolak_count, 'Analisis tersimpan' => $item->analyses_count ?? 'Belum tersedia', 'Tindak lanjut' => $item->follow_ups_count ?? 'Belum tersedia'] as $label => $value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach
</dl><p class="owner-data-note">Analisis dan tindak lanjut dihitung per catatan, termasuk riwayat.</p><a class="owner-detail-link" href="{{ route('dashboard.owner', ['program_studi_id' => $item->id, 'semester_id' => $filters['semester_id'] ?? null]) }}">Ringkasan program</a></details>
</td></tr>
@empty @include('dashboard.owner.partials.empty', ['columns' => 6]) @endforelse
</tbody></table></div>@include('dashboard.owner.partials.pagination')
@endsection
