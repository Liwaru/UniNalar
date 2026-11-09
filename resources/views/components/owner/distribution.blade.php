@props(['items', 'title', 'unit', 'kind' => 'grades', 'note' => ''])
@php
    $items = collect($items);
    $orders = [
        'krs' => ['draft', 'menunggu', 'disetujui', 'ditolak'],
        'grades' => ['A', 'AB', 'B', 'BC', 'C', 'D', 'E'],
        'attendance' => ['hadir', 'terlambat', 'izin', 'sakit', 'alpha', 'alpa'],
    ];
    $order = $orders[$kind] ?? [];
    $items = $items->sortBy(fn ($value, $key) => array_search($key, $order, true) === false ? count($order) : array_search($key, $order, true));
    $total = $items->sum();
    $multiple = $items->filter(fn ($count) => $count > 0)->count() > 1;
@endphp
<section class="owner-distribution" data-distribution="{{ $kind }}" data-total="{{ $total }}" aria-label="{{ $title }}">
    <div class="owner-section-heading"><h2>{{ $title }}</h2><p><strong>{{ number_format($total, 0, ',', '.') }}</strong> {{ $unit }}</p></div>
    @if ($total > 0)
        <dl class="owner-chart-data">
            @foreach ($items as $key => $count)
                @php
                    $label = $key === '' ? 'Huruf belum tercatat' : ($key === 'alpha' ? 'Alpa' : ucfirst(str_replace('_', ' ', $key)));
                    $share = $total > 0 ? round($count / $total * 100, 1) : 0;
                    $tone = match ($key) {
                        'disetujui', 'hadir' => 'success',
                        'menunggu', 'terlambat', 'izin', 'sakit' => 'warning',
                        'ditolak', 'alpha', 'alpa' => 'danger',
                        default => $kind === 'grades' ? 'primary' : 'neutral',
                    };
                @endphp
                <div class="owner-chart-row" data-category="{{ $key }}" data-count="{{ $count }}">
                    <dt>{{ $label }}</dt>
                    <dd><strong>{{ number_format($count, 0, ',', '.') }}</strong> <span>{{ number_format($share, 1, ',', '.') }}%</span></dd>
                    @if ($multiple)<div class="owner-chart-track" aria-hidden="true"><span class="is-{{ $tone }}" style="width: {{ $share }}%"></span></div>@endif
                </div>
            @endforeach
        </dl>
        @unless ($multiple)<p class="owner-data-note">Hanya satu kategori tercatat pada pilihan ini.</p>@endunless
    @else
        <p class="owner-data-note">Belum ada {{ $unit }} untuk pilihan ini.</p>
    @endif
    @if ($note)<p class="owner-data-note">{{ $note }}</p>@endif
</section>
