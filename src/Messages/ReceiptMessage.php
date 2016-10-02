<?php


namespace GigaAI\Messages;


/**
 * Class ReceiptMessage
 *
 * @package GigaAI\Messages
 */
class ReceiptMessage extends AbstractMessage
{
    private $recipientName;

    private $orderNumber;

    private $currency;

    private $paymentMethod;

    private $orderUrl;

    private $timestamp;

    private $elements = [];

    private $address;

    private $summary;

    private $adjustments;

    /**
     * ButtonMessage constructor.
     *
     * @param $recipient
     * @param $recipientName
     * @param $orderNumber
     * @param $currency
     * @param $paymentMethod
     * @param $orderUrl
     * @param $timestamp
     * @param $elements
     * @param $address
     * @param $summary
     * @param $adjustments
     */
    public function __construct(
        $recipient,
        $recipientName,
        $orderNumber,
        $currency,
        $paymentMethod,
        $orderUrl,
        $timestamp,
        $elements,
        $address,
        $summary,
        $adjustments
    ) {
        parent::__construct($recipient);

        $this->recipientName = $recipientName;
        $this->orderNumber = $orderNumber;
        $this->currency = $currency;
        $this->paymentMethod = $paymentMethod;
        $this->orderUrl = $orderUrl;
        $this->timestamp = $timestamp;
        $this->elements = $elements;
        $this->address = $address;
        $this->summary = $summary;
        $this->adjustments = $adjustments;
    }

    /**
     * Return body path of the message
     * Button message format:
     * "message":{
     *     "attachment":{
     *       "type":"template",
     *       "payload":{
     *         "template_type":"receipt",
     *         "recipient_name":"Stephane Crozatier",
     *         "order_number":"12345678902",
     *         "currency":"USD",
     *         "payment_method":"Visa 2345",
     *         "order_url":"http://petersapparel.parseapp.com/order?order_id=123456",
     *         "timestamp":"1428444852",
     *         "elements":[
     *           {
     *             "title":"Classic White T-Shirt",
     *             "subtitle":"100% Soft and Luxurious Cotton",
     *             "quantity":2,
     *             "price":50,
     *             "currency":"USD",
     *             "image_url":"http://petersapparel.parseapp.com/img/whiteshirt.png"
     *           },
     *           {
     *             "title":"Classic Gray T-Shirt",
     *             "subtitle":"100% Soft and Luxurious Cotton",
     *             "quantity":1,
     *             "price":25,
     *             "currency":"USD",
     *             "image_url":"http://petersapparel.parseapp.com/img/grayshirt.png"
     *           }
     *         }
     *      }
     *  }
     *
     * @return mixed
     */
    function getMessageBody()
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'receipt',
                    'recipient_name' => $this->recipientName,
                    'order_number' => $this->orderNumber,
                    'currency' => $this->currency,
                    'payment_method' => $this->paymentMethod,
                    'order_url' => $this->orderUrl,
                    'timestamp' => $this->timestamp,
                    'elements' => $this->elements,
                    'address' => $this->address,
                    'summary' => $this->summary,
                    'adjustments' => $this->adjustments
                ]
            ]
        ];
    }
}