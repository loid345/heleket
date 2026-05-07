<?php

namespace MageBrains\Heleket\Controller\Payment;

use MageBrains\Heleket\Logger\Logger;
use MageBrains\Heleket\Model\OrderManagement;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Webapi\Rest\Request;

class Callback implements HttpPostActionInterface, CsrfAwareActionInterface
{
    const HELEKET_ERROR_STATUSES = [
        'fail',
        'system_fail',
        'wrong_amount',
        'cancel',
    ];

    const HELEKET_PENDING_STATUSES = [
        'process',
        'check',
    ];

    const PAID_STATUSES = [
        'paid',
        'paid_over',
    ];
    /**
     * @var Request
     */
    private Request $request;

    /**
     * @var OrderManagement
     */
    private OrderManagement $orderManagement;

    /**
     * @var PageFactory
     */
    private $resultJsonFactory;

    /**
     * @param Request $request
     * @param OrderManagement $orderManagement
     * @param JsonFactory $resultJsonFactory
     * @param Logger $logger
     */
    public function __construct(
        Request         $request,
        OrderManagement $orderManagement,
        JsonFactory     $resultJsonFactory,
        Logger          $logger
    )
    {
        $this->request = $request;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->request->getBodyParams();
        $this->logger->warning(json_encode($request));
        $heleketOrderStatus = $request['status'];
        $orderId = $request['order_id'];
        $resultJson = $this->resultJsonFactory->create();
        $ocOrderStatus = null;
        if (in_array($heleketOrderStatus, self::HELEKET_ERROR_STATUSES)) {
            $ocOrderStatus = 'payment_heleket_invalid_status_id';
        } elseif (in_array($heleketOrderStatus, self::HELEKET_PENDING_STATUSES)) {
            $ocOrderStatus = 'payment_heleket_pending_status_id';
        } elseif (in_array($heleketOrderStatus, self::PAID_STATUSES)) {
            $ocOrderStatus = 'payment_heleket_paid_status_id';
        }

        if ($ocOrderStatus && $ocOrderStatus === 'payment_heleket_paid_status_id') {
            try {
                $this->orderManagement->createInvoice($orderId);
            } catch (\Exception $exception) {
                $this->logger->warning('Error creating Invoice: ' . $exception->getMessage());
            }
        } elseif ($ocOrderStatus === 'payment_heleket_pending_status_id') {
            $this->logger->warning("Heleket status : $heleketOrderStatus; Heleket order: $orderId");
        } elseif ($ocOrderStatus === 'payment_heleket_invalid_status_id') {
            $this->orderManagement->cancelOrder($orderId);
            $this->logger->warning("Heleket status : $heleketOrderStatus; Heleket order: $orderId");
        }

        return $resultJson->setHttpResponseCode(200);
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
}
