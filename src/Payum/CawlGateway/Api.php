<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway;

use OnlinePayments\Sdk\Authentication\V1HmacAuthenticator;
use OnlinePayments\Sdk\Client;
use OnlinePayments\Sdk\Communicator;
use OnlinePayments\Sdk\CommunicatorConfiguration;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use OnlinePayments\Sdk\Domain\PaymentResponse;
use OnlinePayments\Sdk\Domain\RefundRequest;
use OnlinePayments\Sdk\Domain\RefundResponse;
use OnlinePayments\Sdk\Merchant\MerchantClient;
use OnlinePayments\Sdk\Webhooks\InMemorySecretKeyStore;
use OnlinePayments\Sdk\Webhooks\SignatureValidator;

final class Api
{
    private Client $client;

    private string $merchantId;

    private string $webhookId;

    private string $webhookSecret;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        string $merchantId,
        string $webhookId = '',
        string $webhookSecret = '',
        bool $sandbox = true,
    ) {
        $endpoint = $sandbox
            ? 'https://payment.preprod.cawl-solutions.fr'
            : 'https://payment.cawl.fr';

        $communicatorConfiguration = new CommunicatorConfiguration(
            $apiKey,
            $apiSecret,
            $endpoint,
            'OnlinePayments',
        );

        $authenticator = new V1HmacAuthenticator($communicatorConfiguration);
        $communicator = new Communicator($communicatorConfiguration, $authenticator);
        $this->client = new Client($communicator);

        $this->merchantId = $merchantId;
        $this->webhookId = $webhookId;
        $this->webhookSecret = $webhookSecret;
    }

    public function createHostedPayment(CreateHostedCheckoutRequest $request): CreateHostedCheckoutResponse
    {
        return $this->getMerchantClient()->hostedCheckout()->createHostedCheckout($request);
    }

    public function getPayment(string $paymentId): PaymentResponse
    {
        return $this->getMerchantClient()->payments()->getPayment($paymentId);
    }

    public function getHostedCheckout(string $hostedCheckoutId): GetHostedCheckoutResponse
    {
        return $this->getMerchantClient()->hostedCheckout()->getHostedCheckout($hostedCheckoutId);
    }

    public function refundPayment(string $paymentId, RefundRequest $request): RefundResponse
    {
        return $this->getMerchantClient()->payments()->refundPayment($paymentId, $request);
    }

    private function getMerchantClient(): MerchantClient
    {
        return $this->client->merchant($this->merchantId);
    }

    public function verifySignature(string $payload, array $headers): void
    {
        $headers = array_map(function ($header) {
            return $header[0];
        }, $headers);
        $keyStore = new InMemorySecretKeyStore();
        $keyStore->storeSecretKey($this->getWebhookId(), $this->getWebhookSecret());
        $validator = new SignatureValidator($keyStore);
        $validator->validate($payload, $headers);
    }

    public function getWebhookId(): string
    {
        return $this->webhookId;
    }

    public function getWebhookSecret(): string
    {
        return $this->webhookSecret;
    }
}
