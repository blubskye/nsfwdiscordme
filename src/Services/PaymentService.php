<?php
namespace App\Services;

use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;

/**
 * Class PaymentService
 */
class PaymentService
{
    use LoggerAwareTrait;

    public const TIMEOUT = 2.0;

    protected string $baseURL;

    public function __construct(
        protected string $clientID,
        protected string $clientSecret,
        string $baseURL = 'https://yunogasai.site'
    ) {
        $this->baseURL = rtrim($baseURL, '/');
    }

    public function getURL(string $path): string
    {
        return sprintf('%s/api/v1/%s', $this->baseURL, $path);
    }

    public function getPurchaseURL(string $token): string
    {
        return sprintf('%s/purchase/%s', $this->baseURL, $token);
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function getToken(array $values): string
    {
        $requiredKeys = ['transactionID', 'price', 'description', 'successURL', 'cancelURL', 'failureURL', 'webhookURL'];
        if (!array_all($requiredKeys, fn(string $key): bool => !empty($values[$key]))) {
            throw new InvalidArgumentException('Missing values.');
        }

        $resp = $this->doRequest('POST', $this->getURL('token'), $values);
        if (empty($resp['token'])) {
            throw new Exception('Invalid response.');
        }

        return $resp['token'];
    }

    /**
     * @throws Exception
     * @throws GuzzleException
     */
    public function verify(string $token, string $code, string $price, string $transactionID): bool
    {
        $body = [
            'token' => $token,
            'code' => $code,
            'price' => $price,
            'transactionID' => $transactionID,
        ];
        $resp = $this->doRequest('POST', $this->getURL('verify'), $body);
        if (!isset($resp['valid'])) {
            throw new Exception('Invalid response.');
        }

        return $resp['valid'];
    }

    /**
     * @throws GuzzleException
     */
    protected function doRequest(string $method, string $url, ?array $body = null): mixed
    {
        $headers = [
            'X-Client-ID'     => $this->clientID,
            'X-Client-Secret' => $this->clientSecret,
            'Accept'          => 'application/json',
            'Content-Type'    => 'application/json'
        ];
        $options = [
            'headers' => $headers,
            'body'    => json_encode($body)
        ];

        $this->logger->debug("{$method}: {$url}", [$options]);

        $client = new Guzzle([
            'timeout' => self::TIMEOUT
        ]);
        $response = $client->request($method, $url, $options);

        return json_decode((string)$response->getBody(), true);
    }
}
