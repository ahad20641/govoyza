<?php

namespace Tests\Feature;

use App\Console\Commands\SetStagingPassword;
use App\Http\Middleware\StagingGate;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StagingGateTest extends TestCase
{
    private const PASSWORD = 'stage-Pass-2026';

    private ?string $dir = null;

    protected function tearDown(): void
    {
        SetStagingPassword::$stdin = 'php://stdin';
        if ($this->dir) {
            File::deleteDirectory($this->dir);
        }

        parent::tearDown();
    }

    private function gateOn(): void
    {
        config([
            'govoyza.staging_gate.user' => 'govoyza',
            'govoyza.staging_gate.password_hash' => password_hash(self::PASSWORD, PASSWORD_BCRYPT),
        ]);
    }

    private function auth(string $user, string $password): array
    {
        return ['Authorization' => 'Basic '.base64_encode("{$user}:{$password}")];
    }

    public function test_gate_is_off_without_a_hash(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_gate_asks_for_a_password_on_every_host(): void
    {
        $this->gateOn();

        foreach (['/login', '/', '/no-such-page', 'http://partner.govoyza.test/', 'http://admin.govoyza.test/'] as $url) {
            $this->get($url)
                ->assertUnauthorized()
                ->assertHeader('WWW-Authenticate', 'Basic realm="GoVoyza staging", charset="UTF-8"')
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        }
    }

    public function test_health_check_stays_open(): void
    {
        $this->gateOn();

        $this->get('/up')->assertOk();
    }

    public function test_right_credentials_pass_and_wrong_ones_do_not(): void
    {
        $this->gateOn();

        $this->get('/login', $this->auth('govoyza', self::PASSWORD))->assertOk();
        $this->get('/login', $this->auth('govoyza', 'wrong'))->assertUnauthorized();
        $this->get('/login', $this->auth('someone', self::PASSWORD))->assertUnauthorized();
    }

    public function test_guessing_is_rate_limited(): void
    {
        $this->gateOn();

        foreach (range(1, StagingGate::MAX_FAILURES_PER_MINUTE) as $i) {
            $this->get('/login', $this->auth('govoyza', "guess{$i}"))->assertUnauthorized();
        }

        // Locked out even with the right password.
        $this->get('/login', $this->auth('govoyza', self::PASSWORD))->assertTooManyRequests();
    }

    public function test_command_writes_a_hash_to_env(): void
    {
        $this->dir = sys_get_temp_dir().'/gv-env-'.uniqid();
        mkdir($this->dir);
        file_put_contents($this->dir.'/.env', "APP_NAME=Test\nGOVOYZA_STAGING_PASSWORD_HASH='old'\n");
        $this->app->useEnvironmentPath($this->dir);

        $this->artisan('govoyza:staging-password')
            ->expectsQuestion('Password (hidden)', self::PASSWORD)
            ->expectsQuestion('Password again', self::PASSWORD)
            ->doesntExpectOutputToContain(self::PASSWORD)
            ->assertSuccessful();

        $raw = file_get_contents($this->dir.'/.env');
        $this->assertStringNotContainsString(self::PASSWORD, $raw);
        $this->assertSame(1, substr_count($raw, 'GOVOYZA_STAGING_PASSWORD_HASH='));
        $this->assertStringContainsString('APP_NAME=Test', $raw);

        // The hash survives .env parsing intact (no `$` interpolation).
        $env = Dotenv::parse($raw);
        $this->assertSame('govoyza', $env['GOVOYZA_STAGING_USER']);
        $this->assertTrue(password_verify(self::PASSWORD, $env['GOVOYZA_STAGING_PASSWORD_HASH']));
    }

    public function test_command_reads_stdin_and_rejects_short_passwords(): void
    {
        $this->dir = sys_get_temp_dir().'/gv-env-'.uniqid();
        mkdir($this->dir);
        $this->app->useEnvironmentPath($this->dir);

        file_put_contents($this->dir.'/in', "short1\nshort1\n");
        SetStagingPassword::$stdin = $this->dir.'/in';
        $this->artisan('govoyza:staging-password', ['--password-stdin' => true])->assertFailed();
        $this->assertFileDoesNotExist($this->dir.'/.env');

        file_put_contents($this->dir.'/in', self::PASSWORD."\n".self::PASSWORD."\n");
        $this->artisan('govoyza:staging-password', ['--password-stdin' => true, '--user' => 'team'])->assertSuccessful();
        $this->assertSame('team', Dotenv::parse(file_get_contents($this->dir.'/.env'))['GOVOYZA_STAGING_USER']);
    }

    public function test_command_refuses_in_production(): void
    {
        $this->app['env'] = 'production';

        $this->artisan('govoyza:staging-password')->assertFailed();
    }
}
