<?php


namespace backndev\sixpayment\Payloads;

class Payloads
{
    protected $_customer;
    protected $_requestId;
    protected $_terminal;

    public function __construct(int $customer, string $requestId, int $terminal)
    {
        $this->_customer = $customer;
        $this->_requestId = $requestId;
        $this->_terminal = $terminal;
    }

    public function setDirectPaymentPayload(array $credentials, float $amount, $context, string $user, string $currency = 'CHF') : array {
        $payload = [
            'RequestHeader' =>
                self::setPaymentsHeaders(),
            'TerminalId' => $this->_terminal,
            'Payment' => [
                'Amount' => [
                    'Value' => $amount,
                    'CurrencyCode' => $currency
                ],
                'Description' => $context,
                'PayerNote' => $context . ' user ' . $user
            ],
            'PaymentMeans' =>
            self::setPaymentMeans($credentials)
        ];
        return $payload;
    }

    public function insertAliasCard(array $credentials){
        $payload = [
            'RequestHeader' => self::setPaymentsHeaders(),
            'PaymentMeans' => self::setPaymentMeans($credentials),
            'RegisterAlias' => [
                'IdGenerator' => "RANDOM"
            ]
        ];
        return $payload;
    }

    public function setDirectCapturePayload(string $id) : array {
        $payload = [
            'RequestHeader' => self::setPaymentsHeaders(),
            'TransactionReference' => [
                'TransactionId' => $id
            ]
        ];
        return $payload;
    }

    private static function setPaymentMeans(array $credentials) : array {
        $payload = [
            'Card' => [
                "Number" => $credentials['number'],
                "ExpYear" => $credentials['year'],
                "ExpMonth" => $credentials['month'],
                "HolderName" => $credentials['holder'],
                "VerificationCode" => $credentials['cvc']
            ]
        ];
        return $payload;
    }

    public function CreateDirectPaymentWithAlias($alias, float $amount, $context, string $currency = 'CHF'){
        $payload = [
            'RequestHeader' =>
                self::setPaymentsHeaders(),
            'TerminalId' => $this->_terminal,
            'Payment' => [
                'Amount' => [
                    'Value' => $amount,
                    'CurrencyCode' => $currency
                ],
                'Description' => $context,
                'PayerNote' => $context
            ],
            'PaymentMeans' => [
                'Alias' => [
                    'Id' => $alias
                ]
            ]
        ];
        return $payload;
    }

    private function setPaymentsHeaders() : array {
        $headers = [
            'SpecVersion'=> 1.10,
            'CustomerId' => $this->_customer,
            'RequestId' => $this->_requestId,
            'RetryIndicator' => 0
        ];
        return $headers;
    }
}