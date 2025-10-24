<?php

/*
 * This file is part of the Flux:: shop.flux.audio sources.
 * COPYRIGHT (C), HARMAN INTERNATIONAL. ALL RIGHTS RESERVED.
 * -----------------------------------------------------------------------------
 * CONFIDENTIAL: NO PART OF THIS DOCUMENT MAY BE REPRODUCED IN ANY FORM WITHOUT THE
 * EXPRESSED WRITTEN PERMISSION OF HARMAN INTERNATIONAL.
 * DO NOT DISCLOSE ANY INFORMATION CONTAINED IN THIS DOCUMENT TO ANY THIRD-PARTY
 * WITHOUT THE PRIOR WRITTEN CONSENT OF HARMAN INTERNATIONAL.
 *
 */

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action\Api;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Request\WebhookProcessor;

class WebhookProcessorAction implements ActionInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;

    /**
     * @param WebhookProcessor $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        $details = $this->handleWebhookNotification($request->getPayload());

        $model->exchangeArray($details);
    }

    private function handleWebhookNotification(array $payload): array
    {
        $details = [];
        // Valider la signature du webhook
        try {
            // Traiter les données du webhook selon la documentation Cawl
            if (isset($payload['payment'])) {
                $paymentData = $payload['payment'];

                $details['cawl_payment_id'] = $paymentData['id'] ?? null;
                $details['cawl_status'] = $paymentData['status'] ?? null;
                $details['cawl_webhook_received'] = true;
                $details['cawl_webhook_timestamp'] = time();

                // Ajouter d'autres champs selon la structure des données Cawl
                if (isset($paymentData['statusOutput'])) {
                    $details['cawl_status_code'] = $paymentData['statusOutput']['statusCode'] ?? null;
                    $details['cawl_status_category'] = $paymentData['statusOutput']['statusCategory'] ?? null;
                }
            }

        } catch (\Exception $e) {
            $details['cawl_webhook_error'] = $e->getMessage();
        }

        return $details;
    }

    public function supports($request): bool
    {
        if (false === $request instanceof WebhookProcessor) {
            return false;
        }

        return $request->getModel() instanceof \ArrayAccess;
    }
}
