<?php

use App\Models\Member;
use App\Models\User;
use App\Support\Shelfy;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertStatus(200)
        ->assertSee('Login SHELFY')
        ->assertDontSee('Akun admin awal')
        ->assertDontSee('admin@gmail.com')
        ->assertDontSee('admin123');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('students are redirected to student dashboard after login', function () {
    $member = Member::query()->create([
        'nama' => 'Mahasiswa Test',
        'nim' => '24104888',
        'prodi' => 'Sistem Informasi',
        'email' => 'student@example.com',
        'status' => 'aktif',
    ]);
    $user = User::factory()->create([
        'name' => 'Mahasiswa Test',
        'nama' => 'Mahasiswa Test',
        'email' => 'student@example.com',
        'role' => 'mahasiswa',
        'nim' => '24104888',
        'member_id' => Shelfy::id($member),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
});
