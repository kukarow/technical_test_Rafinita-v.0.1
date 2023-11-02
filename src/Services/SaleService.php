<?php

declare(strict_types=1);

namespace ApiSaleLibrary\Services;

use ApiSaleLibrary\Contracts\RafinitaRequestInterface;
use ApiSaleLibrary\Contracts\RafinitaResponseInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class SaleService implements RafinitaRequestInterface
{
    protected string $apiEndpoint;
    protected array $clientData = [];
    protected string $passwordKey;
    protected string $publicKey;

    public function __construct(string $passwordKey, string $publicKey)
    {
        $this->publicKey = $publicKey;
        $this->passwordKey = $passwordKey;
    }

    protected function setHash(): string
    {

        $hashInput = strtoupper(
            strrev($this->clientData['payer_email']) .
            $this->passwordKey .
            strrev(substr($this->clientData['card_number'], 0, 6) . substr($this->clientData['card_number'], -4))
        );

        return md5($hashInput);
    }
    public function setEndpoint(string $endpoint): void
    {
        $this->apiEndpoint = $endpoint;
    }

    public function setData(array $data): void
    {
        $this->clientData = $data;
    }

    /**
     * @throws GuzzleException
     */
    public function send(): RafinitaResponseInterface
    {
        $this->clientData['hash'] = $this->setHash();
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $options = ['form_params' => $this->clientData];

            $client = new Client();
            $request = new Request('POST', $this->apiEndpoint, $headers);
            $res = $client->sendAsync($request, $options)->wait();

        return new ResponseService($res->getStatusCode(), json_decode((string)$res->getBody(), true));
    }
}
