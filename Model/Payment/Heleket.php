<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MageBrains\Heleket\Model\Payment;

class Heleket extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * @var string
     */
    protected $_code = "heleket";

    /**
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(
        \Magento\Quote\Api\Data\CartInterface $quote = null
    ) {
        return parent::isAvailable($quote);
    }
}

