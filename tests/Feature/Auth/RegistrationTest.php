<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Notification::fake();
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $user = User::where('email', 'test@example.com')->sole();
    $this->assertAuthenticatedAs($user);
    expect(Hash::check('password', $user->password))->toBeTrue();
    Notification::assertNotSentTo($user, VerifyEmail::class);
    $response->assertRedirect(route('profile.edit', absolute: false));
});
