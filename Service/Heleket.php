<?php
declare(strict_types=1);

namespace MageBrains\Heleket\Service;

use Heleket\Api\Client;
use MageBrains\Heleket\Model\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

class Heleket
{
    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Config                $config,
        UrlInterface          $urlBuilder
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return \Heleket\Api\Payment
     */
    private function get()
    {
        return Client::payment($this->config->getPaymentKey(), $this->config->getMerchantUUID());
    }

    /**
     * @param $order
     * @return mixed|null
     * @throws LocalizedException
     * @throws \Heleket\Api\RequestBuilderException
     */
    public function createOrder($order)
    {
        if (!$this->config->configIsValid()) {
            throw new LocalizedException(
                __('Payment config isn\'t valid.')
            );
        }
        $paymentInstance = $this->get();
        $result = $paymentInstance->create($this->getData($order));

        if (isset($result['url'])) {
            return $result['url'];
        }

        return null;
    }

    /**
     * @param $order
     * @return array
     */
    private function getData($order)
    {
        return [
            'amount' => (string)round((float)$order->getGrandTotal(), 2),
            'currency' => $this->getCurrentCurrencyCode(),
            'order_id' => $order->getIncrementId(),
            'url_return' => $this->getReturnPageUrl((string)$order->getIncrementId()),
            'url_callback' => $this->urlBuilder->getUrl('heleket/payment/callback'),
            'is_payment_multiple' => false,
            'lifetime' => $this->config->getLifetime()
        ];
    }

    /**
     * Get current store currency code
     *
     * @return string
     */
    private function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @return string
     */
    private function getReturnPageUrl(string $orderIncrementId)
    {
        $returnSign = hash_hmac('sha256', $orderIncrementId, $this->config->getPaymentKey());

        return $this->urlBuilder->getUrl('heleket/payment/returnpage', [
            "_query" => [
                "order_id" => $orderIncrementId,
                "rsign" => $returnSign
            ]
        ]);
    }
}
