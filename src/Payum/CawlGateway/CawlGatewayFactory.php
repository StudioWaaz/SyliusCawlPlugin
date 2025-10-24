<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway;

use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action\CaptureAction;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action\NotifyAction;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action\StatusAction;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action\RefundAction;

final class CawlGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'cawl',
            'payum.factory_title' => 'CAWL (Crédit Agricole)',
            'payum.template.gateway_config' => '@WaazSyliusCawlPlugin/Admin/GatewayConfig/cawl.html.twig',
        ]);

        if (false === (bool) $config['payum.api']) {
            $config['payum.default_options'] = [
                'api_key' => '',
                'api_secret' => '',
                'merchant_id' => '',
                'webhook_id' => '',
                'webhook_secret' => '',
                'sandbox' => true,
            ];

            $config->defaults($config['payum.default_options']);

            $config['payum.required_options'] = ['api_key', 'api_secret', 'merchant_id'];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    $config['api_key'],
                    $config['api_secret'],
                    $config['merchant_id'],
                        $config['webhook_id'] ?? '',
                    $config['webhook_secret'] ?? '',
                    $config['sandbox']
                );
            };
        }
    }
}
