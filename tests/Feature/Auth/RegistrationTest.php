<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'nim' => '24104999',
        'prodi' => 'Sistem Informasi',
        'no_hp' => '081234567890',
        'alamat' => 'Bandung',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.dashboard', absolute: false));

    $this->assertDatabaseHas('members', [
        'nim' => '24104999',
        'nama' => 'Test User',
    ]);
});
