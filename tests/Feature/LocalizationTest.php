<?php

use App\Models\User;

test('language preference sets the document language', function () {
    $this->withUnencryptedCookie('locale', 'zh_CN')->get('/login')->assertOk()->assertSee('lang="zh-CN"', false);
    $this->withUnencryptedCookie('locale', 'en')->get('/login')->assertOk()->assertSee('lang="en"', false);
});

test('unsupported language preferences fall back to English', function () {
    $this->withUnencryptedCookie('locale', '../../other')->get('/login')->assertOk()->assertSee('lang="en"', false);
});

test('Chinese login validation and authentication errors are localized', function () {
    $this->withUnencryptedCookie('locale', 'zh_CN')->post(route('login.store'), [])
        ->assertSessionHasErrors(['username' => '用户名不能为空。', 'password' => '密码不能为空。']);
    $user = User::factory()->create();
    $this->post(route('login.store'), ['username' => $user->username, 'password' => 'incorrect'])
        ->assertSessionHasErrors(['username' => '用户名或密码不正确。']);
    $this->assertGuest();
});

test('language preference does not grant dashboard access', function () {
    $this->withUnencryptedCookie('locale', 'zh_CN')->actingAs(User::factory()->create())
        ->get('/dashboard')->assertForbidden();
});
