<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'username' => 'Username atau password tidak valid.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');

        return $this->allowedIntendedUrl($request, $intended)
            ? redirect()->to($intended)
            : redirect()->route('dashboard');
    }

    private function allowedIntendedUrl(Request $request, mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }
        $probe = Request::create(str_starts_with($url, '/') && ! str_starts_with($url, '//')
            ? $request->getSchemeAndHttpHost().$url : $url, 'GET');
        if ($probe->getSchemeAndHttpHost() !== $request->getSchemeAndHttpHost()) {
            return false;
        }
        try {
            // Matching inspects route metadata only; it does not run model binding.
            $target = Route::getRoutes()->match($probe);
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return false;
        }
        foreach ($target->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }
            if (explode(':', $middleware, 2)[0] === 'guest') {
                return false;
            }
            if (str_starts_with($middleware, 'level:')
                && ! in_array((int) $request->user()->level, array_map('intval', explode(',', substr($middleware, 6))), true)) {
                return false;
            }
        }

        return true;
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
