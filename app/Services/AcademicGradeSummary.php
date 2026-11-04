<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\Nilai;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AcademicGradeSummary
{
    /** @return array{ips: ?float, ips_semester: ?string, ipk: ?float, final_count: int} */
    public function forStudent(Mahasiswa $student): array
    {
        if (! Schema::hasTable('nilai') || ! Schema::hasColumn('nilai', 'final_at')) {
            return ['ips' => null, 'ips_semester' => null, 'ipk' => null, 'final_count' => 0];
        }

        $grades = Nilai::query()->whereNotNull('final_at')
            ->whereHas('krsDetail.krs', fn (Builder $krs) => $krs
                ->where('mahasiswa_id', $student->id)
                ->whereHas('semester', fn (Builder $semester) => $semester->where('kode', 'not like', 'DEMO%'))
                ->whereHas('mahasiswa', fn (Builder $profile) => $profile->where('nim', 'not like', 'DEMO%')
                    ->whereHas('programStudi', fn (Builder $program) => $program->where('kode', 'not like', '%-DEMO'))))
            ->whereHas('krsDetail.kelasKuliah.mataKuliah', fn (Builder $course) => $course->where('kode', 'not like', 'DEMO%')
                ->whereHas('programStudi', fn (Builder $program) => $program->where('kode', 'not like', '%-DEMO')))
            ->with(['krsDetail.krs.semester', 'krsDetail.kelasKuliah.mataKuliah'])
            ->orderByDesc('final_at')->orderByDesc('id')->get();

        $valid = $grades->filter(fn (Nilai $grade) => $this->points($grade) !== null
            && (int) ($grade->krsDetail?->kelasKuliah?->mataKuliah?->sks ?? 0) > 0)->values();
        $bySemester = $valid->filter(fn (Nilai $grade) => $grade->krsDetail?->krs?->semester !== null)
            ->groupBy(fn (Nilai $grade) => $grade->krsDetail->krs->semester_id)
            ->map(fn (Collection $semesterGrades) => [
                'semester' => $semesterGrades->first()->krsDetail->krs->semester,
                'ips' => $this->weightedAverage($semesterGrades),
            ])->sortBy(fn (array $row) => sprintf('%s-%03d', $row['semester']->tahun_ajaran, $row['semester']->periode));

        $latestAttemptPerCourse = $valid->unique(fn (Nilai $grade) => $grade->krsDetail->kelasKuliah->mata_kuliah_id)->values();
        $latestSemester = $bySemester->last();

        return [
            'ips' => $latestSemester['ips'] ?? null,
            'ips_semester' => $latestSemester['semester']->kode ?? null,
            'ipk' => $latestAttemptPerCourse->isNotEmpty() ? $this->weightedAverage($latestAttemptPerCourse) : null,
            'final_count' => $valid->count(),
        ];
    }

    private function weightedAverage(Collection $grades): ?float
    {
        $weightedPoints = 0.0;
        $totalCredits = 0;

        foreach ($grades as $grade) {
            $credits = (int) $grade->krsDetail->kelasKuliah->mataKuliah->sks;
            $weightedPoints += (float) $this->points($grade) * $credits;
            $totalCredits += $credits;
        }

        return $totalCredits > 0 ? round($weightedPoints / $totalCredits, 2) : null;
    }

    private function points(Nilai $grade): ?float
    {
        $letter = strtoupper(trim((string) $grade->nilai_huruf));
        $configured = config('academic.grade_points', []);
        if ($letter === '' || ! array_key_exists($letter, $configured)) {
            return null;
        }

        $points = (float) $configured[$letter];
        if ($grade->nilai_mutu !== null && abs((float) $grade->nilai_mutu - $points) > 0.01) {
            return null;
        }

        return $points;
    }
}
