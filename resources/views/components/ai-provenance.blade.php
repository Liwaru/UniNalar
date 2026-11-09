@props(['summary' => null])
@if (data_get($summary, 'transport') === 'mock')
    <p {{ $attributes->class(['data-meta']) }}>Hasil simulasi pengujian. Tidak ada panggilan ke layanan AI.</p>
@elseif (data_get($summary, 'transport') === 'live')
    <p {{ $attributes->class(['data-meta']) }}>Hasil live Groq tersimpan. Membuka halaman ini tidak mengirim permintaan baru.</p>
@endif
