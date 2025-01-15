<?php

declare(strict_types=1);

namespace MultipleChain\TON\Models;

use Olifanton\Interop\Units;
use MultipleChain\TON\Address;
use MultipleChain\Utils\Number;
use MultipleChain\TON\Assets\Coin;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\CoinTransactionInterface;

class CoinTransaction extends Transaction implements CoinTransactionInterface
{
    /**
     * @return string
     */
    public function getReceiver(): string
    {
        $data = $this->getData();
        $receiver = $data?->action->details->destination ?? '';
        return Address::parse($receiver)->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        $data = $this->getData();
        $sender = $data?->action->details->source ?? '';
        return Address::parse($sender)->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @return Number
     */
    public function getAmount(): Number
    {
        $data = $this->getData();
        $amount = Units::fromNano($data?->action->details->value);
        return new Number($amount->toFloat(), (new Coin())->getDecimals());
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param float $amount
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, float $amount): TransactionStatus
    {
        $status = $this->getStatus();

        if (TransactionStatus::PENDING === $status) {
            return TransactionStatus::PENDING;
        }

        if ($this->getAmount()->toFloat() !== $amount) {
            return TransactionStatus::FAILED;
        }

        if (AssetDirection::INCOMING === $direction) {
            if (strtolower($this->getReceiver()) !== strtolower($address)) {
                return TransactionStatus::FAILED;
            }
        } else {
            if (strtolower($this->getSender()) !== strtolower($address)) {
                return TransactionStatus::FAILED;
            }
        }

        return TransactionStatus::CONFIRMED;
    }
}
