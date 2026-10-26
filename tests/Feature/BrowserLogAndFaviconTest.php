<?php

test('browser log endpoint responds 200 ok to get, post, and custom methods without csrf', function () {
    $responseGet = $this->get('/_boots/browser-log');
    $responseGet->assertOk();
    $responseGet->assertJson(['status' => 'ok']);

    $responsePost = $this->postJson('/_boots/browser-log', [
        'message' => 'Sample client error log',
        'level' => 'warn',
    ]);
    $responsePost->assertOk();
    $responsePost->assertJson(['status' => 'ok']);
});
