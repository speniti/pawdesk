<?php

declare(strict_types=1);

use App\Exceptions\VonageSmsException;
use App\Models\Tenant;
use App\Services\VonageSmsSender;
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

function vonageClientMock(Collection $response): VonageClient
{
    $sms = Mockery::mock();
    $sms->shouldReceive('send')
        ->with(Mockery::on(fn ($arg) => $arg instanceof SMS))
        ->andReturn($response);

    $client = Mockery::mock(VonageClient::class);
    $client->shouldReceive('sms')->andReturn($sms);

    return $client;
}

function deliveredMessage(): array
{
    return ['to' => '393331234567', 'message-id' => '1', 'status' => '0', 'remaining-balance' => '10', 'message-price' => '0.05', 'network' => '22201'];
}

it('does not throw when vonage accepts the message', function () {
    $client = vonageClientMock(vonageResponse([deliveredMessage()]));

    $sender = senderWithClient($client);

    $sender->send(Mockery::mock(Tenant::class), '+393331234567', 'Test message', 'PawDesk');

    expect(true)->toBeTrue();
});

it('throws a VonageSmsException when vonage rejects the message', function () {
    $client = vonageClientMock(vonageResponse([
        array_merge(deliveredMessage(), ['status' => '1', 'error-text' => 'Throttled']),
    ]));

    $sender = senderWithClient($client);

    $sender->send(Mockery::mock(Tenant::class), '+393331234567', 'Test message', 'PawDesk');
})->throws(VonageSmsException::class, 'Throttled');
