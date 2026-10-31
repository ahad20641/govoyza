<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;

class RegisterAgencyRequest extends FormRequest
{
    /** Business documents collected for admin review (spec §39). */
    public const DOCUMENTS = [
        'trade_license' => ['label' => 'Trade License', 'required' => true],
        'owner_id' => ['label' => 'Owner NID / Passport', 'required' => true],
        'tin_certificate' => ['label' => 'TIN / Tax Certificate', 'required' => false],
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
            'business_email' => strtolower(trim((string) $this->input('business_email'))),
        ]);
    }

    public function rules(): array
    {
        $file = File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(5 * 1024);

        $rules = [
            // Account owner
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 \-]{6,20}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
            // Business
            'agency_name' => ['required', 'string', 'max:150'],
            'trade_license_number' => ['required', 'string', 'max:100'],
            'business_email' => ['required', 'email:rfc', 'max:255'],
            'business_phone' => ['required', 'string', 'max:30', 'regex:/^\+?[0-9 \-]{6,20}$/'],
            'address' => ['required', 'string', 'max:500'],
            'district_id' => ['nullable', Rule::exists('districts', 'id')->where('is_active', true)],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'terms' => ['accepted'],
        ];

        foreach (self::DOCUMENTS as $key => $doc) {
            $rules["documents.$key"] = [$doc['required'] ? 'required' : 'nullable', $file];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return collect(self::DOCUMENTS)->mapWithKeys(fn ($d, $k) => ["documents.$k" => $d['label']])->all();
    }
}
