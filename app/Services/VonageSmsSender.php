<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\VonageSmsException;
use App\Models\Tenant;
use Vonage\Client as VonageClient;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Collection;
use Vonage\SMS\Message\SMS;

class VonageSmsSender
{
    /**
     * Send an SMS using the tenant's Vonage credentials.
     *
     * @param  string|null  $from  Sender ID or Vonage number; null uses the Vonage shared sender.
     *
     * @throws VonageSmsException When Vonage rejects the message (per-message status != 0).
     */
    public function send(Tenant $tenant, string $to, string $content, ?string $from = null): void
    {
        $client = $this->createClient($tenant);

        /** @var Collection $collection */
        $collection = $client->sms()->send(new SMS($to, $from ?? '', $content));

        /** @var array<int, array> $rawMessages */
        $rawMessages = $collection->getAllMessagesRaw()['messages'] ?? [];

        foreach ($collection as $message) {
            if ($message->getStatus() !== 0) {
                $errorText = collect($rawMessages)
                    ->map(fn (array $raw) => $raw['error-text'] ?? null)
                    ->filter()
                    ->implode('; ');

                throw new VonageSmsException(
                    $errorText !== '' ? "Vonage SMS error: {$errorText}" : 'Vonage SMS error.',
                );
            }
        }
    }

    /**
     * Build a Vonage client authenticated with the tenant's credentials.
     * Centralised so tests can override the client construction.
     */
    protected function createClient(Tenant $tenant): VonageClient
    {
        return new VonageClient(
            new Basic((string) $tenant->vonageApiKey(), (string) $tenant->vonageApiSecret()),
        );
    }
}
