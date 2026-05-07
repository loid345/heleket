<?php
declare(strict_types=1);

namespace MageBrains\Heleket\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getPaymentKey(?int $storeId = null): string
    {
        $apiKey = $this->scopeConfig->getValue(
            'payment/heleket/payment_key',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $apiKey;
    }


    /**
     * @param int|null $storeId
     * @return string
     */
    public function getMerchantUUID(?int $storeId = null)
    {
        $apiIdentifier = $this->scopeConfig->getValue(
            'payment/heleket/merchant_uuid',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $apiIdentifier;
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getLifetime(?int $storeId = null)
    {
        $apiIdentifier = $this->scopeConfig->getValue(
            'payment/heleket/lifetime',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $apiIdentifier;
    }

    public function configIsValid()
    {
        return $this->getPaymentKey() && $this->getMerchantUUID();
    }

}
