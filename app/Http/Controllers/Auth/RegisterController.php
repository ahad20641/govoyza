<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterAgencyRequest;
use App\Http\Requests\Auth\RegisterClientRequest;
use App\Models\District;
use App\Models\Nationality;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function choose(): View
    {
        return view('auth.register');
    }

    public function createClient(): View
    {
        return view('auth.register-client', [
            'nationalities' => Nationality::active()->with('country')->orderBy('sort_order')->orderBy('demonym')->get(),
        ]);
    }

    public function storeClient(RegisterClientRequest $request): RedirectResponse
    {
        $user = $this->registration->registerClient($request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }

    public function createAgency(): View
    {
        return view('auth.register-agency', [
            'districts' => District::active()->orderBy('sort_order')->orderBy('name')->get(),
            'documents' => RegisterAgencyRequest::DOCUMENTS,
        ]);
    }

    public function storeAgency(RegisterAgencyRequest $request): RedirectResponse
    {
        $user = $this->registration->registerAgency(
            $request->safe()->except('documents'),
            $request->file('documents', []),
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
    }
}
