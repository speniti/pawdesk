<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;

test('/up health check responds 200', function () {
    $this->get('/up')->assertSuccessful();
});

test('/admin redirects to login when unauthenticated', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('/admin dashboard responds 200 when authenticated', function () {
    // Filament allows access without FilamentUser only in local env
    Config::set('app.env', 'local');

    $this->actingAs(User::factory()->create());

    $this->get('/admin')->assertSuccessful();
});
