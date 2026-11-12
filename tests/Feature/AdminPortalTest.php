<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_admin_portal_renders_and_is_not_linked_from_public_pages(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Administrator Portal');

        foreach (['/', '/login'] as $publicPage) {
            $this->get($publicPage)->assertOk()->assertDontSee('admin/login');
        }
    }

    public function test_admin_can_sign_in_at_the_admin_portal(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_portal_returns_to_the_page_the_admin_asked_for(): void
    {
        $admin = $this->admin();

        $this->get('/admin/accounts')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/admin/accounts');
    }

    public function test_students_and_faculty_cannot_sign_in_at_the_admin_portal(): void
    {
        foreach ([User::ROLE_STUDENT, User::ROLE_FACULTY] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
                ->assertSessionHasErrors(['email' => trans('auth.failed')]);
            $this->assertGuest();
        }
    }

    public function test_wrong_password_at_the_admin_portal_is_a_generic_failure(): void
    {
        $admin = $this->admin();

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);
        $this->assertGuest();
    }

    public function test_admins_are_sent_to_the_admin_portal_from_the_regular_login(): void
    {
        $admin = $this->admin();

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => LoginRequest::ADMIN_ONLY_MESSAGE]);
        $this->assertGuest();
    }

    public function test_students_and_faculty_still_sign_in_at_the_regular_login(): void
    {
        foreach ([User::ROLE_STUDENT, User::ROLE_FACULTY] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->post('/login', ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('dashboard', absolute: false));
            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_guests_are_sent_to_the_login_that_matches_the_area_they_opened(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/admin/login');
        $this->get('/admin/accounts')->assertRedirect('/admin/login');
        $this->get('/student/dashboard')->assertRedirect('/login');
        $this->get('/faculty/dashboard')->assertRedirect('/login');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logging_out_returns_admins_to_the_admin_portal_and_everyone_else_home(): void
    {
        $this->actingAs($this->admin())->post('/logout')->assertRedirect('/admin/login');
        $this->assertGuest();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]))->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_a_deactivated_admin_session_returns_to_the_admin_portal(): void
    {
        $admin = $this->admin();
        $admin->forceFill(['deactivated_at' => now()])->save();

        $this->actingAs($admin->fresh())->get('/admin/dashboard')
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors(['email' => EnsureAccountIsActive::MESSAGE]);
        $this->assertGuest();
    }

    public function test_admin_portal_attempts_are_throttled(): void
    {
        $admin = $this->admin();
        RateLimiter::clear($admin->email.'|127.0.0.1');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => $admin->email, 'password' => 'incorrect'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}