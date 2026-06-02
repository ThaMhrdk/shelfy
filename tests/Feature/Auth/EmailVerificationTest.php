<?php

test('email verification route remains available during migration', function () {
    $response = $this->get('/verify-email');

    $response->assertRedirect(route('login'));
});
