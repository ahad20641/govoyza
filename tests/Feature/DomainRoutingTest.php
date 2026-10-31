<?php

namespace Tests\Feature;

use Tests\TestCase;

class DomainRoutingTest extends TestCase
{
    public function test_domain_routes_are_scoped_by_host_and_private_domains_send_noindex_header(): void
    {
        $this->get('http://'.config('govoyza.domains.main').'/')
            ->assertOk();

        $this->get('http://'.config('govoyza.domains.partner').'/')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex');

        $this->get('http://'.config('govoyza.domains.admin').'/')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex');
    }
}
