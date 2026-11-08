@foreach ($filterOptions ?? [] as $field => $definition)
    <label class="admin-filter-field" for="filter-{{ $field }}"><span>{{ $definition['label'] }}</span><select id="filter-{{ $field }}" name="{{ $field }}"><option value="">Semua</option>@foreach ($definition['choices'] as $id => $label)<option value="{{ $id }}" @selected((string) ($filters[$field] ?? '') === (string) $id)>{{ $label }}</option>@endforeach</select></label>
@endforeach
