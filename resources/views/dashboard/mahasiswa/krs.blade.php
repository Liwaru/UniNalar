@extends('layouts.student')
@section('title', 'KRS dan Mata Kuliah')
@section('page-title', 'KRS dan Mata Kuliah')
@section('page-subtitle', 'Kelola pilihan kelas untuk semester aktif dan lihat riwayat pengajuan.')
@section('content')
    @if (session('status'))<div class="student-card mb-3" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="student-card mb-3" role="alert"><strong>Periksa pengajuan Anda.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if (! $workflowAvailable)
        <div class="empty-state" role="status"><strong>Pengajuan KRS belum tersedia</strong><span>Schema pendukung pengajuan dan riwayat KRS belum diterapkan. Data yang sudah tercatat tetap dapat dilihat di bawah.</span></div>
    @elseif (! $mahasiswa)
        <div class="empty-state" role="status"><strong>Profil operasional belum tersedia</strong><span>Akun ini belum terhubung ke profil mahasiswa operasional.</span></div>
    @elseif (! $activeSemester)
        <div class="empty-state" role="status"><strong>Semester operasional aktif belum tersedia</strong><span>Admin akademik perlu menyiapkan semester dan periode pengajuan resmi.</span></div>
    @else
        <section class="student-section">
            <div class="student-section-heading"><div><h2>{{ $activeSemester->kode }}</h2><p>Periode KRS: @if ($registrationOpen) sedang dibuka @else tidak dibuka atau belum diatur @endif · Status: {{ $activeKrs?->status ?? 'draft' }}</p></div></div>
            <article class="student-card">
                @if ($activeKrs?->details->isNotEmpty())
                    <table class="data-table"><thead><tr><th>Mata kuliah</th><th>Kode</th><th>Kelas</th><th>SKS</th><th>Aksi</th></tr></thead><tbody>
                        @foreach ($activeKrs->details as $detail)
                            @php($course = $detail->kelasKuliah?->mataKuliah)
                            @if ($course)<tr><td><strong>{{ $course->nama }}</strong></td><td>{{ $course->kode }}</td><td>{{ $detail->kelasKuliah?->kode_kelas ?? '—' }}</td><td>{{ $course->sks }}</td><td>
                                @if ($registrationOpen && in_array($activeKrs->status, ['draft', 'ditolak'], true))<form method="POST" action="{{ route('dashboard.mahasiswa.krs.detail.destroy', $detail) }}">@csrf @method('DELETE')<button type="submit">Hapus pilihan</button></form>@else {{ $activeKrs->status }} @endif
                            </td></tr>@endif
                        @endforeach
                    </tbody></table>
                @else
                    <div class="empty-state"><strong>Belum ada kelas dipilih</strong><span>Pilih kelas operasional yang tersedia di bawah selama periode KRS dibuka.</span></div>
                @endif

                @if ($registrationOpen && in_array($activeKrs?->status ?? 'draft', ['draft', 'ditolak'], true))
                    @if ($availableClasses->isNotEmpty())
                        <form method="POST" action="{{ route('dashboard.mahasiswa.krs.kelas.store') }}" class="mt-3">
                            @csrf
                            <label for="kelas-kuliah">Tambah kelas</label>
                            <select id="kelas-kuliah" name="kelas_kuliah_id" required>
                                <option value="">Pilih mata kuliah dan kelas</option>
                                @foreach ($availableClasses as $class)
                                    <option value="{{ $class->id }}">{{ $class->mataKuliah->kode }} · {{ $class->mataKuliah->nama }} · Kelas {{ $class->kode_kelas }} · {{ $class->mataKuliah->sks }} SKS</option>
                                @endforeach
                            </select>
                            <button type="submit">Tambahkan ke KRS</button>
                        </form>
                    @else
                        <div class="empty-state mt-3"><strong>Kelas operasional belum tersedia</strong><span>Katalog kelas aktif untuk program studi Anda belum tercatat.</span></div>
                    @endif
                    @if ($activeKrs?->details->isNotEmpty())<form method="POST" action="{{ route('dashboard.mahasiswa.krs.submit') }}" class="mt-3">@csrf<button type="submit">Ajukan KRS</button></form>@endif
                @elseif ($activeKrs && $activeKrs->status === 'menunggu')
                    <p role="status">Pengajuan sedang menunggu keputusan Admin Akademik.</p>
                @elseif ($activeKrs && $activeKrs->status === 'disetujui')
                    <p role="status">KRS telah disetujui. Perubahan tidak tersedia.</p>
                @endif
            </article>
        </section>
        @if ($histories->isNotEmpty())
            <section class="student-section"><div class="student-section-heading"><div><h2>Riwayat pengajuan</h2><p>Snapshot setiap versi disimpan saat pengajuan dan tidak berubah ketika pilihan berikutnya diedit.</p></div></div><article class="student-card">
                @foreach ($histories as $history)
                    <div class="data-list-item"><div class="data-list-top"><strong>Versi {{ $history->versi }} · {{ ucfirst($history->status) }}</strong><span>{{ $history->diajukan_pada?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</span></div>
                        <ul>@foreach (($history->snapshot_kelas['kelas'] ?? []) as $snapshot)<li>{{ $snapshot['kode_mata_kuliah'] ?? 'Kode tidak tersedia' }} · {{ $snapshot['nama_mata_kuliah'] ?? 'Mata kuliah tidak tersedia' }} · {{ $snapshot['sks'] ?? '—' }} SKS</li>@endforeach</ul>
                        @if ($history->catatan_keputusan)<p>{{ $history->catatan_keputusan }}</p>@endif
                    </div>
                @endforeach
            </article></section>
        @endif
    @endif

    @forelse ($krs as $record)
        @if ((int) $record->semester_id !== (int) ($activeSemester?->id ?? 0))
            <section class="student-section"><div class="student-section-heading"><div><h2>{{ $record->semester?->kode ?? 'Semester' }}</h2><p>Status KRS: {{ $record->status }}</p></div></div><article class="student-card">
                @if ($record->details->isNotEmpty())<table class="data-table"><thead><tr><th>Mata kuliah</th><th>Kode</th><th>Kelas</th><th>SKS</th></tr></thead><tbody>@foreach ($record->details as $detail)@php($course = $detail->kelasKuliah?->mataKuliah)@if ($course)<tr><td><strong>{{ $course->nama }}</strong></td><td>{{ $course->kode }}</td><td>{{ $detail->kelasKuliah?->kode_kelas ?? '—' }}</td><td>{{ $course->sks }}</td></tr>@endif @endforeach</tbody></table>@else<div class="empty-state"><strong>Detail KRS belum tersedia</strong><span>Belum ada mata kuliah pada KRS semester ini.</span></div>@endif
            </article></section>
        @endif
    @empty
        @unless ($activeSemester && $workflowAvailable)<div class="empty-state mt-3" role="status"><strong>Data KRS belum tersedia</strong><span>KRS tampil setelah tercatat pada profil akademik Anda.</span></div>@endunless
    @endforelse
@endsection
