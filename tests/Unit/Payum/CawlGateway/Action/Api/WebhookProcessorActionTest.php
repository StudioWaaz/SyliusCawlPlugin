<?php

declare(strict_types=1);

namespace Tests\Waaz\SyliusCawlPlugin\Unit\Payum\CawlGateway\Action\Api;

use PHPUnit\Framework\TestCase;
use Payum\Core\Bridge\Spl\ArrayObject;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action\Api\WebhookProcessorAction;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Request\WebhookProcessor;

final class WebhookProcessorActionTest extends TestCase
{
    /**
     * @dataProvider paymentMethodsProvider
     */
    public function testItStoresPaymentMethodAndProductId(
        string $paymentMethod,
        string $specificOutputKey,
        int $paymentProductId,
    ): void {
        $model = new ArrayObject();
        $request = new WebhookProcessor($model, [
            'payment' => [
                'id' => 'payment-id',
                'status' => 'CAPTURED',
                'paymentOutput' => [
                    'paymentMethod' => $paymentMethod,
                    $specificOutputKey => [
                        'paymentProductId' => $paymentProductId,
                    ],
                ],
            ],
        ]);

        (new WebhookProcessorAction())->execute($request);

        self::assertSame('payment-id', $model['cawl_payment_id']);
        self::assertSame('CAPTURED', $model['cawl_status']);
        self::assertSame($paymentMethod, $model['cawl_payment_method']);
        self::assertSame($paymentProductId, $model['cawl_payment_product_id']);
    }

    public static function paymentMethodsProvider(): iterable
    {
        yield 'card' => ['card', 'cardPaymentMethodSpecificOutput', 1];
        yield 'mobile' => ['mobile', 'mobilePaymentMethodSpecificOutput', 302];
        yield 'redirect' => ['redirect', 'redirectPaymentMethodSpecificOutput', 840];
        yield 'bank transfer' => ['bankTransfer', 'bankTransferPaymentMethodSpecificOutput', 11];
    }

    public function testItLeavesPaymentDetailsAbsentWhenPaymentOutputIsMissing(): void
    {
        $model = new ArrayObject();
        $request = new WebhookProcessor($model, [
            'payment' => [
                'id' => 'payment-id',
                'status' => 'CAPTURED',
            ],
        ]);

        (new WebhookProcessorAction())->execute($request);

        self::assertArrayNotHasKey('cawl_payment_method', $model);
        self::assertArrayNotHasKey('cawl_payment_product_id', $model);
    }
}
