<?php

declare(strict_types=1);

namespace MageBrains\Heleket\Controller\Payment;

use MageBrains\Heleket\Model\OrderManagement;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

class ReturnPage implements HttpGetActionInterface
{
    private Session $checkoutSession;

    private OrderManagement $orderManagement;

    private ResultFactory $resultFactory;

    public function __construct(
        Session $checkoutSession,
        OrderManagement $orderManagement,
        ResultFactory $resultFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderManagement = $orderManagement;
        $this->resultFactory = $resultFactory;
    }

    public function execute()
    {
        $orderIncrementId = (string)($this->checkoutSession->getLastRealOrderId() ?: '');
        if ($orderIncrementId === '') {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
        }

        $order = $this->orderManagement->getOrderForRedirect($orderIncrementId);
        if (!$order->getId()) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('checkout/cart');
        }

        if ($order->getState() === Order::STATE_CANCELED) {
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
