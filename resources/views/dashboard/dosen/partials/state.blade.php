@if (!$available)
    <div class="empty-state" role="status"><strong>Schema akademik belum tersedia</strong><span>Data ditampilkan setelah tabel pendukung tersedia.</span></div>
@elseif (!$lecturer)
    <div class="empty-state" role="status"><strong>Profil dosen belum tertaut</strong><span>Hubungi Admin Akademik untuk menghubungkan profil operasional aktif.</span></div>
@endif
