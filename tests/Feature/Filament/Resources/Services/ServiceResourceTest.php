<?php

declare(strict_types=1);

it('service resource is registered in filament', function () {
    $resources = filament()->getResources();
    expect($resources)->toContain(App\Filament\Resources\Services\ServiceResource::class);
});
