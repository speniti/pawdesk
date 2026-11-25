<?php

declare(strict_types=1);

use Symfony\Component\Yaml\Yaml;

function productionComposeServices(): array
{
    $parsed = Yaml::parseFile(__DIR__.'/../../docker-compose.prod.yml');
    assert(is_array($parsed));

    /** @var array<string, mixed> $services */
    $services = $parsed['services'] ?? [];

    return $services;
}

test('the production compose stack forces production env on every service', function () {
    $services = productionComposeServices();

    expect(array_keys($services))->toContain('app', 'worker', 'scheduler');

    foreach ($services as $name => $service) {
        // environment: batte env_file:, quindi l'override esplicito ha la
        // precedenza anche se il .env (tab Environment di Dokploy) dovesse
        // portare i valori di sviluppo (#73). La stringa "0" e non il bool
        // YAML è voluta: (bool) "0" è false anche per un getenv() ingenuo.
        expect($service['environment'])
            ->APP_ENV->toBe('production')
            ->APP_DEBUG->toBe('0');
    }
});

test('every production service wires the production config guard', function () {
    $services = productionComposeServices();

    // Anti-drift: se la guardia venisse rimossa da qualunque servizio il
    // pin fallisce (healthcheck di app, command di worker e scheduler).
    expect(implode(' ', (array) $services['app']['healthcheck']['test']))
        ->toContain('app:check-production-config');

    foreach (['worker', 'scheduler'] as $name) {
        expect(implode(' ', (array) $services[$name]['command']))
            ->toContain('app:check-production-config')
            // exec mantiene i segnali sul processo artisan (shutdown grazioso).
            ->toContain('exec php artisan');
    }
});
