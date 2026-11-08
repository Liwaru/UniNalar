<?php

use App\Http\Controllers\AdminAcademicController;
use App\Http\Controllers\AdminKelasKuliahController;
use App\Models\User;
use App\Services\GradeEntryService;
use App\Services\GradeFinalizationService;
use App\Services\KrsDecisionService;
use App\Services\KrsSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

$app = require __DIR__.'/mysql-bootstrap.php';
$job = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$slot = (int) $argv[2];
$args = $job['workers'][$slot];
$connection = DB::connection();
$connectionId = $connection->selectOne('SELECT CONNECTION_ID() AS id')->id;
$locks = 0;
$held = false;
$connection->listen(function ($query) use (&$locks, &$held): void {
    if (str_contains(strtolower($query->sql), 'for update')) {
        $locks++;
        if (! $held) {
            $held = true;
            usleep(200000);
        }
    }
});
file_put_contents($argv[1].'.ready'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($argv[1].'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('QA race barrier timeout');
    }
    usleep(10000);
}
$start = microtime(true);
try {
    $actor = User::findOrFail($args['actor']);
    match ($args['action']) {
        'submit' => app(KrsSubmissionService::class)->submit($actor),
        'decide' => app(KrsDecisionService::class)->decide($args['id'], $actor, $args['decision'], null),
        'grade' => app(GradeEntryService::class)->save($actor, $args['id'], $args['values']),
        'finalize' => app(GradeFinalizationService::class)->finalize($actor, $args['id']),
        'delete' => app(AdminKelasKuliahController::class)->destroy($args['id']),
        'class-update' => app(AdminKelasKuliahController::class)->update(Request::create('/qa', 'PUT', $args['values']), $args['id']),
        'semester-store' => (function () use ($args, $actor, $app) {
            $request = Request::create('/qa-semester', 'POST', $args['values']);
            $route = new Route(['POST'], '/qa-semester', ['uses' => fn () => null]);
            $route->name('dashboard.admin-akademik.semester.store');
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $actor);
            $app->instance('request', $request);

            return app(AdminAcademicController::class)->store($request);
        })(),
    };
    $status = 'ok';
} catch (ValidationException $e) {
    $status = 'validation';
} catch (Throwable $e) {
    $status = get_class($e).':'.$e->getCode();
}
echo json_encode(['status' => $status, 'connection' => $connectionId, 'locks' => $locks, 'elapsed_ms' => (microtime(true) - $start) * 1000]).PHP_EOL;
