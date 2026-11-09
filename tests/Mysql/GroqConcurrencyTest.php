<?php

namespace Tests\Mysql;

use App\Models\AnalisisRisiko;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\MysqlTestCase;
use Tests\Support\MysqlFixtures;

class GroqConcurrencyTest extends MysqlTestCase
{
    private function race(bool $sameStudent): void
    {
        $d = MysqlFixtures::make(2);
        MysqlFixtures::detail($d, 0);
        MysqlFixtures::detail($d, 1);
        $global = (array) DB::table('groq_demo_budgets')->where('id', 1)->first();
        $this->assertSame(0, $global['attempts']);
        $file = tempnam(sys_get_temp_dir(), 'uninalar-groq-race-');
        file_put_contents($file, json_encode(['students' => [$d['profiles'][0]->id, $d['profiles'][$sameStudent ? 0 : 1]->id],
            'users' => [$d['users'][0]->id, $d['users'][1]->id], 'semester' => $d['semester']->id]));
        $processes = [];
        try {
            foreach ([0, 1] as $slot) {
                $process = new Process([PHP_BINARY, base_path('tests/Support/mysql-groq-race-worker.php'), $file, (string) $slot], base_path());
                $process->setTimeout(25)->start();
                $processes[] = $process;
            }
            $deadline = microtime(true) + 20;
            while (! is_file($file.'.ready0') || ! is_file($file.'.ready1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Both independent MySQL processes must reach barrier');
                }
                usleep(10000);
            }
            file_put_contents($file.'.go', 'go');
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
                $results[] = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            }
            $this->assertNotSame($results[0]['connection'], $results[1]['connection']);
            $this->assertSame(1, array_sum(array_column($results, 'fake_requests')));
            $this->assertGreaterThanOrEqual(2, array_sum(array_column($results, 'locks')));
            $statuses = array_column($results, 'status');
            sort($statuses);
            $this->assertSame($sameStudent ? ['new', 'reused'] : ['denied', 'new'], $statuses);
            if ($sameStudent) {
                $this->assertSame($results[0]['analysis'], $results[1]['analysis']);
            }
            $this->assertSame(1, DB::table('groq_demo_budgets')->value('attempts'));
            $this->assertNull(DB::table('groq_demo_budgets')->value('lease_token'));
            $this->assertSame(1, AnalisisRisiko::whereIn('mahasiswa_id', [$d['profiles'][0]->id, $d['profiles'][1]->id])->count());
            echo "\nMYSQL_GROQ_RACE ".json_encode(['case' => $sameStudent ? 'double-submit' : 'two-users-global-limit', 'results' => $results])."\n";
        } finally {
            foreach ($processes as $process) {
                $process->stop();
            }
            // Restore only this disposable test's budget state; never an application reset API.
            DB::table('groq_demo_budgets')->where('id', 1)->update($global);
            DB::table('groq_demo_user_budgets')->whereIn('user_id', [$d['users'][0]->id, $d['users'][1]->id])->delete();
            foreach ([$file, $file.'.ready0', $file.'.ready1', $file.'.go'] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_two_independent_production_requests_reuse_one_analysis_on_double_submit(): void
    {
        $this->race(true);
    }

    public function test_two_independent_users_cannot_exceed_global_attempt_limit(): void
    {
        $this->race(false);
    }
}
