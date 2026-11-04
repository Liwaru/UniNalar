@extends('layouts.role-dashboard')
@section('title', 'Finalisasi Nilai')
@section('page-title', 'Finalisasi Nilai')
@section('page-subtitle', 'Nilai yang dapat ditinjau dibatasi pada kelas yang ditugaskan kepada akun dosen ini.')
@section('content')
    @if (session('status'))<div class="student-card mb-3" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="student-card mb-3" role="alert">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    @if (! $lecturer)
        <div class="empty-state" role="status"><strong>Profil dosen belum tertaut</strong><span>Hubungi Admin Akademik untuk menghubungkan akun ke profil dosen yang benar.</span></div>
    @elseif (! $available)
        <div class="empty-state" role="status"><strong>Finalisasi belum tersedia</strong><span>Schema finalisasi nilai belum diterapkan. Nilai operasional tidak diubah.</span></div>
    @elseif ($classes->isEmpty())
        <div class="empty-state" role="status"><strong>Kelas operasional belum tersedia</strong><span>Belum ada kelas non-demo yang ditugaskan kepada profil dosen ini.</span></div>
    @else
        @foreach ($classes as $class)
            <section class="student-section"><div class="student-section-heading"><div><h2>{{ $class->mataKuliah->kode }} · {{ $class->mataKuliah->nama }}</h2><p>{{ $class->semester->kode }} · Kelas {{ $class->kode_kelas }}</p></div></div><article class="student-card">
                @php($graded = $class->krsDetails->filter(fn ($detail) => $detail->nilai !== null)->values())
                @if ($graded->isEmpty())<div class="empty-state"><strong>Belum ada nilai tercatat</strong><span>Halaman ini tidak membuat nilai; hanya catatan nilai yang sudah ada yang dapat difinalisasi.</span></div>
                @else
                    <table class="data-table"><thead><tr><th>Mahasiswa</th><th>Nilai angka</th><th>Huruf</th><th>Mutu</th><th>Status finalisasi</th><th>Aksi</th></tr></thead><tbody>
                        @foreach ($graded as $detail)
                            @php($grade = $detail->nilai)
                            <tr><td>{{ $detail->krs->mahasiswa->nama }} · {{ $detail->krs->mahasiswa->nim }}</td><td>{{ $grade->nilai_angka ?? 'Belum tersedia' }}</td><td>{{ $grade->nilai_huruf ?? 'Belum tersedia' }}</td><td>{{ $grade->nilai_mutu ?? 'Belum tersedia' }}</td><td>{{ $grade->final_at ? 'Final · '.$grade->final_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') : 'Belum final' }}</td><td>
                                @if (! $grade->final_at)<form method="POST" action="{{ route('dashboard.dosen.nilai.finalize', $grade) }}">@csrf<button type="submit">Finalisasi</button></form>@else Tidak dapat diubah @endif
                            </td></tr>
                        @endforeach
                    </tbody></table>
                @endif
            </article></section>
        @endforeach
    @endif
@endsection
