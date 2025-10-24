<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\Request\GetStatusInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Api;

final class StatusAction implements ActionInterface, ApiAwareInterface
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

        if (isset($details['cawl_error'])) {
            $request->markFailed();
            return;
        }

        if (!isset($details['cawl_payment_id'])) {
            $request->markNew();
            return;
        }

        try {
            $status = $details['cawl_status'];

            switch ($status) {
                case 'CREATED':
                case 'PENDING_PAYMENT':
                case 'PENDING_APPROVAL':
                case 'AUTHORIZATION_REQUESTED':
                case 'REDIRECTED':
                    $request->markPending();
                    break;
                case 'PENDING_CAPTURE':
                case 'CAPTURED':
                case 'PAID':
                    $request->markCaptured();
                    break;
                case 'CANCELLED':
                    $request->markCanceled();
                    break;
                case 'REJECTED':
                case 'REJECTED_CAPTURE':
                    $request->markFailed();
                    break;
                case 'REFUNDED':
                    $request->markRefunded();
                    break;
                default:
                    $request->markUnknown();
                    break;
            }
        } catch (\Exception $e) {
            $details['cawl_error'] = $e->getMessage();
            $payment->setDetails($details);
            $request->markFailed();
        }
    }

    public function supports($request): bool
    {
        return $request instanceof GetStatusInterface &&
            $request->getModel() instanceof PaymentInterface;
    }
}
