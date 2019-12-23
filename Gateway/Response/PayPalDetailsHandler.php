<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Braintree\Gateway\Response;

use Braintree\Transaction;
use Magento\Braintree\Model\Adapter\BraintreeAdapter;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Braintree\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Class PayPalDetailsHandler
 *
 * Handles PayPal data and assigns it to the orders payment.
 */
class PayPalDetailsHandler implements HandlerInterface
{
    const PAYMENT_ID = 'paymentId';
    const PAYER_EMAIL = 'payerEmail';

    /**
     * @var SubjectReader
     */
    private $subjectReader;
    /**
     * @var BraintreeAdapter
     */
    private $braintreeAdapter;

    /**
     * Constructor
     *
     * @param SubjectReader $subjectReader
     * @param BraintreeAdapter $braintreeAdapter
     */
    public function __construct(
        SubjectReader $subjectReader,
        BraintreeAdapter $braintreeAdapter
    ) {
        $this->subjectReader = $subjectReader;
        $this->braintreeAdapter = $braintreeAdapter;
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        /** @var Transaction $transaction */
        $transaction = $this->subjectReader->readTransaction($response);

        if ($transaction->paymentInstrumentType === 'credit_card') {
            $this->braintreeAdapter->voidOrRefund($transaction, 'PayPal', 'Credit Card');
        }

        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $payPal = $this->subjectReader->readPayPal($transaction);
        $payment->setAdditionalInformation(self::PAYMENT_ID, $payPal[self::PAYMENT_ID]);
        $payment->setAdditionalInformation(self::PAYER_EMAIL, $payPal[self::PAYER_EMAIL]);
    }
}
