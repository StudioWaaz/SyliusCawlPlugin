<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\Notify;
use Sylius\Component\Core\Model\PaymentInterface;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Api;

final class NotifyAction implements ActionInterface, ApiAwareInterface
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

        // Parse URL parameters to get hostedCheckoutId
        $hostedCheckoutId = $_GET['hostedCheckoutId'] ?? null;
        $returnMac = $_GET['RETURNMAC'] ?? null;

        // If we have a hosted checkout ID from the return URL, use it to get payment information
        if ($hostedCheckoutId && !isset($details['cawl_payment_id'])) {
            try {
                $hostedCheckoutResponse = $this->api->getHostedCheckout($hostedCheckoutId);

                // If we have a created payment output, extract the payment ID
                if ($hostedCheckoutResponse->getCreatedPaymentOutput() &&
                    $hostedCheckoutResponse->getCreatedPaymentOutput()->getPayment()) {

                    $paymentResponse = $hostedCheckoutResponse->getCreatedPaymentOutput()->getPayment();
                    $details['cawl_payment_id'] = $paymentResponse->getId();
                    $details['cawl_status'] = $paymentResponse->getStatus();
                    $details['cawl_return_mac'] = $returnMac;

                    $payment->setDetails($details);
                }
            } catch (\Exception $e) {
                $details['cawl_error'] = $e->getMessage();
                $payment->setDetails($details);
            }
        }
        // If we already have the hosted checkout ID stored but no payment ID, try to convert it
        elseif (isset($details['cawl_hosted_checkout_id']) && !isset($details['cawl_payment_id'])) {
            try {
                $hostedCheckoutResponse = $this->api->getHostedCheckout($details['cawl_hosted_checkout_id']);

                // If we have a created payment output, extract the payment ID
                if ($hostedCheckoutResponse->getCreatedPaymentOutput() &&
                    $hostedCheckoutResponse->getCreatedPaymentOutput()->getPayment()) {

                    $paymentResponse = $hostedCheckoutResponse->getCreatedPaymentOutput()->getPayment();
                    $details['cawl_payment_id'] = $paymentResponse->getId();
                    $details['cawl_status'] = $paymentResponse->getStatus();

                    $payment->setDetails($details);
                }
            } catch (\Exception $e) {
                $details['cawl_error'] = $e->getMessage();
                $payment->setDetails($details);
            }
        }
    }

    public function supports($request): bool
    {
        return $request instanceof Notify &&
            $request->getModel() instanceof PaymentInterface;
    }
}
