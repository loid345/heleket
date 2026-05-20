<?php

namespace MageBrains\Heleket\Controller\Payment;

use MageBrains\Heleket\Service\Heleket as PaymentService;
use Magento\Framework\Exception\LocalizedException;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Redirect implements HttpGetActionInterface
{
    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var PaymentService
     */
    private PaymentService $paymentService;

    /**
     * @var ResultFactory
     */
    private ResultFactory $resultFactory;

    /**
     * @param Session $checkoutSession
     * @param PaymentService $paymentService
     * @param ResultFactory $resultFactory
     */
    public function __construct(Session $checkoutSession, PaymentService $paymentService, ResultFactory $resultFactory)
    {
        $this->checkoutSession = $checkoutSession;
        $this->paymentService = $paymentService;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @return \Magento\Backend\Model\View\Result\Redirect|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Heleket\Api\RequestBuilderException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $paymentResponse = $this->paymentService->createOrder($order);

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$paymentResponse) {
            throw new LocalizedException(__('Unable to create Heleket payment URL.'));
        }

        return $resultRedirect->setUrl($paymentResponse);
    }
}
