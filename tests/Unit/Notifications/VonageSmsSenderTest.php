<?php

declare(strict_types=1);

use App\Exceptions\VonageSmsException;
use App\Models\Tenant;
use App\Services\VonageSmsSender;
use Mockery\MockInterface;
use Vonage\Client as VonageClient;
use Vonage\SMS\Collection;
use Vonage\SMS\Message\SMS;

function senderWithClient(VonageClient $client): VonageSmsSender
{
    return new class($client) extends VonageSmsSender
    {
        public function __construct(private readonly VonageClient $client) {}

        protected function createClient(Tenant $tenant): VonageClient
        {
            return $this->client;
        }
    };
}

function vonageResponse(array $messages): Collection
{
    return new Collection(['messages' => $messages]);
}

function vonageClientMock(Collection $response): VonageClient&MockInterface
{
    $sms = Mockery::mock();
    $sms->shouldReceive('send')
        ->with(Mockery::on(fn ($arg) => $arg instanceof SMS))
        ->andReturn($response);

    $client = Mockery::mock(VonageClient::class);
    $client->shouldReceive('sms')->andReturn($sms);

    assert($client instanceof VonageClient);

    return $client;
}

function deliveredMessage(): array
{
    return ['to' => '393331234567', 'message-id' => '1', 'status' => '0', 'remaining-balance' => '10', 'message-price' => '0.05', 'network' => '22201'];
}

it('does not throw when vonage accepts the message', function () {
    $client = vonageClientMock(vonageResponse([deliveredMessage()]));

    $sender = senderWithClient($client);

    $tenant = Mockery::mock(Tenant::class);
    assert($tenant instanceof Tenant);

    expect(fn () => $sender->send($tenant, '+393331234567', 'Test message', 'PawDesk'))
        ->not->toThrow(VonageSmsException::class);
});

it('accepts a null sender id and falls back to the shared pool', function () {
    $client = vonageClientMock(vonageResponse([deliveredMessage()]));

    $sender = senderWithClient($client);

    $tenant = Mockery::mock(Tenant::class);
    assert($tenant instanceof Tenant);

    expect(fn () => $sender->send($tenant, '+393331234567', 'Test message', null))
        ->not->toThrow(VonageSmsException::class);
});

it('throws a VonageSmsException when vonage rejects the message', function () {
    $client = vonageClientMock(vonageResponse([
        array_merge(deliveredMessage(), ['status' => '1', 'error-text' => 'Throttled']),
    ]));

    $sender = senderWithClient($client);

    $tenant = Mockery::mock(Tenant::class);
    assert($tenant instanceof Tenant);

    $sender->send($tenant, '+393331234567', 'Test message', 'PawDesk');
})->throws(VonageSmsException::class, 'Throttled');
