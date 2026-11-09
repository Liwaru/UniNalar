@extends('dashboard.owner.layout')
@section('monitoring')
@include('dashboard.owner.partials.class-table', ['classScopeNote' => 'Program studi mengikuti mata kuliah. Peserta resmi mencakup mahasiswa lintas program dengan KRS disetujui dan mata kuliah diambil; nilai final dibandingkan dengan seluruh peserta resmi.'])
@endsection
