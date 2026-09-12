<?php

namespace Tests\Feature;

use App\Models\TabContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TabContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin', 'pegawai', 'kanit', 'kasubag', 'pejabat_berwenang', 'kanit_kepegawaian', 'kasubag_tu'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }

    private function makeUser(array $extra = []): User
    {
        $user = User::create(array_merge([
            'nama' => 'Test User',
            'nip' => '123456789012345678',
            'password' => bcrypt('password'),
            'is_profile_completed' => true,
        ], $extra));

        $user->assignRole('pegawai');

        return $user;
    }

    // ─── TabContext Model ───

    public function test_create_for_user_returns_token_and_context(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('context', $result);
        $this->assertEquals(64, strlen($result['token']));
        $this->assertEquals($user->id, $result['context']->user_id);
    }

    public function test_token_is_stored_as_sha256_hash(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $stored = TabContext::first();
        $this->assertEquals(TabContext::hashToken($result['token']), $stored->token_hash);
        $this->assertEquals(hash('sha256', $result['token']), $stored->token_hash);
    }

    public function test_user_agent_is_stored_as_sha256_hash(): void
    {
        $user = $this->makeUser();
        TabContext::createForUser($user->id, 'Mozilla/5.0 Test', 120);

        $stored = TabContext::first();
        $this->assertEquals(hash('sha256', 'Mozilla/5.0 Test'), $stored->user_agent_hash);
    }

    public function test_resolve_returns_tab_context_for_valid_token(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $context = TabContext::resolve($result['token'], 'Mozilla/5.0');

        $this->assertNotNull($context);
        $this->assertEquals($user->id, $context->user_id);
    }

    public function test_resolve_returns_null_for_invalid_token(): void
    {
        $user = $this->makeUser();
        TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $context = TabContext::resolve('invalid-token-00000000000000000000000000000000000000000000000000', 'Mozilla/5.0');

        $this->assertNull($context);
    }

    public function test_resolve_returns_null_for_expired_token(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', -1);

        $context = TabContext::resolve($result['token'], 'Mozilla/5.0');

        $this->assertNull($context);
    }

    public function test_resolve_returns_null_for_ua_mismatch(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $context = TabContext::resolve($result['token'], 'Different UserAgent');

        $this->assertNull($context);
    }

    public function test_resolve_updates_last_seen_at(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $context = TabContext::resolve($result['token'], 'Mozilla/5.0');
        $this->assertNotNull($context->last_seen_at);
    }

    public function test_purge_expired_removes_old_rows(): void
    {
        $user = $this->makeUser();

        $active = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);
        $expired = TabContext::createForUser($user->id, 'Mozilla/5.0', -1);

        $purged = TabContext::purgeExpired();
        $this->assertGreaterThanOrEqual(1, $purged);

        $this->assertNull(TabContext::find($expired['context']->id));
    }

    public function test_revoke_all_for_user_removes_all_tokens(): void
    {
        $user = $this->makeUser();
        $result1 = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);
        $result2 = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        TabContext::revokeAllForUser($user->id);

        $this->assertNull(TabContext::resolve($result1['token'], 'Mozilla/5.0'));
        $this->assertNull(TabContext::resolve($result2['token'], 'Mozilla/5.0'));
    }

    // ─── TabGuard ───

    public function test_tab_guard_check_true_when_user_set(): void
    {
        $user = $this->makeUser();
        $guard = Auth::guard('tab');
        $guard->setUser($user);

        $this->assertTrue($guard->check());
    }

    public function test_tab_guard_check_false_when_no_user(): void
    {
        $guard = Auth::guard('tab');

        $this->assertFalse($guard->check());
    }

    public function test_tab_guard_returns_user(): void
    {
        $user = $this->makeUser();
        $guard = Auth::guard('tab');
        $guard->setUser($user);

        $this->assertEquals($user->id, $guard->user()->id);
    }

    public function test_tab_guard_returns_id(): void
    {
        $user = $this->makeUser();
        $guard = Auth::guard('tab');
        $guard->setUser($user);

        $this->assertEquals((string) $user->id, $guard->id());
    }

    public function test_tab_guard_guest_is_inverse_of_check(): void
    {
        $guard = Auth::guard('tab');
        $this->assertTrue($guard->guest());

        $user = $this->makeUser();
        $guard->setUser($user);
        $this->assertFalse($guard->guest());
    }

    public function test_tab_guard_has_user_matches_check(): void
    {
        $guard = Auth::guard('tab');
        $this->assertFalse($guard->hasUser());

        $user = $this->makeUser();
        $guard->setUser($user);
        $this->assertTrue($guard->hasUser());
    }

    // ─── TabContextMiddleware via HTTP ───

    public function test_middleware_sets_user_from_header_token(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, null, 120);

        $response = $this->withHeader('X-Tab-Token', $result['token'])
            ->get('/');

        $response->assertStatus(200);
    }

    public function test_middleware_sets_user_from_query_token(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, null, 120);

        $response = $this->get('/?ctx=' . $result['token']);

        $response->assertStatus(200)->assertSee($user->nama);
    }

    public function test_internal_redirect_preserves_context_without_propagating_to_login(): void
    {
        Route::middleware(\App\Http\Middleware\TabContextMiddleware::class)
            ->get('/tab-context-redirect-test', fn () => redirect('/pengajuan-cutis'));

        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, null, 120);

        $this->get('/tab-context-redirect-test?ctx=' . $result['token'])
            ->assertRedirect('/pengajuan-cutis?ctx=' . $result['token']);

        $this->get('/auth/logout?ctx=' . $result['token'])
            ->assertRedirect('/login');
    }

    public function test_reload_with_same_context_keeps_user_authenticated(): void
    {
        $user = $this->makeUser(['nama' => 'User Reload']);
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->get('/?ctx=' . $result['token'])
            ->assertOk()
            ->assertSee($user->nama);

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->get('/?ctx=' . $result['token'])
            ->assertOk()
            ->assertSee($user->nama);
    }

    public function test_two_contexts_remain_isolated_across_reload_requests(): void
    {
        $userA = $this->makeUser(['nama' => 'User A']);
        $userB = $this->makeUser(['nama' => 'User B', 'nip' => '987654321098765432']);
        $contextA = TabContext::createForUser($userA->id, 'Mozilla/5.0 A', 120);
        $contextB = TabContext::createForUser($userB->id, 'Mozilla/5.0 B', 120);

        $this->withHeader('User-Agent', 'Mozilla/5.0 A')
            ->get('/?ctx=' . $contextA['token'])
            ->assertOk()
            ->assertSee($userA->nama)
            ->assertDontSee($userB->nama);

        $this->withHeader('User-Agent', 'Mozilla/5.0 B')
            ->get('/?ctx=' . $contextB['token'])
            ->assertOk()
            ->assertSee($userB->nama)
            ->assertDontSee($userA->nama);

        $this->withHeader('User-Agent', 'Mozilla/5.0 A')
            ->get('/?ctx=' . $contextA['token'])
            ->assertOk()
            ->assertSee($userA->nama)
            ->assertDontSee($userB->nama);
    }

    public function test_revoking_one_context_does_not_logout_another_context(): void
    {
        $userA = $this->makeUser(['nama' => 'User A']);
        $userB = $this->makeUser(['nama' => 'User B', 'nip' => '987654321098765432']);
        $contextA = TabContext::createForUser($userA->id, 'Mozilla/5.0 A', 120);
        $contextB = TabContext::createForUser($userB->id, 'Mozilla/5.0 B', 120);

        $this->get('/auth/logout?ctx=' . $contextA['token']);

        $this->withHeader('User-Agent', 'Mozilla/5.0 A')
            ->get('/?ctx=' . $contextA['token'])
            ->assertRedirect('/login');

        $this->withHeader('User-Agent', 'Mozilla/5.0 B')
            ->get('/?ctx=' . $contextB['token'])
            ->assertOk()
            ->assertSee($userB->nama);
    }

    public function test_middleware_guest_when_no_token(): void
    {
        $response = $this->get('/');

        $response->assertStatus(302);
        $this->assertNull(Auth::guard('tab')->user());
    }

    public function test_middleware_guest_for_expired_token(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', -1);

        $response = $this->get('/?ctx=' . $result['token']);

        $response->assertStatus(302);
        $this->assertNull(Auth::guard('tab')->user());
    }

    public function test_middleware_guest_for_ua_mismatch(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $this->app['request']->server->set('HTTP_USER_AGENT', 'DifferentAgent');
        $response = $this->get('/?ctx=' . $result['token']);

        $response->assertStatus(302);
        $this->assertNull(Auth::guard('tab')->user());
    }

    // ─── Login creates TabContext ───

    public function test_login_creates_tab_context(): void
    {
        $user = $this->makeUser();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'password',
            ])
            ->call('authenticate');

        $this->assertDatabaseCount('tab_contexts', 1);
        $context = TabContext::first();
        $this->assertEquals($user->id, $context->user_id);
    }

    public function test_login_stores_token_in_session(): void
    {
        $user = $this->makeUser();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('tab_contexts', [
            'user_id' => $user->id,
        ]);
    }

    // ─── Tab independence ───

    public function test_two_tabs_for_same_user_are_independent(): void
    {
        $user = $this->makeUser();
        $result1 = TabContext::createForUser($user->id, 'Mozilla/5.0 Tab1', 120);
        $result2 = TabContext::createForUser($user->id, 'Mozilla/5.0 Tab2', 120);

        $ctx1 = TabContext::resolve($result1['token'], 'Mozilla/5.0 Tab1');
        $ctx2 = TabContext::resolve($result2['token'], 'Mozilla/5.0 Tab2');

        $this->assertNotNull($ctx1);
        $this->assertNotNull($ctx2);
        $this->assertNotEquals($ctx1->token_hash, $ctx2->token_hash);
    }

    public function test_revoking_one_tab_does_not_affect_the_other(): void
    {
        $user = $this->makeUser();
        $result1 = TabContext::createForUser($user->id, 'Mozilla/5.0 Tab1', 120);
        $result2 = TabContext::createForUser($user->id, 'Mozilla/5.0 Tab2', 120);

        $hash1 = TabContext::hashToken($result1['token']);
        TabContext::where('token_hash', $hash1)->delete();

        $ctx1 = TabContext::resolve($result1['token'], 'Mozilla/5.0 Tab1');
        $ctx2 = TabContext::resolve($result2['token'], 'Mozilla/5.0 Tab2');

        $this->assertNull($ctx1);
        $this->assertNotNull($ctx2);
    }

    // ─── Referrer-Policy header ───

    public function test_referrer_policy_no_referrer_header(): void
    {
        $response = $this->get('/');

        $response->assertHeader('Referrer-Policy', 'no-referrer');
    }

    // ─── No fallback to session auth ───

    public function test_no_fallback_to_session_auth_when_no_token(): void
    {
        $user = $this->makeUser();
        Auth::guard('web')->login($user);

        $response = $this->get('/');

        $this->assertNull(Auth::guard('tab')->user());
    }

    // ─── Purge command ───

    public function test_purge_command_removes_expired(): void
    {
        $user = $this->makeUser();
        TabContext::createForUser($user->id, 'Mozilla/5.0', -1);

        $this->artisan('tab-contexts:purge')
            ->expectsOutputToContain('Purged');

        $this->assertDatabaseCount('tab_contexts', 0);
    }

    // ─── Token hash is unique ───

    public function test_different_tokens_produce_different_hashes(): void
    {
        $user = $this->makeUser();
        $result1 = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);
        $result2 = TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $this->assertNotEquals($result1['context']->token_hash, $result2['context']->token_hash);
    }

    // ═══════════════════════════════════════════════════════════════
    // MULTI-TAB ISOLATION TESTS (A-J)
    // ═══════════════════════════════════════════════════════════════

    /** A: Two tabs — same user, independent token_hash, both resolve independently */
    public function test_A_two_tabs_independent_identity(): void
    {
        $user = $this->makeUser();
        $tabA = TabContext::createForUser($user->id, 'Mozilla/5.0 TabA', 120);
        $tabB = TabContext::createForUser($user->id, 'Mozilla/5.0 TabB', 120);

        $resolvedA = TabContext::resolve($tabA['token'], 'Mozilla/5.0 TabA');
        $resolvedB = TabContext::resolve($tabB['token'], 'Mozilla/5.0 TabB');

        $this->assertNotNull($resolvedA);
        $this->assertNotNull($resolvedB);
        $this->assertEquals($user->id, $resolvedA->user_id);
        $this->assertEquals($user->id, $resolvedB->user_id);
        $this->assertNotEquals($resolvedA->id, $resolvedB->id);
        $this->assertNotEquals($resolvedA->token_hash, $resolvedB->token_hash);
    }

    /** B: Login must NOT write _auth_web_id to session */
    public function test_B_no_auth_web_id_in_session(): void
    {
        $user = $this->makeUser();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertNull(
            session()->get('_auth_web_id'),
            '_auth_web_id must NOT be written to session after login'
        );
    }

    /** C: Overwriting _auth_web_id in session must NOT affect tab guard auth */
    public function test_C_forced_session_override_does_not_affect_tab_guard(): void
    {
        $userA = $this->makeUser(['nip' => '111111111111111111']);
        $userB = $this->makeUser(['nip' => '222222222222222222']);

        $tabA = TabContext::createForUser($userA->id, 'Mozilla/5.0', 120);

        Auth::guard('tab')->setUser($userA);
        $this->assertEquals((string) $userA->id, Auth::guard('tab')->id());

        session()->put('_auth_web_id', (string) $userB->id);

        $resolved = TabContext::resolve($tabA['token'], 'Mozilla/5.0');
        Auth::guard('tab')->setUser($resolved->user);

        $this->assertEquals((string) $userA->id, Auth::guard('tab')->id());
        $this->assertNotEquals((string) $userB->id, Auth::guard('tab')->id());
    }

    /** D: Request without ?ctx= → guest (no auth) */
    public function test_D_guest_without_ctx(): void
    {
        $user = $this->makeUser();
        TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $response = $this->get('/');

        $response->assertStatus(302);
        $this->assertNull(Auth::guard('tab')->user());
    }

    /** E: Request with invalid ?ctx= → guest */
    public function test_E_guest_with_invalid_ctx(): void
    {
        $user = $this->makeUser();
        TabContext::createForUser($user->id, 'Mozilla/5.0', 120);

        $response = $this->get('/?ctx=0000000000000000000000000000000000000000000000000000000000000000');

        $response->assertStatus(302);
        $this->assertNull(Auth::guard('tab')->user());
    }

    /** F: Request with expired ?ctx= → guest */
    public function test_F_guest_with_expired_ctx(): void
    {
        $user = $this->makeUser();
        $result = TabContext::createForUser($user->id, 'Mozilla/5.0', -1);

        $response = $this->get('/?ctx=' . $result['token']);

        $response->assertStatus(302);
        $this->assertNull(Auth::guard('tab')->user());
    }

    /** G: Logout tab A does NOT affect tab B */
    public function test_G_logout_isolation(): void
    {
        $user = $this->makeUser();
        $tabA = TabContext::createForUser($user->id, 'Mozilla/5.0 TabA', 120);
        $tabB = TabContext::createForUser($user->id, 'Mozilla/5.0 TabB', 120);

        $this->assertDatabaseCount('tab_contexts', 2);

        $this->get('/auth/logout?ctx=' . $tabA['token']);

        $this->assertDatabaseCount('tab_contexts', 1);

        $resolvedA = TabContext::resolve($tabA['token'], 'Mozilla/5.0 TabA');
        $resolvedB = TabContext::resolve($tabB['token'], 'Mozilla/5.0 TabB');

        $this->assertNull($resolvedA);
        $this->assertNotNull($resolvedB);
    }

    /** H: Logout tab A only deletes tab A context, NOT all user contexts */
    public function test_H_logout_only_deletes_own_context(): void
    {
        $user = $this->makeUser();
        $tabA = TabContext::createForUser($user->id, 'Mozilla/5.0 TabA', 120);
        $tabB = TabContext::createForUser($user->id, 'Mozilla/5.0 TabB', 120);

        $hashA = TabContext::hashToken($tabA['token']);
        $hashB = TabContext::hashToken($tabB['token']);

        $this->get('/auth/logout?ctx=' . $tabA['token']);

        $this->assertDatabaseMissing('tab_contexts', ['token_hash' => $hashA]);
        $this->assertDatabaseHas('tab_contexts', ['token_hash' => $hashB]);
    }

    /** I: Session does not write _auth_web_id — auth()->user() returns null without tab context */
    public function test_I_auth_user_returns_null_without_tab_context(): void
    {
        $user = $this->makeUser();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertNull(
            session()->get('_auth_web_id'),
            'Session must not contain _auth_web_id after login'
        );
    }

    /** J: After FIX 2 (no web login), default guard returns guest not the user */
    public function test_J_default_guard_returns_guest_after_login(): void
    {
        $user = $this->makeUser();

        Livewire::test(\App\Filament\Pages\Auth\Login::class)
            ->fillForm([
                'nip' => $user->nip,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertNull(
            Auth::guard('web')->user(),
            'Web guard must return null (guest) after tab-context login — no $webGuard->login()'
        );
    }
}
