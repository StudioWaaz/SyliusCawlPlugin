<?php

declare(strict_types=1);

namespace Waaz\SyliusCawlPlugin\Payum\CawlGateway\Action;

use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\OrderReferences;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Request\Capture;
use Payum\Core\Security\GenericTokenFactoryAwareInterface;
use Payum\Core\Security\GenericTokenFactoryAwareTrait;
use Payum\Core\Security\TokenFactoryInterface;
use Payum\Core\Security\TokenInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Waaz\SyliusCawlPlugin\Payum\CawlGateway\Api;

final class CaptureAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, GenericTokenFactoryAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use GenericTokenFactoryAwareTrait;

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
        $token = $request->getToken();
        $notifyToken = $this->createNotifyToken($request);

        // If payment is already processed, skip
        if (isset($details['cawl_payment_id']) && isset($details['cawl_redirect_url'])) {
            throw new HttpRedirect($details['cawl_redirect_url']);
        }

        $createHostedCheckoutRequest = new CreateHostedCheckoutRequest();
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($payment->getAmount());
        $amountOfMoney->setCurrencyCode('EUR');

        $order = new Order();
        $reference = new OrderReferences();

        $reference->setMerchantReference((string) $payment->getId());
        $reference->setMerchantParameters($notifyToken->getHash());
        $order->setReferences($reference);
        $order->setAmountOfMoney($amountOfMoney);

        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $afterUrl = $request->getToken()->getAfterUrl();
        $hostedCheckoutSpecificInput->setReturnUrl($afterUrl);

        $createHostedCheckoutRequest->setOrder($order);
        $createHostedCheckoutRequest->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);


        $response = $this->api->createHostedPayment($createHostedCheckoutRequest);

        $details['cawl_hosted_checkout_id'] = $response->hostedCheckoutId;
        $details['cawl_merchant_reference'] = $response->merchantReference;

        $redirectUrl = $response->redirectUrl;

        $payment->setDetails($details);

        // Redirect to CAWL payment page
        if ($redirectUrl) {
            throw new HttpRedirect($redirectUrl);
        }
    }

    private function createNotifyToken($request): TokenInterface
    {
        $token = $request->getToken();

        return $this->tokenFactory->createNotifyToken(
            $token->getGatewayName(),
            $token->getDetails()
        );
    }

    public function supports($request): bool
    {
        return $request instanceof Capture &&
            $request->getModel() instanceof PaymentInterface;
    }
}
