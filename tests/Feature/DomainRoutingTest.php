<?php

namespace Tests\Feature;

use Tests\TestCase;

class DomainRoutingTest extends TestCase
{
    public function test_domain_routes_are_scoped_by_host_and_private_domains_send_noindex_header(): void
    {
        $this->get('http://'.config('govoyza.domains.main').'/')
            ->assertOk();

        // The partner and admin domains are Filament panels: guests go to their login pages.
        foreach (['partner', 'admin'] as $panel) {
            $host = 'http://'.config("govoyza.domains.{$panel}");

            $this->get($host.'/')
                ->assertRedirect(route("filament.{$panel}.auth.login"))
                ->assertHeader('X-Robots-Tag', 'noindex');
            $this->get($host.'/login')
                ->assertOk()
                ->assertHeader('X-Robots-Tag', 'noindex');
        }
    }
}
