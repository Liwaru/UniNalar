<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\ProgramStudi;
use App\Models\Semester;
use App\Models\User;
use App\Services\AdminAcademicData;
use App\Services\OperationalAcademicData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAcademicController extends Controller
{
    private const DEMO_USER_DOMAIN = '@demo.uninlar.test';

    public function index(Request $request): View
    {
        $config = $this->resourceConfig();
        $query = $this->operationalQuery($config);
        $filterFields = match ($config['key']) {
            'program-studi', 'semester' => ['aktif'],
            'mata-kuliah' => ['program_studi_id'],
            'dosen' => ['program_studi_id', 'aktif'],
            'mahasiswa' => ['program_studi_id', 'status'],
        };
        $filterData = app(AdminAcademicData::class)->filters($request, $filterFields);
        $filters = $filterData['filters'];
        app(AdminAcademicData::class)->apply($query, $filters);

        $search = trim($filters['q'] ?? '');
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($config, $search): void {
                foreach ($config['search'] as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $relatedColumn] = explode('.', $column, 2);
                        $builder->orWhereHas($relation, fn (Builder $related) => $related->where($relatedColumn, 'like', "%{$search}%"));
                    } else {
                        $builder->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        $records = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('dashboard.admin-akademik.resource-index', [
            'config' => $config,
            'records' => $records,
            'search' => $search ?? '',
        ] + $filterData);
    }

    public function create(): View
    {
        return $this->formView(null);
    }

    public function store(Request $request): RedirectResponse
    {
        $config = $this->resourceConfig();
        try {
            DB::transaction(function () use ($request, $config): void {
                $this->lockSemesters($config);
                $data = $this->validatedData($request, $config);
                $config['model']::query()->create($data);
            }, 3);
        } catch (QueryException $exception) {
            $this->handleConstraint($exception);
        }

        return redirect()->route($config['route'].'.index')->with('status', $config['label'].' berhasil ditambahkan.');
    }

    public function edit(string $record): View
    {
        $config = $this->resourceConfig();

        return $this->formView($this->operationalQuery($config)->findOrFail($record));
    }

    public function update(Request $request, string $record): RedirectResponse
    {
        $config = $this->resourceConfig();
        try {
            DB::transaction(function () use ($request, $record, $config): void {
                $this->lockSemesters($config);
                $model = $this->operationalQuery($config)->lockForUpdate()->findOrFail($record);
                $values = $this->validatedData($request, $config, $model);
                if ($config['key'] === 'mata-kuliah' && (int) $values['sks'] !== (int) $model->sks) {
                    // Serialize with registration's class locks before checking current
                    // detail rows; an older REPEATABLE READ snapshot is insufficient.
                    $classIds = $model->kelasKuliah()->orderBy('id')->lockForUpdate()->pluck('id');
                    $used = DB::table('krs_detail')->whereIn('kelas_kuliah_id', $classIds)->lockForUpdate()->first();
                    if ($used) {
                        throw ValidationException::withMessages(['sks' => 'SKS mata kuliah yang sudah digunakan KRS tidak dapat diubah agar riwayat akademik tetap konsisten.']);
                    }
                }
                $model->update($values);
            }, 3);
        } catch (QueryException $exception) {
            $this->handleConstraint($exception);
        }

        return redirect()->route($config['route'].'.index')->with('status', $config['label'].' berhasil diperbarui.');
    }

    public function destroy(string $record): RedirectResponse
    {
        $config = $this->resourceConfig();
        try {
            $message = DB::transaction(function () use ($config, $record): ?string {
                $model = $this->operationalQuery($config)->lockForUpdate()->findOrFail($record);
                foreach ($config['protected_relations'] as $relation => $message) {
                    if ($model->{$relation}()->lockForUpdate()->first()) {
                        return $message;
                    }
                }
                $model->delete();

                return null;
            }, 3);
            if ($message) {
                return back()->with('error', $message);
            }
        } catch (QueryException $exception) {
            if (! in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23503'], true)) {
                throw $exception;
            }

            return back()->with('error', 'Data masih digunakan oleh catatan akademik lain.');
        }

        return redirect()->route($config['route'].'.index')->with('status', $config['label'].' berhasil dihapus.');
    }

    public function show(string $record): View
    {
        $config = $this->resourceConfig();
        $record = $this->operationalQuery($config)->withCount(array_keys($config['protected_relations']))->findOrFail($record);
        $krs = $config['key'] === 'mahasiswa'
            ? OperationalAcademicData::krs()->where('mahasiswa_id', $record->id)->with('semester')->withCount('details')->orderByDesc('semester_id')->paginate(15)
            : null;

        return view('dashboard.admin-akademik.resource-detail', compact('config', 'record', 'krs'));
    }

    private function lockSemesters(array $config): void
    {
        if ($config['key'] === 'semester') {
            // Serialize the already-defined single-active-semester validation.
            Semester::query()->orderBy('id')->lockForUpdate()->get(['id']);
        }
    }

    private function handleConstraint(QueryException $exception): never
    {
        if (! in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23503'], true)) {
            throw $exception;
        }
        throw ValidationException::withMessages(['data' => 'Data duplikat atau relasinya telah berubah. Muat ulang dan periksa kembali isian.']);
    }

    private function formView(ProgramStudi|Semester|MataKuliah|Dosen|Mahasiswa|null $record): View
    {
        $config = $this->resourceConfig();
        $options = [];

        if (in_array($config['key'], ['mata-kuliah', 'dosen', 'mahasiswa'], true)) {
            $programs = ProgramStudi::query()->where('kode', 'not like', '%-DEMO')
                ->where(fn (Builder $query) => $query->where('aktif', true)
                    ->when($record?->program_studi_id, fn (Builder $scope, $id) => $scope->orWhere('id', $id)));
            $options['program_studi_id'] = $programs
                ->orderBy('nama')->pluck('nama', 'id')->all();
        }

        if (in_array($config['key'], ['dosen', 'mahasiswa'], true)) {
            $level = $config['key'] === 'dosen' ? User::ROLE_DOSEN : User::ROLE_MAHASISWA;
            $profileRelation = $config['key'];
            $options['user_id'] = User::query()->where('level', $level)
                ->where('email', 'not like', '%'.self::DEMO_USER_DOMAIN)
                ->where(function (Builder $query) use ($profileRelation, $record): void {
                    $query->whereDoesntHave($profileRelation);
                    if ($record?->user_id) {
                        $query->orWhere('id', $record->user_id);
                    }
                })->orderBy('name')->pluck('name', 'id')->all();
        }

        foreach ($config['fields'] as $field => &$definition) {
            if (in_array($definition['type'], ['text', 'email'], true) && preg_match('/(?:^|\|)max:(\d+)/', $config['rules'][$field] ?? '', $match)) {
                $definition['maxlength'] = (int) $match[1];
            }
            $definition['help'] = match ($field) {
                'user_id' => 'Pilih akun dengan role yang sesuai. Kredensial login dikelola terpisah.',
                'sks' => 'SKS tidak dapat diubah setelah mata kuliah digunakan dalam KRS.',
                'tahun_ajaran' => 'Format: 2026/2027.',
                'krs_mulai_pada', 'krs_selesai_pada' => 'Waktu Indonesia Barat (WIB). Isi waktu mulai dan akhir untuk membuka periode KRS.',
                default => null,
            };
        }
        unset($definition);

        return view('dashboard.admin-akademik.resource-form', compact('config', 'record', 'options'));
    }

    private function validatedData(Request $request, array $config, ProgramStudi|Semester|MataKuliah|Dosen|Mahasiswa|null $record = null): array
    {
        $rules = $config['rules'];

        foreach ($rules as $field => $fieldRules) {
            if (str_contains($fieldRules, 'unique:')) {
                $fieldRules .= ','.$record?->getKey().',id';
                $rules[$field] = $fieldRules;
            }
        }

        if (in_array($config['key'], ['dosen', 'mahasiswa'], true)) {
            $table = $config['key'];
            // The operational listing deliberately excludes this reserved domain.
            // Reject it at entry rather than reporting success for an invisible record.
            $rules['email'] .= '|not_regex:/@demo\.uninlar\.test$/i';
            $requiredLevel = $table === 'dosen' ? User::ROLE_DOSEN : User::ROLE_MAHASISWA;
            $rules['user_id'] = [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('level', $requiredLevel)->where('email', 'not like', '%'.self::DEMO_USER_DOMAIN)),
                Rule::unique($table, 'user_id')->ignore($record?->getKey()),
            ];
        }

        if (array_key_exists('program_studi_id', $rules)) {
            $required = str_contains($rules['program_studi_id'], 'required') ? 'required' : 'nullable';
            $rules['program_studi_id'] = [
                $required, 'integer',
                Rule::exists('program_studi', 'id')->where(fn ($query) => $query
                    ->where('kode', 'not like', '%-DEMO')
                    ->where(fn ($scope) => $scope->where('aktif', true)
                        ->when($record?->program_studi_id, fn ($nested, $id) => $nested->orWhere('id', $id)))),
            ];
        }

        if ($config['key'] === 'semester') {
            $rules['periode'] = [
                'required', 'integer', 'between:1,255',
                Rule::unique('semester', 'periode')->where(fn ($query) => $query->where('tahun_ajaran', $request->input('tahun_ajaran')))->ignore($record?->getKey()),
            ];

            if ($request->boolean('aktif')) {
                $rules['aktif'] = [
                    'sometimes', 'boolean',
                    Rule::unique('semester', 'aktif')->where(fn ($query) => $query->where('aktif', true))->ignore($record?->getKey()),
                ];
            }
        }

        $data = $request->validate($rules, [
            'email.not_regex' => 'Domain email tersebut khusus data demo dan tidak dapat digunakan untuk profil operasional.',
        ] + AdminAcademicData::messages(), collect($config['fields'])->mapWithKeys(fn ($definition, $field) => [$field => $definition['label']])->all());

        foreach (['krs_mulai_pada', 'krs_selesai_pada'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = str_replace('T', ' ', $data[$field]);
            }
        }

        if (array_key_exists('aktif', $config['rules'])) {
            $data['aktif'] = $request->boolean('aktif');
        }

        return $data;
    }

    private function operationalQuery(array $config): Builder
    {
        return app(AdminAcademicData::class)->masterQuery($config['key'])->with($config['with'] ?? []);
    }

    private function resourceConfig(): array
    {
        abort_unless(app(AdminAcademicData::class)->available(), 503, 'Data administrasi akademik belum tersedia.');
        $routeName = (string) request()->route()?->getName();
        $key = explode('.', str_replace('dashboard.admin-akademik.', '', $routeName))[0] ?? '';

        $configs = [
            'program-studi' => [
                'model' => ProgramStudi::class, 'label' => 'Program studi', 'route' => 'dashboard.admin-akademik.program-studi',
                'title' => 'Program Studi', 'search' => ['kode', 'nama', 'fakultas'], 'columns' => ['kode' => 'Kode', 'nama' => 'Nama', 'jenjang' => 'Jenjang', 'fakultas' => 'Fakultas', 'aktif' => 'Status'],
                'fields' => ['kode' => ['label' => 'Kode', 'type' => 'text'], 'nama' => ['label' => 'Nama program studi', 'type' => 'text'], 'jenjang' => ['label' => 'Jenjang', 'type' => 'text'], 'fakultas' => ['label' => 'Fakultas', 'type' => 'text', 'nullable' => true], 'aktif' => ['label' => 'Aktif', 'type' => 'checkbox']],
                'rules' => ['kode' => 'required|string|max:20|not_regex:/-DEMO$/i|unique:program_studi,kode', 'nama' => 'required|string|max:255', 'jenjang' => 'required|string|max:10', 'fakultas' => 'nullable|string|max:255', 'aktif' => 'sometimes|boolean'],
                'protected_relations' => ['mahasiswa' => 'Program studi masih memiliki profil mahasiswa.', 'mataKuliah' => 'Program studi masih memiliki mata kuliah.', 'dosen' => 'Program studi masih memiliki profil dosen.'],
            ],
            'semester' => [
                'model' => Semester::class, 'label' => 'Semester', 'route' => 'dashboard.admin-akademik.semester',
                'title' => 'Semester', 'search' => ['kode', 'tahun_ajaran'], 'columns' => ['kode' => 'Kode', 'tahun_ajaran' => 'Tahun ajaran', 'periode' => 'Periode', 'tanggal_mulai' => 'Mulai', 'tanggal_selesai' => 'Selesai', 'aktif' => 'Status'],
                'fields' => ['kode' => ['label' => 'Kode semester', 'type' => 'text'], 'tahun_ajaran' => ['label' => 'Tahun ajaran', 'type' => 'text'], 'periode' => ['label' => 'Periode', 'type' => 'number'], 'tanggal_mulai' => ['label' => 'Tanggal mulai', 'type' => 'date', 'nullable' => true], 'tanggal_selesai' => ['label' => 'Tanggal selesai', 'type' => 'date', 'nullable' => true], 'aktif' => ['label' => 'Aktif', 'type' => 'checkbox']],
                'rules' => ['kode' => 'required|string|max:20|not_regex:/^DEMO/i|unique:semester,kode', 'tahun_ajaran' => 'required|string|regex:/^\d{4}\/\d{4}$/', 'periode' => 'required|integer|between:1,255', 'tanggal_mulai' => 'nullable|date', 'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai', 'aktif' => 'sometimes|boolean'],
                'protected_relations' => ['kelasKuliah' => 'Semester masih digunakan oleh kelas kuliah.', 'krs' => 'Semester masih digunakan oleh KRS.', 'analisisRisiko' => 'Semester masih digunakan oleh analisis risiko.'],
            ],
            'mata-kuliah' => [
                'model' => MataKuliah::class, 'label' => 'Mata kuliah', 'route' => 'dashboard.admin-akademik.mata-kuliah', 'with' => ['programStudi'],
                'title' => 'Mata Kuliah', 'search' => ['kode', 'nama', 'programStudi.nama'], 'columns' => ['kode' => 'Kode', 'nama' => 'Nama', 'programStudi.nama' => 'Program studi', 'sks' => 'SKS', 'semester_rekomendasi' => 'Semester rekomendasi'],
                'fields' => ['program_studi_id' => ['label' => 'Program studi', 'type' => 'select'], 'kode' => ['label' => 'Kode mata kuliah', 'type' => 'text'], 'nama' => ['label' => 'Nama mata kuliah', 'type' => 'text'], 'sks' => ['label' => 'SKS', 'type' => 'number'], 'semester_rekomendasi' => ['label' => 'Semester rekomendasi', 'type' => 'number', 'nullable' => true]],
                'rules' => ['program_studi_id' => 'required|exists:program_studi,id', 'kode' => 'required|string|max:30|not_regex:/^DEMO/i|unique:mata_kuliah,kode', 'nama' => 'required|string|max:255', 'sks' => 'required|integer|between:1,255', 'semester_rekomendasi' => 'nullable|integer|between:1,255'],
                'protected_relations' => ['kelasKuliah' => 'Mata kuliah masih digunakan oleh kelas kuliah.'],
            ],
            'dosen' => [
                'model' => Dosen::class, 'label' => 'Profil dosen', 'route' => 'dashboard.admin-akademik.dosen', 'with' => ['programStudi', 'user'],
                'title' => 'Data Dosen', 'search' => ['nidn', 'nama', 'email', 'programStudi.nama'], 'columns' => ['nidn' => 'NIDN', 'nama' => 'Nama', 'email' => 'Email', 'programStudi.nama' => 'Program studi', 'user.username' => 'Username akun', 'aktif' => 'Status'],
                'fields' => ['user_id' => ['label' => 'Akun login dosen (opsional)', 'type' => 'select', 'nullable' => true], 'nidn' => ['label' => 'NIDN', 'type' => 'text', 'nullable' => true], 'nama' => ['label' => 'Nama dosen', 'type' => 'text'], 'email' => ['label' => 'Email', 'type' => 'email', 'nullable' => true], 'no_telepon' => ['label' => 'Nomor telepon', 'type' => 'text', 'nullable' => true], 'program_studi_id' => ['label' => 'Program studi', 'type' => 'select', 'nullable' => true], 'aktif' => ['label' => 'Aktif', 'type' => 'checkbox']],
                'rules' => ['user_id' => 'nullable|integer', 'nidn' => 'nullable|string|max:20|not_regex:/^DEMO/i|unique:dosen,nidn', 'nama' => 'required|string|max:255', 'email' => 'nullable|email|max:255|unique:dosen,email', 'no_telepon' => 'nullable|string|max:30', 'program_studi_id' => 'nullable|exists:program_studi,id', 'aktif' => 'sometimes|boolean'],
                'protected_relations' => ['kelasKuliah' => 'Profil dosen masih ditugaskan pada kelas kuliah.', 'kehadiranDicatat' => 'Profil dosen masih tercatat pada riwayat kehadiran.'],
            ],
            'mahasiswa' => [
                'model' => Mahasiswa::class, 'label' => 'Profil mahasiswa', 'route' => 'dashboard.admin-akademik.mahasiswa', 'with' => ['programStudi', 'user'],
                'title' => 'Data Mahasiswa', 'search' => ['nim', 'nama', 'email', 'programStudi.nama'], 'columns' => ['nim' => 'NIM', 'nama' => 'Nama', 'angkatan' => 'Angkatan', 'programStudi.nama' => 'Program studi', 'user.username' => 'Username akun', 'status' => 'Status'],
                'fields' => ['user_id' => ['label' => 'Akun login mahasiswa (opsional)', 'type' => 'select', 'nullable' => true], 'nim' => ['label' => 'NIM', 'type' => 'text'], 'nama' => ['label' => 'Nama mahasiswa', 'type' => 'text'], 'email' => ['label' => 'Email', 'type' => 'email', 'nullable' => true], 'no_telepon' => ['label' => 'Nomor telepon', 'type' => 'text', 'nullable' => true], 'angkatan' => ['label' => 'Tahun angkatan', 'type' => 'number'], 'program_studi_id' => ['label' => 'Program studi', 'type' => 'select'], 'status' => ['label' => 'Status', 'type' => 'select', 'choices' => ['aktif' => 'Aktif', 'cuti' => 'Cuti', 'lulus' => 'Lulus', 'keluar' => 'Keluar']]],
                'rules' => ['user_id' => 'nullable|integer', 'nim' => 'required|string|max:30|not_regex:/^DEMO/i|unique:mahasiswa,nim', 'nama' => 'required|string|max:255', 'email' => 'nullable|email|max:255|unique:mahasiswa,email', 'no_telepon' => 'nullable|string|max:30', 'angkatan' => 'required|integer|between:1900,2200', 'program_studi_id' => 'required|exists:program_studi,id', 'status' => 'required|in:aktif,cuti,lulus,keluar'],
                'protected_relations' => ['krs' => 'Profil mahasiswa masih memiliki catatan KRS.', 'analisisRisiko' => 'Profil mahasiswa masih memiliki catatan analisis risiko.'],
            ],
        ];

        abort_unless(isset($configs[$key]), 404);

        $config = $configs[$key];
        $config['key'] = $key;
        $config['show'] = true;

        if ($key === 'semester' && Schema::hasColumn('semester', 'krs_mulai_pada') && Schema::hasColumn('semester', 'krs_selesai_pada')) {
            $config['columns']['krs_mulai_pada'] = 'KRS dibuka';
            $config['columns']['krs_selesai_pada'] = 'KRS ditutup';
            $config['fields']['krs_mulai_pada'] = ['label' => 'Mulai periode KRS (WIB)', 'type' => 'datetime-local', 'nullable' => true];
            $config['fields']['krs_selesai_pada'] = ['label' => 'Akhir periode KRS (WIB)', 'type' => 'datetime-local', 'nullable' => true];
            $config['rules']['krs_mulai_pada'] = 'nullable|date_format:Y-m-d\\TH:i|required_with:krs_selesai_pada';
            $config['rules']['krs_selesai_pada'] = 'nullable|date_format:Y-m-d\\TH:i|required_with:krs_mulai_pada|after_or_equal:krs_mulai_pada';
        }

        return $config;
    }
}
