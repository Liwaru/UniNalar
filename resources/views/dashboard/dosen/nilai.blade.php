@extends('layouts.role-dashboard')
@section('title', 'Pengelolaan Nilai')
@section('page-title', 'Pengelolaan Nilai')
@section('page-subtitle', 'Input dan finalisasi nilai untuk kelas yang diampu.')
@section('content')
    @if (session('status'))<div class="student-card mb-3" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="student-card mb-3" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if (! $available)
        <div class="empty-state" role="status"><strong>Pengelolaan nilai belum tersedia</strong><span>Schema pendukung perlu diterapkan terlebih dahulu.</span></div>
    @elseif (! $lecturer)
        <div class="empty-state" role="status"><strong>Profil dosen belum tertaut</strong><span>Hubungi Admin Akademik untuk menghubungkan profil dosen operasional.</span></div>
    @elseif ($classes->isEmpty())
        <div class="empty-state" role="status"><strong>Kelas operasional belum tersedia</strong><span>Belum ada kelas yang ditugaskan kepada profil dosen ini.</span></div>
    @else
        <p class="student-card">Pilih nilai huruf sesuai hasil penilaian resmi. Nilai mutu dihitung dari skala huruf yang tersedia. Ambang konversi angka ke huruf belum ditetapkan; sistem tidak menebaknya.</p>
        @foreach ($classes as $class)
            <section class="student-section">
                <div class="student-section-heading"><div><h2>{{ $class->mataKuliah->kode }} · {{ $class->mataKuliah->nama }}</h2><p>{{ $class->semester->kode }} · Kelas {{ $class->kode_kelas }}</p></div></div>
                <article class="student-card">
                    @if ($class->krsDetails->isEmpty())
                        <div class="empty-state"><strong>Peserta belum tersedia</strong><span>Peserta tampil setelah KRS disetujui.</span></div>
                    @else
                        <div class="academic-table-wrap"><table class="data-table"><thead><tr><th>Mahasiswa</th><th>Penilaian</th><th>Nilai mutu</th><th>Status dan finalisasi</th></tr></thead><tbody>
                            @foreach ($class->krsDetails as $detail)
                                @php($grade = $detail->nilai)
                                <tr>
                                    <td>{{ $detail->krs->mahasiswa->nama }}<br>{{ $detail->krs->mahasiswa->nim }}</td>
                                    <td>
                                        @if ($grade?->final_at)
                                            {{ $grade->nilai_angka ?? 'Belum tersedia' }} · {{ $grade->nilai_huruf }}
                                        @else
                                            <form class="grade-entry-form" method="POST" action="{{ route('dashboard.dosen.nilai.save', $detail->id) }}">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="revision" value="{{ \App\Services\GradeEntryService::revision($grade) }}">
                                                <label for="angka-{{ $detail->id }}">Nilai angka (0–100)</label>
                                                <input id="angka-{{ $detail->id }}" type="number" name="nilai_angka" min="0" max="100" step="0.01" required value="{{ $grade?->nilai_angka }}">
                                                <label for="huruf-{{ $detail->id }}">Nilai huruf</label>
                                                <select id="huruf-{{ $detail->id }}" name="nilai_huruf" required>
                                                    <option value="">Pilih huruf</option>
                                                    @foreach ($gradePoints as $letter => $points)<option value="{{ $letter }}" @selected($grade?->nilai_huruf === $letter)>{{ $letter }} ({{ number_format($points, 2) }})</option>@endforeach
                                                </select>
                                                <button type="submit">Simpan nilai</button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>{{ $grade?->nilai_mutu ?? 'Belum tersedia' }}</td>
                                    <td>
                                        @if ($grade?->final_at)
                                            Final · {{ $grade->final_at->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}<br>Nilai terkunci
                                        @elseif ($grade)
                                            Belum final
                                            <form method="POST" action="{{ route('dashboard.dosen.nilai.finalize', $grade->id) }}">@csrf<button type="submit">Finalisasi nilai</button></form>
                                        @else
                                            Belum ada nilai
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody></table></div>
                    @endif
                </article>
            </section>
        @endforeach
        {{ $classes->links() }}
    @endif
@endsection
