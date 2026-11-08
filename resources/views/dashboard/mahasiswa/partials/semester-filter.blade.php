<form class="monitoring-filters student-card" method="GET" action="{{ url()->current() }}">
    <label for="semester-filter">Semester<select id="semester-filter" name="semester_id"><option value="">Semua semester</option>@foreach ($semesters as $semester)<option value="{{ $semester->id }}" @selected((string) ($filters['semester_id'] ?? '') === (string) $semester->id)>{{ $semester->kode }}</option>@endforeach</select></label>
    <button type="submit">Terapkan filter</button>
    @if (!empty($filters['semester_id']))<a class="text-link" href="{{ url()->current() }}">Hapus filter</a>@endif
</form>
@if ($errors->any())<div class="student-card mb-3" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
