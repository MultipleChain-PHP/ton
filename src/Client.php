<?php

declare(strict_types=1);

namespace MultipleChain\TON;

/**
 * @method mixed get(string $url, array<mixed> $data = [])
 * @method mixed post(string $url, array<mixed> $data = [])
 * @method mixed put(string $url, array<mixed> $data = [])
 * @method mixed delete(string $url, array<mixed> $data = [])
 * @method mixed head(string $url, array<mixed> $data = [])
 * @method mixed connect(string $url, array<mixed> $data = [])
 * @method mixed options(string $url, array<mixed> $data = [])
 * @method mixed trace(string $url, array<mixed> $data = [])
 * @method mixed patch(string $url, array<mixed> $data = [])
 */
final class Client
{
    /**
     * @var string
     */
    private string $mainnetApi = 'https://toncenter.com/api/v3/';

    /**
     * @var string
     */
    private string $testnetApi = 'https://testnet.toncenter.com/api/v3/';

    /**
     * @var string
     */
    private string $apiKey;

    /**
     * Base API url
     * @var string
     */
    private string $api;

    /**
     * cURL process infos
     * @var mixed
     */
    private mixed $info;

    /**
     * cURL process errors
     * @var string
     */
    private string $error;

    /**
     * @var array<string>
     */
    private array $methods = [
        "GET",
        "HEAD",
        "POST",
        "PUT",
        "DELETE",
        "CONNECT",
        "OPTIONS",
        "TRACE",
        "PATCH",
    ];

    /**
     * @var array<string>
     */
    private array $headers = [];

    /**
     * Default options
     * @var array<int,mixed>
     */
    private array $options = [
        CURLOPT_RETURNTRANSFER => true,
    ];

    /**
     * @param array<string,mixed> $config
     */
    public function __construct(array $config)
    {
        if (!isset($config['apiKey'])) {
            throw new \RuntimeException('API key is required');
        }

        $this->apiKey = $config['apiKey'];

        $testnet = $config['testnet'] ?? false;

        $this->api = $testnet ? $this->testnetApi : $this->mainnetApi;

        $this->addHeader('X-API-KEY', $this->apiKey);
    }

    /**
     * @param int $key
     * @param mixed $value
     * @return Client
     */
    public function addOption(int $key, mixed $value): Client
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * @param int $key
     * @return Client
     */
    public function deleteOption(int $key): Client
    {
        if (isset($this->options[$key])) {
            unset($this->options[$key]);
        }
        return $this;
    }

    /**
     * @param array<int> $keys
     * @return Client
     */
    public function deleteOptions(array $keys): Client
    {
        foreach ($keys as $key) {
            $this->deleteOption($key);
        }
        return $this;
    }

    /**
     * @param array<int,mixed> $options
     * @return Client
     */
    public function addOptions(array $options): Client
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return Client
     */
    public function addHeader(string $key, string $value): Client
    {
        $this->headers[$key] = $key . ': ' . $value;
        return $this;
    }

    /**
     * @param string $key
     * @return Client
     */
    public function deleteHeader(string $key): Client
    {
        if (isset($this->headers[$key])) {
            unset($this->headers[$key]);
        }
        return $this;
    }

    /**
     * @param array<string,string> $headers
     * @return Client
     */
    public function addHeaders(array $headers): Client
    {
        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }

        return $this;
    }

    /**
     * @param array<string> $keys
     * @return Client
     */
    public function deleteHeaders(array $keys): Client
    {
        foreach ($keys as $key) {
            $this->deleteHeader($key);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInfo(): mixed
    {
        return $this->info;
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     *
     * @param string $string
     * @return mixed
     */
    private function ifIsJson(string $string): mixed
    {
        $json = json_decode($string);
        if (JSON_ERROR_NONE === json_last_error()) {
            return $json;
        } else {
            return $string;
        }
    }

    /**
     * @param string $name
     * @param array<mixed> $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!in_array(strtoupper($name), $this->methods)) {
            throw new \Exception("Method not found");
        }

        $this->addOption(CURLOPT_CUSTOMREQUEST, strtoupper($name));
        $this->addOption(CURLOPT_HTTPHEADER, array_values($this->headers));
        return $this->beforeSend($name, ...$arguments);
    }

    /**
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array<mixed> $data
     * @param boolean $raw
     * @return mixed
     */
    private function beforeSend(string $method, string $url, array $data = [], bool $raw = false): mixed
    {
        if (!empty($data)) {
            if ($raw && 'GET' !== strtoupper($method)) {
                $data = json_encode($data);
                $data = <<<DATA
                    $data
                DATA;
            } else {
                $queryParts = [];
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $subValue) {
                            $queryParts[] = urlencode($key) . '=' . urlencode($subValue);
                        }
                    } else {
                        $queryParts[] = urlencode($key) . '=' . urlencode($value);
                    }
                }
                $data = implode('&', $queryParts);
            }

            if ('GET' === strtoupper($method)) {
                $url .= '?' . $data;
            } else {
                $this->addOption(CURLOPT_POSTFIELDS, $data);
            }
        }

        return $this->send($url);
    }

    /**
     * @param string $url
     * @return mixed
     */
    private function send(string $url): mixed
    {
        $url = $this->api . $url;

        // Init
        $curl = curl_init($url);

        if (false === $curl) {
            throw new \RuntimeException('Failed to initialize cURL');
        }

        // Set options
        curl_setopt_array($curl, $this->options);

        // Exec
        $result = curl_exec($curl);

        // Get some information
        $this->info = curl_getinfo($curl);
        $this->error = curl_error($curl);

        // Close
        curl_close($curl);

        if (is_string($result)) {
            $result = $this->ifIsJson($result);
        }

        $this->deleteOption(CURLOPT_POSTFIELDS);

        return $result;
    }
}
