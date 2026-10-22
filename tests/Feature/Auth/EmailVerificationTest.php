<?php

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Route;

test('email verification is not required to use the account', function () {
    $user = User::factory()->unverified()->create();
    expect($user)->not->toBeInstanceOf(MustVerifyEmail::class);
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    $this->get(route('profile.orders'))->assertOk();
    expect($user->fresh()->email_verified_at)->toBeNull();
});

test('unused Breeze email verification endpoints are not exposed', function () {
    expect(Route::has('verification.notice'))->toBeFalse()
        ->and(Route::has('verification.verify'))->toBeFalse()
        ->and(Route::has('verification.send'))->toBeFalse();
    $this->actingAs(User::factory()->unverified()->create());
    $this->get('/verify-email')->assertNotFound();
    $this->get('/verify-email/1/hash')->assertNotFound();
    $this->post('/email/verification-notification')->assertNotFound();
});
