<?php

test('web requests return strict security headers', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-XSS-Protection', '1; mode=block');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('prohibited php-cgi argument injection queries are rejected with 400 Bad Request', function () {
    // Attempting CGI argument injection
    $response = $this->get('/login?-d+allow_url_include=1+-d+auto_prepend_file=php://input');

    $response->assertStatus(400);
});
