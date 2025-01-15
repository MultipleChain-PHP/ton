<?php

declare(strict_types=1);

namespace MultipleChain\TON\Assets;

use Olifanton\Ton\SendMode;
use Olifanton\Interop\Units;
use MultipleChain\TON\Address;
use MultipleChain\Utils\Number;
use MultipleChain\TON\Provider;
use MultipleChain\Enums\ErrorType;
use Olifanton\Interop\Boc\SnakeString;
use Olifanton\Ton\Contracts\Wallets\Transfer;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Assets\CoinInterface;
use MultipleChain\TON\Services\TransactionSigner;

class Coin implements CoinInterface
{
    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @param Provider|null $provider
     */
    public function __construct(?ProviderInterface $provider = null)
    {
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Toncoin';
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return 'TON';
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return 9;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $response = $this->provider->client->get('addressInformation', ['address' => $owner]);
        return new Number(Units::fromNano($response->balance)->toFloat(), $this->getDecimals());
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param float $amount
     * @param string|null $body
     * @return TransactionSigner
     */
    public function transfer(string $sender, string $receiver, float $amount, ?string $body = null): TransactionSigner
    {
        if ($amount < 0) {
            throw new \RuntimeException(ErrorType::INVALID_AMOUNT->value);
        }

        if ($amount > $this->getBalance($sender)->toFloat()) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        return new TransactionSigner(
            new Transfer(
                bounce: false,
                amount: Units::toNano($amount),
                dest: Address::parse($receiver),
                sendMode: SendMode::PAY_GAS_SEPARATELY,
                payload: $body ? SnakeString::fromString($body)->cell(true) : '',
            )
        );
    }
}
