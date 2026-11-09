@extends('layouts.role-dashboard')
@section('title', 'Detail '.$config['label'])
@section('page-title', 'Detail '.$config['label'])
@section('content')
    <section class="admin-panel">
        <div class="admin-panel-heading"><h2 class="academic-card-title">{{ $record->nama ?? $record->kode }}</h2><a class="admin-button secondary" href="{{ route($config['route'].'.edit', $record->id) }}">Ubah {{ strtolower($config['label']) }}</a></div>
        @php
            $detailFields = [];
            foreach ($config['fields'] as $field => $definition) {
                if ($field === 'nama' || ($field === 'kode' && !isset($record->nama))) continue;
                $value = match ($field) {
                    'program_studi_id' => $record->programStudi?->nama,
                    'user_id' => $record->user?->username,
                    default => $record->{$field},
                };
                if ($value instanceof \DateTimeInterface) $value = $value->format($definition['type'] === 'datetime-local' ? 'd/m/Y H:i' : 'd/m/Y').($definition['type'] === 'datetime-local' ? ' WIB' : '');
                $detailFields[$field === 'aktif' ? 'Status' : $definition['label']] = $value;
            }
        @endphp
        <x-detail-fields :fields="$detailFields" />
        <h3 class="academic-card-title">Data terkait</h3>
        <dl class="admin-related-counts">
            @foreach ($config['protected_relations'] as $relation => $message)
                <div><dt>{{ ['krs' => 'KRS', 'mataKuliah' => 'Mata kuliah', 'kelasKuliah' => 'Kelas kuliah', 'analisisRisiko' => 'Analisis risiko'][$relation] ?? \Illuminate\Support\Str::headline($relation) }}:</dt><dd>{{ $record->{\Illuminate\Support\Str::snake($relation).'_count'} }}</dd></div>
            @endforeach
        </dl>
        <p class="admin-help">Data yang masih digunakan dalam catatan akademik lain tidak dapat dihapus.</p>
        <div class="detail-actions">
        @if ($config['key'] === 'program-studi')
            <nav class="detail-actions" aria-label="Data program studi">@foreach (['mahasiswa' => 'Mahasiswa', 'dosen' => 'Dosen', 'mata-kuliah' => 'Mata kuliah'] as $key => $label)<a class="academic-action is-secondary" href="{{ route('dashboard.admin-akademik.'.$key.'.index', ['program_studi_id' => $record->id]) }}">{{ $label }}</a>@endforeach</nav>
        @elseif (in_array($config['key'], ['semester', 'mata-kuliah', 'dosen'], true))
            @php($filter = ['semester' => 'semester_id', 'mata-kuliah' => 'mata_kuliah_id', 'dosen' => 'dosen_id'][$config['key']])
            <a class="academic-action is-secondary" href="{{ route('dashboard.admin-akademik.kelas-kuliah.index', [$filter => $record->id]) }}">Lihat kelas terkait</a>
        @endif
        <a class="academic-action is-secondary" href="{{ route($config['route'].'.index') }}">Kembali ke daftar</a>
        </div>
    </section>
    @if ($krs !== null)
        <section class="admin-panel"><h2 class="academic-card-title">KRS mahasiswa</h2>
            <div class="admin-table-wrap" role="region" aria-label="KRS mahasiswa" tabindex="0"><table class="admin-table"><thead><tr><th>Semester</th><th>Status KRS</th><th>Jumlah pilihan kelas</th><th>Pengajuan</th></tr></thead><tbody>
            @forelse ($krs as $item)<tr><td>{{ $item->semester->kode }}</td><td>{{ ucfirst($item->status) }}</td><td>{{ $item->details_count }}</td><td><a href="{{ route('dashboard.admin-akademik.krs-pengajuan.index', ['q' => $record->nim, 'semester_id' => $item->semester_id, 'status' => 'semua', 'cakupan' => 'semua_versi']) }}">Lihat riwayat pengajuan</a></td></tr>
            @empty<tr><td colspan="4">Belum ada KRS tercatat.</td></tr>@endforelse
            </tbody></table></div>{{ $krs->links() }}
        </section>
    @endif
@endsection
