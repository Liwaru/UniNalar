@if ($submission->status === 'menunggu' && $submission->krs->status === 'menunggu' && (int) $submission->latest_version === (int) $submission->versi)
    <span class="admin-mobile-status status-chip">Menunggu</span>
    @foreach (['disetujui' => 'Setujui', 'ditolak' => 'Tolak'] as $decision => $label)
        <form class="admin-decision-form" method="POST" action="{{ route('dashboard.admin-akademik.krs-pengajuan.decision', [$submission->id, $decision]) }}">
            @csrf
            <label for="note-{{ $submission->id }}-{{ $decision }}">Catatan keputusan (opsional)</label><textarea id="note-{{ $submission->id }}-{{ $decision }}" name="catatan_keputusan" maxlength="2000" rows="2"></textarea>
            <button class="admin-button {{ $decision === 'ditolak' ? 'secondary' : '' }}" type="submit">{{ $label }}</button>
        </form>
    @endforeach
@else
    <span class="status-chip">{{ ucfirst($submission->status) }}</span>
    @if ($submission->catatan_keputusan)<p class="admin-note">{{ $submission->catatan_keputusan }}</p>@endif
@endif
