<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password';

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::findOrCreate('Admin', 'admin');

        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'role_id' => $role->id,
            'status' => '1',
            'password' => self::PASSWORD,
        ]);
    }

    public function test_login_is_not_limited_when_rate_limit_is_passive(): void
    {
        SecuritySetting::current();

        foreach (range(1, 4) as $attempt) {
            $this->attemptLogin('wrong-password');
        }

        $this->attemptLogin(self::PASSWORD)->assertRedirect(route('backend.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'admin');
    }

    public function test_login_is_blocked_after_three_failed_attempts_when_rate_limit_is_active(): void
    {
        SecuritySetting::current()->update(['rate_limit_status' => true]);

        foreach (range(1, SecuritySetting::MAX_LOGIN_ATTEMPTS) as $attempt) {
            $this->attemptLogin('wrong-password')->assertSessionHas('warning');
        }

        $this->assertLoginBlocked($this->attemptLogin(self::PASSWORD));
        $this->assertGuest('admin');
    }

    public function test_login_form_shows_remaining_lock_time_for_countdown(): void
    {
        SecuritySetting::current()->update(['rate_limit_status' => true]);

        $this->get(route('backend.login.form'))->assertSee('data-retry-after="0"', false);

        foreach (range(1, SecuritySetting::MAX_LOGIN_ATTEMPTS) as $attempt) {
            $this->attemptLogin('wrong-password');
        }

        $this->travel(20)->seconds();

        $this->get(route('backend.login.form'))
            ->assertSee('data-retry-after="' . (SecuritySetting::LOGIN_LOCK_SECONDS - 20) . '"', false)
            ->assertSee('disabled', false);
    }

    public function test_block_applies_to_ip_regardless_of_email(): void
    {
        SecuritySetting::current()->update(['rate_limit_status' => true]);

        foreach (range(1, SecuritySetting::MAX_LOGIN_ATTEMPTS) as $attempt) {
            $this->from(route('backend.login.form'))->post(route('backend.login'), [
                'email' => "unknown{$attempt}@example.com",
                'password' => 'wrong-password',
            ]);
        }

        $this->assertLoginBlocked($this->attemptLogin(self::PASSWORD));
        $this->assertGuest('admin');
    }

    public function test_rate_limit_lock_lasts_full_duration_after_last_failed_attempt(): void
    {
        SecuritySetting::current()->update(['rate_limit_status' => true]);

        $this->attemptLogin('wrong-password');
        $this->travel(SecuritySetting::LOGIN_LOCK_SECONDS - 10)->seconds();
        $this->attemptLogin('wrong-password');
        $this->attemptLogin('wrong-password');

        $this->travel(SecuritySetting::LOGIN_LOCK_SECONDS - 10)->seconds();
        $this->assertLoginBlocked($this->attemptLogin(self::PASSWORD));

        $this->travel(11)->seconds();
        $this->attemptLogin(self::PASSWORD)->assertRedirect(route('backend.dashboard'));
    }

    public function test_successful_login_resets_failed_attempts(): void
    {
        SecuritySetting::current()->update(['rate_limit_status' => true]);

        $this->attemptLogin('wrong-password');
        $this->attemptLogin('wrong-password');
        $this->attemptLogin(self::PASSWORD)->assertRedirect(route('backend.dashboard'));

        auth('admin')->logout();

        $this->attemptLogin('wrong-password')->assertSessionHas('warning');
    }

    public function test_recaptcha_widget_is_shown_only_when_active(): void
    {
        $this->get(route('backend.login.form'))->assertDontSee('g-recaptcha');

        $this->activateRecaptcha();

        $this->get(route('backend.login.form'))
            ->assertSee('g-recaptcha', false)
            ->assertSee('site-key', false)
            ->assertDontSee('secret-key', false);
    }

    public function test_login_requires_recaptcha_when_active(): void
    {
        $this->activateRecaptcha();
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->attemptLogin(self::PASSWORD)->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest('admin');
    }

    public function test_login_fails_when_recaptcha_verification_fails(): void
    {
        $this->activateRecaptcha();
        Http::fake(['*' => Http::response(['success' => false])]);

        $this->attemptLogin(self::PASSWORD, ['g-recaptcha-response' => 'token'])
            ->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest('admin');
    }

    public function test_login_succeeds_with_valid_recaptcha(): void
    {
        $this->activateRecaptcha();
        Http::fake(['*' => Http::response(['success' => true])]);

        $this->attemptLogin(self::PASSWORD, ['g-recaptcha-response' => 'token'])
            ->assertRedirect(route('backend.dashboard'));
        $this->assertAuthenticatedAs($this->admin, 'admin');

        Http::assertSent(fn ($request) => $request['secret'] === 'secret-key' && $request['response'] === 'token');
    }

    public function test_login_ignores_recaptcha_when_passive(): void
    {
        SecuritySetting::current();
        Http::fake();

        $this->attemptLogin(self::PASSWORD)->assertRedirect(route('backend.dashboard'));

        Http::assertNothingSent();
    }

    public function test_security_tab_is_hidden_from_non_developer(): void
    {
        $this->grantSettingsPermission();

        $this->actingAs($this->admin, 'admin')
            ->get(route('backend.profile.form'))
            ->assertOk()
            ->assertDontSee('kt_profile_security');
    }

    public function test_non_developer_cannot_update_security_settings(): void
    {
        $this->grantSettingsPermission();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('backend.profile.security'), $this->securityPayload())
            ->assertForbidden();
    }

    public function test_developer_can_update_security_settings(): void
    {
        $this->makeDeveloper();

        $this->actingAs($this->admin, 'admin')
            ->get(route('backend.profile.form'))
            ->assertOk()
            ->assertSee('kt_profile_security');

        $this->actingAs($this->admin, 'admin')
            ->patch(route('backend.profile.security'), $this->securityPayload())
            ->assertRedirect(route('backend.profile.form') . '#kt_profile_security')
            ->assertSessionHas('success');

        $security = SecuritySetting::current();

        $this->assertTrue($security->rate_limit_status);
        $this->assertTrue($security->recaptcha_status);
        $this->assertSame('site-key', $security->recaptcha_site_key);
        $this->assertSame('secret-key', $security->recaptcha_secret_key);
    }

    public function test_recaptcha_keys_are_stored_encrypted(): void
    {
        $this->activateRecaptcha();

        $stored = DB::table('security_settings')->first();

        $this->assertNotSame('site-key', $stored->recaptcha_site_key);
        $this->assertNotSame('secret-key', $stored->recaptcha_secret_key);
    }

    public function test_recaptcha_keys_are_required_when_activating_for_the_first_time(): void
    {
        $this->makeDeveloper();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('backend.profile.security'), $this->securityPayload([
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
            ]))
            ->assertSessionHasErrors(['recaptcha_site_key', 'recaptcha_secret_key']);

        $this->assertFalse(SecuritySetting::current()->recaptcha_status);
    }

    public function test_blank_keys_keep_stored_values(): void
    {
        $this->makeDeveloper();
        $this->activateRecaptcha();

        $this->actingAs($this->admin, 'admin')
            ->patch(route('backend.profile.security'), $this->securityPayload([
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
            ]))
            ->assertSessionHasNoErrors();

        $security = SecuritySetting::current();

        $this->assertSame('site-key', $security->recaptcha_site_key);
        $this->assertSame('secret-key', $security->recaptcha_secret_key);
    }

    private function assertLoginBlocked($response): void
    {
        $response->assertRedirect(route('backend.login.form'))
            ->assertSessionHas('warning', fn ($message) => $message !== trans('backend.messages.warning.login'));
        $this->assertGuest('admin');
    }

    private function attemptLogin(string $password, array $extra = [])
    {
        return $this->from(route('backend.login.form'))->post(route('backend.login'), [
            'email' => $this->admin->email,
            'password' => $password,
        ] + $extra);
    }

    private function activateRecaptcha(): void
    {
        SecuritySetting::current()->update([
            'recaptcha_status' => true,
            'recaptcha_site_key' => 'site-key',
            'recaptcha_secret_key' => 'secret-key',
        ]);
    }

    private function grantSettingsPermission(): void
    {
        $permission = Permission::findOrCreate('settings edit', 'admin');

        $this->admin->role->givePermissionTo($permission);
        $this->admin->assignRole($this->admin->role);
    }

    private function makeDeveloper(): void
    {
        $this->admin->update([
            'role_id' => Role::findOrCreate(Admin::DEVELOPER_ROLE, 'admin')->id,
        ]);
        $this->admin->load('role');
    }

    private function securityPayload(array $overrides = []): array
    {
        return $overrides + [
            'rate_limit_status' => '1',
            'recaptcha_status' => '1',
            'recaptcha_site_key' => 'site-key',
            'recaptcha_secret_key' => 'secret-key',
        ];
    }
}
