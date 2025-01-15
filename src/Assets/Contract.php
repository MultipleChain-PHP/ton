<?php

declare(strict_types=1);

namespace MultipleChain\TON\Assets;

use MultipleChain\TON\Provider;
use Olifanton\Interop\Boc\Cell;
use Olifanton\Interop\Boc\Builder;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\ContractInterface;

class Contract implements ContractInterface
{
    /**
     * @var string
     */
    private string $address;

    /**
     * @var array<string,mixed>
     */
    private array $cachedMethods = [];

    /**
     * @var Provider
     */
    protected Provider $provider;

    /**
     * @param string $address
     * @param Provider|null $provider
     */
    public function __construct(string $address, ?ProviderInterface $provider = null)
    {
        $this->address = $address;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethod(string $method, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function callMethodWithCache(string $method, mixed ...$args): mixed
    {
        if (isset($this->cachedMethods[$method])) {
            return $this->cachedMethods[$method];
        }

        return $this->cachedMethods[$method] = $this->callMethod($method, ...$args);
    }

    /**
     * @param string $method
     * @param mixed ...$args
     * @return mixed
     */
    public function getMethodData(string $method, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $method
     * @param string $from
     * @param mixed ...$args
     * @return mixed
     */
    public function createTransactionData(string $method, string $from, mixed ...$args): mixed
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param Builder $cell
     * @param string $receiver
     * @param string $sender
     * @param string|null $body
     * @return Cell
     */
    public function endCell(Builder $cell, string $receiver, string $sender, string $body = null): Cell
    {
        $cell->storeAddress(Address::parse($receiver))
            ->storeAddress(Address::parse($sender))
            ->storeBit(0)
            ->storeCoins(1);

        if ($body) {
            $cell->storeBit(1)->storeRef((new Builder())->storeUint(0, 32)->storeStringTail($body)->endCell());
        } else {
            $cell->storeBit(0);
        }

        return $cell->endCell();
    }
}
