<?php

declare(strict_types=1);

namespace MageBrains\Heleket\Controller\Payment;

use MageBrains\Heleket\Model\Config;
use MageBrains\Heleket\Model\OrderManagement;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

class ReturnPage implements HttpGetActionInterface
{
    private const HELEKET_ERROR_STATUSES = [
        'fail',
        'system_fail',
        'wrong_amount',
        'cancel',
    ];

    private Session $checkoutSession;

    private OrderManagement $orderManagement;

    private Config $config;

    private ResultFactory $resultFactory;

    private RequestInterface $request;

    public function __construct(
        Session $checkoutSession,
        OrderManagement $orderManagement,
        Config $config,
        ResultFactory $resultFactory,
        RequestInterface $request
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderManagement = $orderManagement;
        $this->config = $config;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
    }

    public function execute()
    {
        $requestOrderId = (string)$this->request->getParam('order_id');
        $sessionOrderId = (string)$this->checkoutSession->getLastRealOrderId();
        $orderIncrementId = (string)($requestOrderId ?: $sessionOrderId ?: '');
        if ($orderIncrementId === '') {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
        }

        $providedSign = strtolower(trim((string)$this->request->getParam('rsign')));
        if ($requestOrderId !== '') {
            if ($providedSign === '') {
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
            }

            $expectedSign = hash_hmac('sha256', $requestOrderId, $this->config->getPaymentKey());
            if (!hash_equals($expectedSign, $providedSign)) {
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
            }
        }

        $order = $this->orderManagement->getOrderForRedirect($orderIncrementId);
        if (!$order->getId()) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
        }

        $status = strtolower(trim((string)$this->request->getParam('status')));
        if (in_array($status, self::HELEKET_ERROR_STATUSES, true) || $order->getState() === Order::STATE_CANCELED) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/onepage/failure');
        }

        if (!in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE], true)) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
        }

        $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastOrderId($order->getEntityId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/onepage/success');
    }
}
