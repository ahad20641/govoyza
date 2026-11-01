<?php

namespace Tests\Feature;

use App\Console\Commands\CreateAdmin;
use App\Console\Commands\ResetPassword;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class StaffPasswordCommandsTest extends TestCase
{
    private const STRONG = 'Str0ng!Passw0rd';

    private ?string $stdinFile = null;

    protected function tearDown(): void
    {
        CreateAdmin::$stdin = ResetPassword::$stdin = 'php://stdin';
        if ($this->stdinFile) {
            @unlink($this->stdinFile);
        }

        parent::tearDown();
    }

    /** Points --password-stdin of both commands at a file with these lines. */
    private function stdin(string ...$lines): void
    {
        $this->stdinFile = tempnam(sys_get_temp_dir(), 'pw');
        file_put_contents($this->stdinFile, implode("\n", $lines)."\n");
        CreateAdmin::$stdin = ResetPassword::$stdin = $this->stdinFile;
    }

    private function createAdmin(string $email = 'ops@govoyza.test'): PendingCommand
    {
        return $this->artisan('govoyza:create-admin', ['--name' => 'Ops', '--email' => $email]);
    }

    public function test_password_prompts_are_hidden_with_no_visible_fallback(): void
    {
        $question = CreateAdmin::hiddenQuestion('Password (hidden)');

        $this->assertTrue($question->isHidden());
        $this->assertFalse($question->isHiddenFallback());
        $this->assertFalse(ResetPassword::hiddenQuestion('x')->isHiddenFallback());
    }

    public function test_create_admin_asks_for_the_password_twice(): void
    {
        $this->createAdmin()
            ->expectsQuestion('Password (hidden)', self::STRONG)
            ->expectsQuestion('Password again', self::STRONG)
            ->doesntExpectOutputToContain(self::STRONG)
            ->assertSuccessful();

        $admin = User::firstWhere('email', 'ops@govoyza.test');
        $this->assertTrue($admin->isOwner());
        $this->assertTrue(Hash::check(self::STRONG, $admin->password));
    }

    public function test_create_admin_rejects_mismatched_or_weak_passwords(): void
    {
        $this->createAdmin()
            ->expectsQuestion('Password (hidden)', self::STRONG)
            ->expectsQuestion('Password again', self::STRONG.'x')
            ->expectsOutputToContain('do not match')
            ->assertFailed();

        $this->createAdmin()
            ->expectsQuestion('Password (hidden)', 'weak')
            ->expectsQuestion('Password again', 'weak')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_create_admin_has_no_password_option(): void
    {
        $this->assertFalse(app(CreateAdmin::class)->getDefinition()->hasOption('password'));
    }

    public function test_create_admin_reads_two_lines_from_stdin(): void
    {
        $this->stdin(self::STRONG, self::STRONG);
        $this->artisan('govoyza:create-admin', ['--name' => 'Ops', '--email' => 'ops@govoyza.test', '--password-stdin' => true])
            ->assertSuccessful();
        $this->assertTrue(Hash::check(self::STRONG, User::firstWhere('email', 'ops@govoyza.test')->password));

        $this->stdin(self::STRONG, 'different');
        $this->artisan('govoyza:create-admin', ['--name' => 'Fin', '--email' => 'fin@govoyza.test', '--password-stdin' => true])
            ->assertFailed();
        $this->assertNull(User::firstWhere('email', 'fin@govoyza.test'));
    }

    public function test_reset_password_changes_it_and_signs_out_everywhere(): void
    {
        $owner = User::factory()->owner()->create(['remember_token' => 'old-token']);
        DB::table('sessions')->insert([
            'id' => 'abc', 'user_id' => $owner->id, 'payload' => '', 'last_activity' => time(),
        ]);

        $this->artisan('govoyza:reset-password', ['email' => strtoupper($owner->email)])
            ->expectsQuestion('Password (hidden)', 'N3w!Passw0rd99')
            ->expectsQuestion('Password again', 'N3w!Passw0rd99')
            ->doesntExpectOutputToContain('N3w!Passw0rd99')
            ->assertSuccessful();

        $owner->refresh();
        $this->assertTrue(Hash::check('N3w!Passw0rd99', $owner->password));
        $this->assertNotSame('old-token', $owner->remember_token);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $owner->id)->count());
        $this->assertTrue(AuditLog::where('action', 'auth.password_reset_via_cli')->exists());
    }

    public function test_reset_password_keeps_the_old_one_on_bad_input(): void
    {
        $owner = User::factory()->owner()->create();

        // Mismatch.
        $this->artisan('govoyza:reset-password', ['email' => $owner->email])
            ->expectsQuestion('Password (hidden)', 'N3w!Passw0rd99')
            ->expectsQuestion('Password again', 'N3w!Passw0rd98')
            ->assertFailed();

        // Staff need the 12-character staff rule; this passes the customer rule only.
        $this->artisan('govoyza:reset-password', ['email' => $owner->email])
            ->expectsQuestion('Password (hidden)', 'Secret123pass')
            ->expectsQuestion('Password again', 'Secret123pass')
            ->assertFailed();

        $this->assertTrue(Hash::check('password', $owner->fresh()->password));

        $this->artisan('govoyza:reset-password', ['email' => 'nobody@govoyza.test'])->assertFailed();
    }

    public function test_reset_password_uses_the_customer_rule_for_customers_and_stdin(): void
    {
        $customer = User::factory()->create();

        $this->stdin('Secret123pass', 'Secret123pass');
        $this->artisan('govoyza:reset-password', ['email' => $customer->email, '--password-stdin' => true])
            ->assertSuccessful();

        $this->assertTrue(Hash::check('Secret123pass', $customer->fresh()->password));
    }

    public function test_send_test_email(): void
    {
        Mail::fake();

        $this->artisan('govoyza:send-test-email', ['to' => 'owner@govoyza.test'])->assertSuccessful();
        $this->artisan('govoyza:send-test-email', ['to' => 'not-an-email'])->assertFailed();
    }
}
