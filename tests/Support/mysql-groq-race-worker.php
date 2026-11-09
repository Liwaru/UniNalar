<?php

use App\Models\Mahasiswa;
use App\Services\GroqAcademicAnalysis;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

$app = require __DIR__.'/mysql-bootstrap.php';
$job = json_decode(file_get_contents($argv[1]), true, flags: JSON_THROW_ON_ERROR);
$slot = (int) $argv[2];
$student = Mahasiswa::findOrFail($job['students'][$slot]);
$connection = DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
Http::preventStrayRequests();
Http::fake(['api.groq.com/*' => function () {
    usleep(400000);

    return Http::response(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => json_encode([
        'penjelasan' => 'Hasil race sintetis MySQL.', 'rekomendasi' => ['Tinjau materi.'],
    ])]]]]);
}]);
config(['app.debug' => false, 'app.url' => 'https://demo.example.test', 'session.secure' => true,
    'session.http_only' => true, 'session.driver' => 'file', 'cache.default' => 'database',
    'services.groq.key' => 'synthetic-qa-only', 'services.groq.live_enabled' => true,
    'services.groq.demo.enabled' => true, 'services.groq.demo.mode' => 'production-demo',
    'services.groq.demo.environments' => ['production'],
    'services.groq.demo.database' => config('database.connections.mysql.database'),
    'services.groq.demo.user_ids' => array_map('strval', $job['users']),
    'services.groq.demo.global_limit' => 1, 'services.groq.demo.user_limit' => 1]);
$locks = 0;
DB::listen(function ($query) use (&$locks) {
    if (str_contains(strtolower($query->sql), 'for update')) {
        $locks++;
    }
});
file_put_contents($argv[1].'.ready'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($argv[1].'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('QA Groq race barrier timeout');
    }
    usleep(10000);
}
$app['env'] = 'production';
$id = null;
try {
    $analysis = app(GroqAcademicAnalysis::class)->generate($student, (int) $job['semester']);
    $id = $analysis->id;
    $status = $analysis->wasRecentlyCreated ? 'new' : 'reused';
} catch (DomainException) {
    $status = 'denied';
}
echo json_encode(['connection' => $connection, 'status' => $status, 'analysis' => $id,
    'fake_requests' => Http::recorded()->count(), 'locks' => $locks], JSON_THROW_ON_ERROR);
