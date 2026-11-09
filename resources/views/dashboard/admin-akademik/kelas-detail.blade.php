@extends('layouts.role-dashboard')
@section('title', 'Detail Kelas Kuliah')
@section('page-title', 'Detail Kelas Kuliah')
@section('content')
    <article class="admin-panel">
        <h2>{{ $record->mataKuliah->nama }} · {{ $record->kode_kelas }}</h2>
        <dl class="detail-fields">
            @foreach (['Mata kuliah' => $record->mataKuliah->kode, 'Program studi' => $record->mataKuliah->programStudi->nama, 'Semester' => $record->semester->kode, 'Dosen pengampu' => $record->dosen?->nama ?? 'Belum ditugaskan', 'Kapasitas' => $record->kapasitas ?? 'Tanpa batas', 'Peserta dan pengajuan menunggu' => $enrollments, 'Ruang' => $record->ruang ?? 'Belum tersedia'] as $label => $value)
                <div ><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
        <p>Identitas dan pengampu kelas dikunci setelah kelas digunakan dalam KRS.</p>
        <a class="admin-button" href="{{ route('dashboard.admin-akademik.kelas-kuliah.edit', $record) }}">Ubah kelas</a>
        <a class="admin-button secondary" href="{{ route('dashboard.admin-akademik.kelas-kuliah.index') }}">Kembali ke daftar</a>
    </article>
    <section class="admin-panel"><h2 class="academic-card-title">Peserta dan pilihan KRS</h2>
        <form class="admin-search" method="GET" action="{{ url()->current() }}"><label class="admin-filter-field" for="roster-status"><span>Status KRS</span><select id="roster-status" name="status"><option value="">Semua</option>@foreach (['draft', 'menunggu', 'disetujui', 'ditolak'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><div class="filter-actions"><button class="admin-button" type="submit">Terapkan filter</button>@if (!empty($filters['status']))<x-filter-reset :href="url()->current()" />@endif</div>
</form>
        <div class="admin-table-wrap" role="region" aria-label="Peserta dan pilihan KRS" tabindex="0"><table class="admin-table"><thead><tr><th>NIM</th><th>Mahasiswa</th><th>Program studi</th><th>Status KRS</th><th>Status pilihan</th></tr></thead><tbody>@forelse ($participants as $detail)<tr><td>{{ $detail->krs->mahasiswa->nim }}</td><td><a href="{{ route('dashboard.admin-akademik.mahasiswa.show', $detail->krs->mahasiswa_id) }}">{{ $detail->krs->mahasiswa->nama }}</a></td><td>{{ $detail->krs->mahasiswa->programStudi?->nama }}</td><td>{{ ucfirst($detail->krs->status) }}</td><td>{{ $detail->status }}</td></tr>@empty<tr><td colspan="5">Tidak ada peserta atau pilihan KRS sesuai filter.</td></tr>@endforelse</tbody></table></div>{{ $participants->links() }}
        <p class="admin-help">Peserta resmi adalah KRS disetujui dengan pilihan berstatus diambil. Pengajuan menunggu turut diperhitungkan dalam kapasitas.</p>
    </section>
@endsection
