<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetToken;
use Payum\Core\Request\Notify;
use Payum\Core\Security\TokenInterface;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Api;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Request\WebhookProcessor;

final class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;

    public function __construct() {
        $this->apiClass = Api::class;
    }

    /**
     * @param Notify $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        if (null === $request->getModel()) {
            $webhookProcessor = $this->processUnsafe($request);
            $this->gateway->execute($webhookProcessor);
        }
    }

    private function processUnsafe(Notify $request): WebhookProcessor
    {
        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);

        // Not a Symfony app then
        if (!property_exists($httpRequest, 'headers')) {
            throw RequestNotSupportedException::create($request);
        }

        $headers = $httpRequest->headers;
        if (!$this->isWebhookNotification($headers)) {
            throw RequestNotSupportedException::create($request);
        }

        $content = $httpRequest->content;

        $this->api->verifySignature($content, $headers);

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw RequestNotSupportedException::create($request);
        }

        $hash = $this->retrieveTokenHash($payload);

        $token = $this->findTokenByHash($hash);

        return new WebhookProcessor(
            $token,
            $payload
        );
    }


    private function findTokenByHash(string $tokenHash): TokenInterface
    {
        $getTokenRequest = new GetToken($tokenHash);
        $this->gateway->execute($getTokenRequest);
        return $getTokenRequest->getToken();
    }

    private function retrieveTokenHash(array $payload): string
    {
        return $payload['payment']['paymentOutput']['merchantParameters'];
    }

    private function isWebhookNotification(array $headers): bool
    {
        return isset($headers['x-gcs-signature'][0]);
    }
    public function supports($request): bool
    {
        return $request instanceof Notify;
    }
}
