@props(['config', 'record', 'compact' => false])
@php
    $name = $record->nama ?? $record->kode ?? $record->kode_kelas;
    $menuId = 'record-actions-'.$record->getKey();
@endphp
<div @class(['admin-row-actions', 'is-compact' => $compact]) data-record-actions>
    @if ($config['show'] ?? false)<a class="admin-row-action" href="{{ route($config['route'].'.show', $record) }}">Detail</a>@endif
    @if ($compact)<button type="button" class="admin-row-action admin-more-actions" hidden data-actions-toggle aria-label="Aksi untuk {{ $name }}" aria-expanded="false" aria-controls="{{ $menuId }}"><span aria-hidden="true">⋯</span></button>@endif
    <div id="{{ $menuId }}" class="admin-action-options" @if ($compact) data-actions-panel @endif>
        <a class="admin-row-action is-edit" href="{{ route($config['route'].'.edit', $record) }}">Ubah</a>
        <form method="POST" action="{{ route($config['route'].'.destroy', $record) }}" data-confirm-form inert>
            @csrf @method('DELETE')
            <x-confirm-action class="admin-row-action is-delete" :title="'Hapus '.$config['label'].'?'" description="Tindakan ini tidak dapat dibatalkan. Data yang masih digunakan dalam catatan akademik lain tidak dapat dihapus." :record="$name" label="Hapus" tone="danger" />
        </form>
    </div>
</div>
