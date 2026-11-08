<nav class="lecturer-class-links" aria-label="Menu kelas">
    <a href="{{ route('dashboard.dosen.kelas.show', $class->id) }}">Detail kelas</a>
    <a href="{{ route('dashboard.dosen.kelas.peserta', $class->id) }}">Peserta resmi</a>
    <a href="{{ route('dashboard.dosen.nilai', ['kelas_id' => $class->id]) }}">Kelola nilai</a>
    <a href="{{ route('dashboard.dosen.kelas.kehadiran', $class->id) }}">Rekap kehadiran</a>
</nav>
