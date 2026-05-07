<?php
declare(strict_types=1);

namespace MageBrains\Heleket\Model;

use MageBrains\Heleket\Logger\Logger;
use Magento\Framework\DB\Transaction;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\InvoiceService;

class OrderManagement
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var OrderManagementInterface
     */
    private OrderManagementInterface $orderManagement;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param OrderFactory $orderFactory
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     * @param OrderManagementInterface $orderManagement
     * @param Logger $logger
     */
    public function __construct(
        OrderFactory   $orderFactory,
        InvoiceService $invoiceService,
        InvoiceSender  $invoiceSender,
        Transaction    $transaction,
        OrderManagementInterface  $orderManagement,
        Logger $logger
    ) {
        $this->orderFactory = $orderFactory;
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->orderManagement = $orderManagement;
        $this->logger = $logger;
    }

    /**
     * @param $orderIncrementId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createInvoice($orderIncrementId)
    {
        $order = $this->getOrderByIncrementId($orderIncrementId);
        if ($order->getId()) {
            $invoice = null;

            if ($order->canInvoice()) {
                $invoice = $this->invoiceService->prepareInvoice($order);
                $invoice->register();
                $invoice->save();

                $transactionSave =
                    $this->transaction
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder());
                $transactionSave->save();
                $this->logger->warning("Invoice for order $orderIncrementId successfully created");
            } else {
                $this->logger->warning("Invoice for order $orderIncrementId already created. Skipping");
            }

            $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
            $order->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
            $order->save();

            if ($invoice) {
                try {
                    $this->invoiceSender->send($invoice);
                    $order->addCommentToStatusHistory(
                        __('Notified customer about invoice creation')
                    )->setIsCustomerNotified(true)->save();
                } catch (\Exception $exception) {
                    $this->logger->warning('Error sending Invoice email: ' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * @param $incrementId
     * @return void
     */
    public function cancelOrder($incrementId)
    {
        $order = $this->getOrderByIncrementId($incrementId);
        if ($order->getId()) {
            $this->orderManagement->cancel($order->getId());
        }
    }

    /**
     * @param $orderIncrementId
     * @return \Magento\Sales\Model\Order
     */
    private function getOrderByIncrementId($orderIncrementId)
    {
        return $this->orderFactory->create()->loadByIncrementId($orderIncrementId);
    }
}
