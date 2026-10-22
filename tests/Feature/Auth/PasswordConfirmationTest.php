<?php

use App\Models\User;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('profile.edit', absolute: false));
    $response->assertSessionHas('auth.password_confirmed_at');
    $response->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
    $response->assertSessionMissing('auth.password_confirmed_at');
});

test('password confirmation respects the intended destination', function () {
    $this->actingAs(User::factory()->create())
        ->withSession(['url.intended' => route('profile.orders')])
        ->post('/confirm-password', ['password' => 'password'])
        ->assertRedirect(route('profile.orders'));
});
