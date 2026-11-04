@extends('layouts.role-dashboard')
@section('title', 'Pengajuan KRS')
@section('page-title', 'Tinjau Pengajuan KRS')
@section('content')
    <section class="admin-panel">
        <div class="admin-panel-heading"><div><p class="admin-intro">Tinjau snapshot pengajuan mahasiswa operasional. Keputusan dan catatan disimpan pada KRS serta riwayat versi.</p></div></div>
        @if (session('status'))<div class="admin-alert success" role="status">{{ session('status') }}</div>@endif
        @if ($errors->any())<div class="admin-alert error" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @if (! $available)
            <div class="admin-empty"><strong>Alur persetujuan belum tersedia.</strong><span>Schema riwayat pengajuan KRS belum diterapkan.</span></div>
        @else
            <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Mahasiswa</th><th>Semester</th><th>Versi</th><th>Diajukan</th><th>Snapshot mata kuliah</th><th>Keputusan</th></tr></thead><tbody>
                @forelse ($submissions as $submission)
                    <tr><td>{{ $submission->krs->mahasiswa->nama }}<br>{{ $submission->krs->mahasiswa->nim }}</td><td>{{ $submission->krs->semester->kode }}</td><td>{{ $submission->versi }}</td><td>{{ $submission->diajukan_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td><td><ul>@foreach (($submission->snapshot_kelas['kelas'] ?? []) as $class)<li>{{ $class['kode_mata_kuliah'] ?? '—' }} · {{ $class['nama_mata_kuliah'] ?? 'Nama tidak tersedia' }} · {{ $class['sks'] ?? '—' }} SKS</li>@endforeach</ul></td><td>
                        <form method="POST" action="{{ route('dashboard.admin-akademik.krs-pengajuan.decision', [$submission, 'disetujui']) }}">@csrf<label>Catatan (opsional)<textarea name="catatan_keputusan" maxlength="2000"></textarea></label><button class="admin-button" type="submit">Setujui</button></form>
                        <form method="POST" action="{{ route('dashboard.admin-akademik.krs-pengajuan.decision', [$submission, 'ditolak']) }}">@csrf<label>Catatan (opsional)<textarea name="catatan_keputusan" maxlength="2000"></textarea></label><button class="admin-button secondary" type="submit">Tolak</button></form>
                    </td></tr>
                @empty
                    <tr><td colspan="6"><div class="admin-empty"><strong>Tidak ada pengajuan yang menunggu.</strong><span>Pengajuan baru akan muncul di sini setelah mahasiswa mengirim KRS.</span></div></td></tr>
                @endforelse
            </tbody></table></div>
            <div class="admin-pagination">{{ $submissions->links() }}</div>
        @endif
    </section>
@endsection
