@props(['title', 'description', 'record', 'label', 'tone' => 'primary'])
<button {{ $attributes->class(['confirm-trigger']) }} type="button" disabled data-confirm-trigger data-confirm-title="{{ $title }}" data-confirm-description="{{ $description }}" data-confirm-record="{{ $record }}" data-confirm-tone="{{ $tone }}">{{ $label }}</button>
