<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $this->get('/login');
    $sessionId = session()->getId();
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($sessionId);
    $response->assertRedirect(route('profile.edit', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->withSession(['private_marker' => 'value']);
    $token = session()->token();
    $response = $this->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
    $response->assertSessionMissing('private_marker');
    expect(session()->token())->not->toBe($token);
});

test('login respects an intended authenticated destination', function () {
    $user = User::factory()->create();
    $this->get(route('profile.orders'))->assertRedirect(route('login'));
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('profile.orders'));
});

test('authenticated users visiting guest screens are redirected to their profile', function () {
    $this->actingAs(User::factory()->create());
    $this->get('/login')->assertRedirect(route('profile.edit'));
    $this->get('/register')->assertRedirect(route('profile.edit'));
});
