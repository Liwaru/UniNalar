@if ($submission->status === 'menunggu' && $submission->krs->status === 'menunggu' && (int) $submission->latest_version === (int) $submission->versi)
    <span class="admin-mobile-status status-chip">Menunggu</span>
    @foreach (['disetujui' => 'Setujui', 'ditolak' => 'Tolak'] as $decision => $label)
        <form class="admin-decision-form" data-confirm-form inert method="POST" action="{{ route('dashboard.admin-akademik.krs-pengajuan.decision', [$submission->id, $decision]) }}">
            @csrf
            <label for="note-{{ $submission->id }}-{{ $decision }}">Catatan keputusan (opsional)</label><textarea id="note-{{ $submission->id }}-{{ $decision }}" name="catatan_keputusan" maxlength="2000" rows="2"></textarea>
            <x-confirm-action class="admin-button {{ $decision === 'ditolak' ? 'secondary' : '' }}" :title="$label.' pengajuan KRS?'" :description="$decision === 'disetujui' ? 'Kelas pada pengajuan ini akan disetujui sesuai versi yang ditinjau.' : 'Pengajuan ini akan ditolak. Mahasiswa dapat meninjau catatan keputusan dan memperbaiki pengajuannya sesuai periode KRS.'" :record="$submission->krs->mahasiswa->nama.' · Versi '.$submission->versi" :label="$label" />
        </form>
    @endforeach
@else
    <span class="status-chip">{{ ucfirst($submission->status) }}</span>
    @if ($submission->catatan_keputusan)<p class="admin-note">{{ $submission->catatan_keputusan }}</p>@endif
@endif
