<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('account deletion is not offered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response->assertStatus(405);
    $this->assertAuthenticatedAs($user);
    $this->assertNotNull($user->fresh());
});

test('guests cannot view or update profiles', function () {
    $this->get('/profile')->assertRedirect(route('login'));
    $this->patch('/profile', ['name' => 'Guest', 'email' => 'guest@example.com'])
        ->assertRedirect(route('login'));
});

test('profile form supports multipart uploads through PATCH method spoofing', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')
        ->assertSee('name="_method" value="PATCH"', false);

    $this->from('/profile')->post('/profile', [
        '_method' => 'PATCH',
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    Storage::disk('public')->assertExists($user->fresh()->avatar);
});
