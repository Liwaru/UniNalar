@props(['href', 'disabled' => false])
@if ($disabled)
    <span {{ $attributes->class(['filter-reset']) }} aria-disabled="true">Hapus filter</span>
@else
    <a {{ $attributes->class(['filter-reset']) }} href="{{ $href }}">Hapus filter</a>
@endif
