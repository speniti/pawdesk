<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('/up health check responds 200', function () {
    $this->get('/up')->assertSuccessful();
});

test('/admin redirects to login when unauthenticated', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('/admin dashboard responds 200 when authenticated with tenant', function () {
    Config::set('app.env', 'local');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->tenants()->attach($tenant);

    $this->actingAs($user);

    $this->get("/admin/{$tenant->slug}")->assertSuccessful();
});
