<?php

namespace App\Http\Controllers;

use App\Models\KelasKuliah;
use App\Models\Semester;
use App\Services\OperationalAcademicData;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminKelasKuliahController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $query = OperationalAcademicData::classes()->with(['mataKuliah', 'semester', 'dosen']);
        if ($search !== '') {
            $query->where(fn ($q) => $q->where('kode_kelas', 'like', "%{$search}%")
                ->orWhereHas('mataKuliah', fn ($c) => $c->where('nama', 'like', "%{$search}%")->orWhere('kode', 'like', "%{$search}%"))
                ->orWhereHas('semester', fn ($s) => $s->where('kode', 'like', "%{$search}%"))
                ->orWhereHas('dosen', fn ($d) => $d->where('nama', 'like', "%{$search}%")));
        }
        $records = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $config = $this->config();

        return view('dashboard.admin-akademik.resource-index', compact('config', 'records', 'search'));
    }

    public function show(int $kelas_kuliah): View
    {
        $record = OperationalAcademicData::classes()->with(['mataKuliah.programStudi', 'semester', 'dosen'])->findOrFail($kelas_kuliah);
        $enrollments = $record->krsDetails()->whereHas('krs', fn ($q) => $q->whereIn('status', ['menunggu', 'disetujui']))->count();

        return view('dashboard.admin-akademik.kelas-detail', compact('record', 'enrollments'));
    }

    public function create(): View
    {
        return $this->form(null);
    }

    public function edit(int $kelas_kuliah): View
    {
        return $this->form(OperationalAcademicData::classes()->findOrFail($kelas_kuliah));
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            KelasKuliah::query()->create($this->values($request));
        } catch (QueryException $exception) {
            $this->handleConstraint($exception);
        }

        return redirect()->route('dashboard.admin-akademik.kelas-kuliah.index')->with('status', 'Kelas kuliah berhasil ditambahkan.');
    }

    public function update(Request $request, int $kelas_kuliah): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $kelas_kuliah): void {
                $record = OperationalAcademicData::classes()->lockForUpdate()->findOrFail($kelas_kuliah);
                $values = $this->values($request, $record);
                if ($record->krsDetails()->exists()) {
                    foreach (['mata_kuliah_id', 'semester_id', 'dosen_id', 'kode_kelas'] as $field) {
                        if ((string) ($values[$field] ?? '') !== (string) $record->{$field}) {
                            throw ValidationException::withMessages([$field => 'Identitas dan pengampu kelas yang sudah digunakan KRS tidak dapat diubah.']);
                        }
                    }
                }
                $reserved = $record->krsDetails()->whereHas('krs', fn ($q) => $q->whereIn('status', ['menunggu', 'disetujui']))->count();
                if ($values['kapasitas'] !== null && $values['kapasitas'] < $reserved) {
                    throw ValidationException::withMessages(['kapasitas' => 'Kapasitas tidak boleh lebih kecil daripada jumlah peserta dan pengajuan yang menunggu.']);
                }
                $record->update($values);
            }, 3);
        } catch (QueryException $exception) {
            $this->handleConstraint($exception);
        }

        return redirect()->route('dashboard.admin-akademik.kelas-kuliah.index')->with('status', 'Kelas kuliah berhasil diperbarui.');
    }

    public function destroy(int $kelas_kuliah): RedirectResponse
    {
        try {
            DB::transaction(function () use ($kelas_kuliah): void {
                $record = OperationalAcademicData::classes()->lockForUpdate()->findOrFail($kelas_kuliah);
                if ($record->krsDetails()->exists()) {
                    throw ValidationException::withMessages(['kelas' => 'Kelas masih digunakan oleh KRS dan tidak dapat dihapus.']);
                }
                $record->delete();
            }, 3);
        } catch (QueryException $exception) {
            $this->handleConstraint($exception);
        }

        return redirect()->route('dashboard.admin-akademik.kelas-kuliah.index')->with('status', 'Kelas kuliah berhasil dihapus.');
    }

    private function values(Request $request, ?KelasKuliah $record = null): array
    {
        $values = $request->validate([
            'mata_kuliah_id' => ['required', 'integer', Rule::exists('mata_kuliah', 'id')->where(fn ($q) => $q->whereIn('id', OperationalAcademicData::courses()->select('mata_kuliah.id')))],
            'semester_id' => ['required', 'integer', Rule::exists('semester', 'id')->where(fn ($q) => $q->where('kode', 'not like', 'DEMO%'))],
            'dosen_id' => ['nullable', 'integer', Rule::exists('dosen', 'id')->where(fn ($q) => $q->whereIn('id', OperationalAcademicData::lecturers()->where(fn ($d) => $d->where('aktif', true)->when($record?->dosen_id, fn ($scope, $id) => $scope->orWhere('id', $id)))->select('dosen.id')))],
            'kode_kelas' => ['required', 'string', 'max:20', 'not_regex:/^DEMO/i', Rule::unique('kelas_kuliah', 'kode_kelas')->where(fn ($q) => $q->where('mata_kuliah_id', $request->input('mata_kuliah_id'))->where('semester_id', $request->input('semester_id')))->ignore($record?->id)],
            'kapasitas' => ['nullable', 'integer', 'between:1,65535'],
            'ruang' => ['nullable', 'string', 'max:50'],
        ]);
        $values['dosen_id'] ??= null;
        $values['kapasitas'] ??= null;
        $values['ruang'] ??= null;

        return $values;
    }

    private function form(?KelasKuliah $record): View
    {
        $config = $this->config();
        $options = [
            'mata_kuliah_id' => OperationalAcademicData::courses()->orderBy('kode')->get()->mapWithKeys(fn ($c) => [$c->id => $c->kode.' · '.$c->nama])->all(),
            'semester_id' => Semester::query()->where('kode', 'not like', 'DEMO%')->orderByDesc('id')->pluck('kode', 'id')->all(),
            'dosen_id' => OperationalAcademicData::lecturers()->where(fn ($q) => $q->where('aktif', true)->when($record?->dosen_id, fn ($d, $id) => $d->orWhere('id', $id)))->orderBy('nama')->pluck('nama', 'id')->all(),
        ];

        return view('dashboard.admin-akademik.resource-form', compact('config', 'record', 'options'));
    }

    private function config(): array
    {
        return [
            'label' => 'Kelas kuliah', 'title' => 'Kelas Kuliah', 'route' => 'dashboard.admin-akademik.kelas-kuliah', 'show' => true,
            'columns' => ['mataKuliah.kode' => 'Kode mata kuliah', 'mataKuliah.nama' => 'Nama mata kuliah', 'semester.kode' => 'Semester', 'kode_kelas' => 'Kelas', 'dosen.nama' => 'Dosen', 'kapasitas' => 'Kapasitas', 'ruang' => 'Ruang'],
            'fields' => [
                'mata_kuliah_id' => ['label' => 'Mata kuliah', 'type' => 'select'],
                'semester_id' => ['label' => 'Semester', 'type' => 'select'],
                'dosen_id' => ['label' => 'Dosen pengampu', 'type' => 'select', 'nullable' => true],
                'kode_kelas' => ['label' => 'Kode kelas', 'type' => 'text'],
                'kapasitas' => ['label' => 'Kapasitas (kosong berarti tanpa batas)', 'type' => 'number', 'nullable' => true, 'max' => 65535],
                'ruang' => ['label' => 'Ruang', 'type' => 'text', 'nullable' => true],
            ],
        ];
    }

    private function handleConstraint(QueryException $exception): never
    {
        if (! in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23503'], true)) {
            throw $exception;
        }
        throw ValidationException::withMessages(['kelas' => 'Kelas duplikat atau data masih memiliki relasi. Muat ulang dan periksa pilihan Anda.']);
    }
}
