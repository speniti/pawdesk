<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('/admin redirects to login when unauthenticated', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('/admin dashboard responds 200 when authenticated with tenant', function () {
    Config::set('app.env', 'local');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->tenants()->attach($tenant);

    $this->actingAs($user);

    $this->get("/admin/$tenant->slug")->assertSuccessful();
});
