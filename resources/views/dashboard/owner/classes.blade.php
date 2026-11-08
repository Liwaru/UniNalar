@extends('dashboard.owner.layout')
@section('monitoring')
<p class="admin-intro">Program studi mengikuti mata kuliah kelas. Jumlah peserta mencakup seluruh mahasiswa resmi, termasuk mahasiswa lintas program.</p>@include('dashboard.owner.partials.class-table')
@endsection
