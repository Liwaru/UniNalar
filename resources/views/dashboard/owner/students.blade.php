@extends('dashboard.owner.layout')
@section('monitoring')
<p class="admin-intro">Filter semester membatasi mahasiswa yang memiliki KRS pada semester tersebut. IPK tetap seluruh riwayat; IPS mengikuti semester pilihan atau semester terbaru dengan nilai final valid.</p>
<div class="admin-table-wrap" role="region" aria-label="Daftar mahasiswa" tabindex="0"><table class="admin-table"><thead><tr><th>NIM</th><th>Mahasiswa</th><th>Program studi</th><th>Angkatan</th><th>Status</th><th>IPS</th><th>IPK</th><th>Detail</th></tr></thead><tbody>
@forelse(($records ?? []) as $item) @php($s = $summaries->get($item->id))<tr><td>{{ $item->nim }}</td><td>{{ $item->nama }}</td><td>{{ $item->programStudi->nama }}</td><td>{{ $item->angkatan }}</td><td>{{ ucfirst($item->status) }}</td><td>{{ $s['ips'] !== null ? number_format($s['ips'], 2) : 'Belum tersedia' }}<small class="d-block">{{ $s['ips_semester'] }}</small></td><td>{{ $s['ipk'] !== null ? number_format($s['ipk'], 2) : 'Belum tersedia' }}</td><td><a href="{{ route('dashboard.owner.mahasiswa.show', ['student' => $item->id] + $filters) }}">Ringkasan</a></td></tr>@empty @include('dashboard.owner.partials.empty') @endforelse
</tbody></table></div>@include('dashboard.owner.partials.pagination')
@endsection
