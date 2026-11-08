@extends('dashboard.owner.layout')
@section('monitoring')
<section class="admin-panel"><h2>{{ $record->nama }}</h2><p>{{ $record->nidn ?? 'NIDN belum tersedia' }} · {{ $record->programStudi?->nama ?? 'Program studi belum ditetapkan' }}</p><p class="admin-help">Daftar mencakup seluruh kelas yang diampu pada semester pilihan, termasuk mata kuliah lintas program.</p></section>@include('dashboard.owner.partials.class-table')
@endsection
