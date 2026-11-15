<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_read_or_change_any_account_resource(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Customer', 'email' => 'customer@example.com', 'phone' => '']);
        foreach (['users' => $user->id, 'customers' => $customer->id] as $resource => $id) {
            foreach (["/$resource", "/$resource/create", "/$resource/$id", "/$resource/$id/edit"] as $url) {
                $this->get($url)->assertRedirect('/login');
            }
            $this->post("/$resource", [])->assertRedirect('/login');
            $this->put("/$resource/$id", [])->assertRedirect('/login');
            $this->patch("/$resource/$id", [])->assertRedirect('/login');
            $this->delete("/$resource/$id")->assertRedirect('/login');
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_login_returns_to_requested_page_and_logout_protects_it_again(): void
    {
        $user = User::factory()->create();
        $this->get('/customers')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/customers');
        $this->assertAuthenticatedAs($user);
        $this->get('/customers')->assertOk();
        $this->get('/users')->assertOk();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->get('/customers')->assertRedirect('/login');
    }

    public function test_invalid_password_is_rejected_and_attempts_are_limited(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_registration_uses_existing_user_fields_and_hashes_the_password(): void
    {
        $this->get('/register')->assertOk();
        $this->post('/register', [
            'username' => 'student', 'full_name' => 'Student', 'email' => 'student@example.com',
            'password' => 'secure-password', 'password_confirmation' => 'secure-password',
        ])->assertRedirect('/dashboard');
        $user = User::firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertSame('Student', $user->full_name);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_registration_rejects_duplicate_addresses_and_unconfirmed_passwords(): void
    {
        $user = User::factory()->create();
        $this->post('/register', [
            'username' => $user->username, 'full_name' => 'Student', 'email' => $user->email,
            'password' => 'secure-password', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['username', 'email', 'password']);
        $this->assertGuest();
    }

    public function test_authenticated_users_can_view_and_delete_customer_and_user_records(): void
    {
        $admin = User::factory()->create();
        $other = User::factory()->create();
        $customer = Customer::create(['full_name' => 'Customer', 'email' => 'customer@example.com', 'phone' => '']);
        $this->actingAs($admin);
        foreach (['users' => $other->id, 'customers' => $customer->id] as $resource => $id) {
            foreach (["/$resource", "/$resource/create", "/$resource/$id", "/$resource/$id/edit"] as $url) {
                $this->get($url)->assertOk();
            }
            $this->delete("/$resource/$id")->assertRedirect("/$resource");
            $this->assertDatabaseMissing($resource, ['id' => $id]);
        }
        $this->delete('/users/'.$admin->id)->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_existing_passwordless_users_can_set_a_password_without_losing_their_data(): void
    {
        $this->seed();
        $user = User::where('username', 'arjmnz')->firstOrFail();
        $this->artisan('tfa8:set-password', ['username' => 'arjmnz'])
            ->expectsQuestion('Enter a new password (at least 8 characters)', 'my-private-password')
            ->expectsQuestion('Confirm the new password', 'my-private-password')->assertSuccessful();
        $this->assertTrue(Hash::check('my-private-password', $user->fresh()->password));
        $this->assertSame('arjimenez@feualabang.edu.ph', $user->fresh()->email);
        $this->seed();
        $this->assertTrue(Hash::check('my-private-password', $user->fresh()->password));
    }

    public function test_password_reset_notifications_and_token_reset_work(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $token = Password::createToken($user);
        $this->get('/reset-password/'.$token.'?email='.urlencode($user->email))->assertOk();
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'changed-password', 'password_confirmation' => 'changed-password',
        ])->assertRedirect('/login');
        $this->assertTrue(Hash::check('changed-password', $user->fresh()->password));
    }

    public function test_profile_displays_the_signed_in_user(): void
    {
        User::factory()->create(['full_name' => 'First User']);
        $current = User::factory()->create(['full_name' => 'Current User']);
        $this->actingAs($current)->get('/profile')->assertOk()->assertSeeText('Current User')->assertDontSeeText('First User');
    }
}
