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

final class Api
{
    private Client $client;
    private string $merchantId;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        string $merchantId,
        bool $sandbox = true
    ) {
        $endpoint = $sandbox
            ? 'https://payment.preprod.cawl-solutions.fr'
            : 'https://payment.cawl.fr';

        $communicatorConfiguration = new CommunicatorConfiguration(
            $apiKey,
            $apiSecret,
            $endpoint,
            'OnlinePayments'
        );

        $authenticator = new V1HmacAuthenticator($communicatorConfiguration);
        $communicator = new Communicator($communicatorConfiguration, $authenticator);
        $this->client = new Client($communicator);

        $this->merchantId = $merchantId;
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
}
