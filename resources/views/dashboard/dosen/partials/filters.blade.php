@if ($gradeMode ?? false)
<form class="monitoring-filters student-card" method="GET" action="{{ url()->current() }}">
    <label for="grade-semester-filter">Semester<select id="grade-semester-filter" name="semester_id"><option value="">Semua semester</option>@foreach ($semesters as $semester)<option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->kode }}</option>@endforeach</select></label>
    <button type="submit">Pilih semester</button>
</form>
@endif
<form class="monitoring-filters student-card" method="GET" action="{{ url()->current() }}">
    @if (!(($participantMode ?? false) || ($gradeMode ?? false)))
        <label for="semester-filter">Semester<select id="semester-filter" name="semester_id"><option value="">Semua semester</option>@foreach ($semesters as $semester)<option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->kode }}</option>@endforeach</select></label>
    @endif
    @if ($gradeMode ?? false)
        <input type="hidden" name="semester_id" value="{{ $filters['semester_id'] ?? '' }}">
        <label for="class-filter">Kelas<select id="class-filter" name="kelas_id"><option value="">Kelas pertama sesuai semester</option>@foreach ($classOptions as $option)<option value="{{ $option->id }}" @selected((int) ($filters['kelas_id'] ?? $selectedClass?->id) === (int) $option->id)>{{ $option->mataKuliah->kode }} · {{ $option->kode_kelas }} · {{ $option->semester->kode }}</option>@endforeach</select></label>
    @endif
    @unless ($dashboardMode ?? false)
        <label for="search-filter">{{ ($participantMode ?? false) || ($gradeMode ?? false) ? 'Nama atau NIM' : 'Cari mata kuliah atau kelas' }}<input id="search-filter" type="search" name="q" maxlength="255" value="{{ $filters['q'] ?? '' }}"></label>
    @endunless
    @if (($participantMode ?? false) || ($gradeMode ?? false))
        <label for="grade-filter">Status nilai<select id="grade-filter" name="status_nilai"><option value="">Semua status</option>@foreach (['belum_dinilai' => 'Belum dinilai', 'draft' => 'Draft', 'final' => 'Final'] as $key => $label)<option value="{{ $key }}" @selected(($filters['status_nilai'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
    @endif
    <button type="submit">Terapkan filter</button>
    <a class="text-link" href="{{ url()->current() }}">Hapus filter</a>
</form>
