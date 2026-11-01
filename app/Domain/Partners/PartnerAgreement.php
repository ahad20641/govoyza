<?php

namespace App\Domain\Partners;

use Illuminate\Support\Str;

/** The partner agreement in force: resources/agreements/partner-agreement.md. */
class PartnerAgreement
{
    public function version(): string
    {
        return (string) config('govoyza.partner.agreement_version');
    }

    public function text(): string
    {
        return (string) file_get_contents(resource_path('agreements/partner-agreement.md'));
    }

    public function html(): string
    {
        return Str::markdown($this->text(), ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    /** Stored with each signature, so we can prove which text was signed. */
    public function sha256(): string
    {
        return hash('sha256', $this->text());
    }
}
