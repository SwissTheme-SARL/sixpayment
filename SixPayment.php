<?php


namespace backndev\sixpayment;

use backndev\sixpayment\Payloads\Payloads;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SixPayment extends Bundle
{
    protected $_customer;
    protected $_requestId;
    protected $_terminal;
    protected $_apiKey;
    protected $_uri;
    private $_serializer;

    public function __construct(string $customer, int $terminal, int $requestId, string $apiKey, string $uri)
    {
        $this->_customer = $customer;
        $this->_terminal = $terminal;
        $this->_requestId = $requestId;
        $this->_apiKey = $apiKey;
        $this->_uri = $uri;
        $encoders = [new XmlEncoder(), new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->_serializer = new Serializer($normalizers, $encoders);
    }

    public function createDirectPayment(array $credentials){
        $payload = self::createPayloadInstance();
        $client = HttpClient::create(['headers' => self::setHeadersRequest()]);
        try {
            $response = $client->request('POST', $this->_uri . '/Payment/v1/Transaction/AuthorizeDirect',
                [
                    'body' => json_encode($payload->setDirectPaymentPayload($credentials['credentials'], $credentials['amount'], $credentials['currency'], $credentials['context']), JSON_FORCE_OBJECT)
                ]);
            $data = json_decode($response->getContent());
            $alias = json_decode(self::createAlias($credentials));
            $data->alias = $alias->Alias->Id;
            if (isset($data->Transaction->Status) && $data->Transaction->Status === 'AUTHORIZED') {
                return json_encode($data);
            }
        } catch (ClientExceptionInterface $e) {
            return json_encode(['error' => 'A problem was occurred during the payment']);
        } catch (RedirectionExceptionInterface $e) {
            return json_encode(['error' => 'A problem was occurred during the payment']);
        } catch (ServerExceptionInterface $e) {
            return json_encode(['error' => 'A problem was occurred during the payment']);
        } catch (TransportExceptionInterface $e) {
            return json_encode(['error' => 'A problem was occurred during the payment']);
        }
        return json_encode(['error' => 'Payment failed']);
    }

    public function createAlias($credentials){
        $payload = self::createPayloadInstance();
        $client = HttpClient::create(['headers' => self::setHeadersRequest()]);
        $alias = $client->request('POST', $this->_uri . '/Payment/v1/Alias/InsertDirect',
            [
                'body' => json_encode($payload->insertAliasCard($credentials['credentials']), JSON_FORCE_OBJECT)
            ]);
        try {
            return $alias->getContent();
        } catch (ClientExceptionInterface $e) {
            return json_encode(['error' => 'Payment failed']);
        } catch (RedirectionExceptionInterface $e) {
            return json_encode(['error' => 'Payment failed']);
        } catch (ServerExceptionInterface $e) {
            return json_encode(['error' => 'Payment failed']);
        } catch (TransportExceptionInterface $e) {
            return json_encode(['error' => 'Payment failed']);
        }
    }

    public function capturePayment(string $id){
        try {
            $payload = new Payloads($this->_customer, $this->_requestId, $this->_terminal);
            $client = HttpClient::create(['headers' => self::setHeadersRequest()]);
            $response = $client->request('POST', $this->_uri . '/Payment/v1/Transaction/Capture', [
                'body' => json_encode($payload->setDirectCapturePayload($id))
            ]);
        } catch (TransportExceptionInterface $e) {
            return json_encode(['error' => 'A problem was occurred during the payment']);
        }
    }

    public function cancelPayment(string $id): string {
        try {
            $payload = self::createPayloadInstance();
            $client = HttpClient::create(['headers' => self::setHeadersRequest()]);
            $response = $client->request('POST', $this->_uri . '/Payment/v1/Transaction/Cancel', [
                'body' => json_encode($payload->setDirectCapturePayload($id))
            ]);
        }catch (TransportExceptionInterface $e){
            return json_encode(['error' => 'Transaction not found']);
        }
        try {
            return $response->getContent();
        } catch (ClientExceptionInterface $e) {
            return json_encode(['error' => 'Transaction not found']);
        } catch (RedirectionExceptionInterface $e) {
            return json_encode(['error' => 'Transaction not found']);
        } catch (ServerExceptionInterface $e) {
            return json_encode(['error' => 'Transaction not found']);
        } catch (TransportExceptionInterface $e) {
            return json_encode(['error' => 'Transaction not found']);
        }
    }

    public function createAliasPayment(string $alias, $amount, $context){
        $payloadInstance = self::createPayloadInstance();
        $payload = $payloadInstance->CreateDirectPaymentWithAlias($alias, $amount, $context);
        $client = HttpClient::create(['headers' => self::setHeadersRequest()]);
        $res = $client->request('POST', $this->_uri . '/Payment/v1/Transaction/AuthorizeDirect', [
            'body' => json_encode($payload)
        ]);
        return $res->getContent();
    }

    private function setHeadersRequest(){
        $headers = [
            'Content-type' => 'application/json',
            'Authorization' => 'Basic ' . $this->_apiKey
        ];
        return $headers;
    }

    private function createPayloadInstance() : Payloads{
        return new Payloads($this->_customer, $this->_requestId, $this->_terminal);
    }
}
