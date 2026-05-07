<?php

namespace MageBrains\Heleket\Controller\Payment;

use MageBrains\Heleket\Logger\Logger;
use MageBrains\Heleket\Model\Config;
use MageBrains\Heleket\Model\OrderManagement;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;

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
        'confirm_check',
    ];

    const PAID_STATUSES = [
        'paid',
        'paid_over',
    ];
    /**
     * @var Http
     */
    private Http $request;

    /**
     * @var OrderManagement
     */
    private OrderManagement $orderManagement;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var JsonFactory
     */
    private JsonFactory $resultJsonFactory;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param Http $request
     * @param OrderManagement $orderManagement
     * @param Config $config
     * @param JsonFactory $resultJsonFactory
     * @param Logger $logger
     */
    public function __construct(
        Http            $request,
        OrderManagement $orderManagement,
        Config          $config,
        JsonFactory     $resultJsonFactory,
        Logger          $logger
    ) {
        $this->request = $request;
        $this->logger = $logger;
        $this->orderManagement = $orderManagement;
        $this->config = $config;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequestData();
        $this->logger->warning(json_encode($request));

        $heleketOrderStatus = strtolower(trim((string)($request['status'] ?? '')));
        $orderId = (string)($request['order_id'] ?? '');
        $resultJson = $this->resultJsonFactory->create();

        if (!$heleketOrderStatus || !$orderId) {
            $this->logger->warning('Heleket callback missed status or order_id');
            return $resultJson->setHttpResponseCode(200);
        }

        if (!$this->isSignatureValid($request)) {
            $this->logger->warning("Invalid Heleket callback signature; Heleket status: $heleketOrderStatus; Heleket order: $orderId");
            return $resultJson->setHttpResponseCode(400);
        }

        if (in_array($heleketOrderStatus, self::PAID_STATUSES, true)) {
            try {
                $this->orderManagement->createInvoice($orderId);
            } catch (\Exception $exception) {
                $this->logger->warning('Error creating Invoice: ' . $exception->getMessage());
            }
        } elseif (in_array($heleketOrderStatus, self::HELEKET_PENDING_STATUSES, true)) {
            $this->logger->warning("Heleket status : $heleketOrderStatus; Heleket order: $orderId");
        } elseif (in_array($heleketOrderStatus, self::HELEKET_ERROR_STATUSES, true)) {
            $this->orderManagement->cancelOrder($orderId);
            $this->logger->warning("Heleket status : $heleketOrderStatus; Heleket order: $orderId");
        } else {
            $this->logger->warning("Unknown Heleket status : $heleketOrderStatus; Heleket order: $orderId");
        }

        return $resultJson->setHttpResponseCode(200);
    }

    /**
     * @return array
     */
    private function getRequestData(): array
    {
        $request = $this->request->getPostValue();
        if (is_array($request) && $request) {
            return $request;
        }

        $content = (string)$this->request->getContent();
        if ($content) {
            $decodedRequest = json_decode($content, true);
            if (is_array($decodedRequest)) {
                return $decodedRequest;
            }
        }

        $params = $this->request->getParams();
        return is_array($params) ? $params : [];
    }

    /**
     * @param array $request
     * @return bool
     */
    private function isSignatureValid(array $request): bool
    {
        if (empty($request['sign'])) {
            return false;
        }

        $sign = (string)$request['sign'];
        unset($request['sign']);

        $hash = md5(base64_encode(json_encode($request, JSON_UNESCAPED_UNICODE)) . $this->config->getPaymentKey());

        return hash_equals($hash, $sign);
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
