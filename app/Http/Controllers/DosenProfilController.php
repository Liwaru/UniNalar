<?php

namespace App\Http\Controllers;

use App\Services\LecturerAcademicData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DosenProfilController extends Controller
{
    public function __construct(private readonly LecturerAcademicData $data) {}

    public function show(Request $request): View
    {
        $available = $this->data->available();
        $lecturer = $this->data->profile($request->user())?->load('programStudi');

        return view('dashboard.dosen.profil', compact('available', 'lecturer'));
    }

    public function update(Request $request): RedirectResponse
    {
        $lecturer = $this->data->profile($request->user());
        abort_unless($lecturer, 404);
        $values = $request->validate([
            'email' => ['nullable', 'email', 'max:255', 'not_regex:/@demo\.uninlar\.test$/i', Rule::unique('dosen', 'email')->ignore($lecturer->id)],
            'no_telepon' => ['nullable', 'string', 'max:30'],
        ]);
        $lecturer->fill($values)->save();

        return back()->with('status', 'Informasi kontak dosen berhasil diperbarui.');
    }
}
