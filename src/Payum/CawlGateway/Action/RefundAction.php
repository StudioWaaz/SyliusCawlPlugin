<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action;

use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\RefundRequest as CawlRefundRequest;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Refund;
use Sylius\Component\Core\Model\PaymentInterface;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Api;

final class RefundAction implements ActionInterface, ApiAwareInterface
{
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getModel();
        $details = $payment->getDetails();

        if (!isset($details['cawl_payment_id'])) {
            throw new \InvalidArgumentException('Payment ID is required for refund');
        }

        if (isset($details['cawl_refund_id'])) {
            return; // Already refunded
        }

        $amount = $payment->getAmount();
        $currencyCode = $payment->getCurrencyCode();

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($amount);
        $amountOfMoney->setCurrencyCode($currencyCode);

        $refundRequest = new CawlRefundRequest();
        $refundRequest->setAmountOfMoney($amountOfMoney);

        try {
            $response = $this->api->refundPayment($details['cawl_payment_id'], $refundRequest);

            $details['cawl_refund_id'] = $response->getId();
            $details['cawl_refund_status'] = $response->getStatus();
            $payment->setDetails($details);
        } catch (\Exception $e) {
            $details['cawl_refund_error'] = $e->getMessage();
            $payment->setDetails($details);
            throw $e;
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Refund &&
            $request->getModel() instanceof PaymentInterface;
    }
}
