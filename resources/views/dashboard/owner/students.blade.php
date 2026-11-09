@extends('dashboard.owner.layout')
@section('monitoring')
<p class="admin-intro">Semester menyaring mahasiswa yang memiliki KRS. IPS mengikuti semester pilihan atau semester terbaru dengan nilai final valid; IPK tetap seluruh riwayat.</p>
<div class="admin-table-wrap" role="region" aria-label="Daftar mahasiswa" tabindex="0"><table class="admin-table"><thead><tr><th>NIM</th><th>Mahasiswa</th><th>Program studi</th><th>Angkatan</th><th>Status</th><th>IPS</th><th>IPK</th><th>Detail</th></tr></thead><tbody>
@forelse(($records ?? []) as $item) @php($s = $summaries->get($item->id))<tr><td class="owner-code">{{ $item->nim }}</td><td>{{ $item->nama }}</td><td>{{ $item->programStudi->nama }}</td><td>{{ $item->angkatan }}</td><td><x-owner.status :value="$item->status" /></td><td>{{ $s['ips'] !== null ? number_format($s['ips'], 2) : 'Belum tersedia' }}<small class="d-block">{{ $s['ips_semester'] }}</small></td><td>{{ $s['ipk'] !== null ? number_format($s['ipk'], 2) : 'Belum tersedia' }}</td><td><a class="owner-detail-link" href="{{ route('dashboard.owner.mahasiswa.show', ['student' => $item->id] + $filters) }}">Ringkasan</a></td></tr>@empty @include('dashboard.owner.partials.empty', ['columns' => 8]) @endforelse
</tbody></table></div>@include('dashboard.owner.partials.pagination')
@endsection
