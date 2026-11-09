@props(['value', 'label' => null])
@php
    $tone = match ($value) {
        'aktif', 'disetujui', 'hadir' => 'success',
        'menunggu', 'izin', 'terlambat', 'sakit' => 'warning',
        'ditolak', 'alpha', 'alpa', 'tinggi' => 'danger',
        default => 'neutral',
    };
@endphp
<span class="owner-status is-{{ $tone }}">{{ $label ?? ($value === null ? 'Belum tersedia' : ($value === 'alpha' ? 'Alpa' : ucfirst(str_replace('_', ' ', $value)))) }}</span>
