<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FinalSystemSecurityTest extends TestCase
{
    public function test_private_local_files_require_signed_capabilities_for_read_and_write(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('qa-private.txt', 'PRIVATE-QA-CONTENT');
        $this->get('/storage/qa-private.txt')->assertForbidden()->assertDontSee('PRIVATE-QA-CONTENT');
        $this->call('PUT', '/storage/qa-untrusted.txt?upload=true', [], [], [], [], 'UNTRUSTED-QA-CONTENT')->assertForbidden();
        Storage::disk('local')->assertMissing('qa-untrusted.txt');
    }

    public function test_every_academic_endpoint_requires_its_role_and_owner_is_read_only(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            foreach (['mahasiswa' => 1, 'dosen' => 2, 'admin-akademik' => 3, 'owner' => 4] as $role => $level) {
                if ($route->uri() !== 'dashboard/'.$role && ! str_starts_with($route->uri(), 'dashboard/'.$role.'/')) {
                    continue;
                }
                $this->assertContains('auth', $route->gatherMiddleware(), $route->uri());
                $this->assertContains('level:'.$level, $route->gatherMiddleware(), $route->uri());
                if ($role === 'owner') {
                    $this->assertSame(['GET', 'HEAD'], $route->methods(), $route->uri());
                }
                $checked++;
            }
        }
        $this->assertSame(85, $checked);
    }
}
