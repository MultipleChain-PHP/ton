<?php

declare(strict_types=1);

namespace MultipleChain\TON;

use MultipleChain\BaseNetworkConfig;

class NetworkConfig extends BaseNetworkConfig
{
    /**
     * @var boolean
     */
    public bool $testnet;

    /**
     * @var string
     */
    public string $apiKey;

    /**
     * @var int
     */
    public int $workchain;

    /**
     * @var string
     */
    public string $explorer = 'tonscan';

    /**
     * @param array<string,mixed> $network
     */
    public function __construct(array $network)
    {
        if (!isset($network['apiKey'])) {
            throw new \RuntimeException('API key is required');
        }

        $this->apiKey = $network['apiKey'];
        $this->testnet = $network['testnet'] ?? false;
        $this->workchain = $network['workchain'] ?? 0;

        if (isset($network['explorer'])) {
            $this->explorer = $network['explorer'];
        }

        parent::__construct($network);
    }

    /**
     * @return boolean
     */
    public function isTestnet(): bool
    {
        return $this->testnet;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return int
     */
    public function getWorkchain(): int
    {
        return $this->workchain;
    }

    /**
     * @return string
     */
    public function getExplorer(): string
    {
        return $this->explorer;
    }
}
