<?php

declare(strict_types=1);

namespace MultipleChain\TON\Models;

use MultipleChain\TON\Address;
use MultipleChain\Utils\Math;
use Olifanton\Interop\Units;
use MultipleChain\Utils\Number;
use MultipleChain\TON\Assets\Token;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\TokenTransactionInterface;

class TokenTransaction extends ContractTransaction implements TokenTransactionInterface
{
    /**
     * @return string
     */
    public function getAddress(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->asset ?? '';
        return Address::parse($source)->toStringContract($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->receiver ?? '';
        return Address::parse($source)->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->sender ?? '';
        return Address::parse($source)->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getReceiverJettonAddress(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->receiver_jetton_wallet ?? '';
        return Address::parse($source)->toStringContract($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getSenderJettonAddress(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->sender_jetton_wallet ?? '';
        return Address::parse($source)->toStringContract($this->provider->isTestnet());
    }

    /**
     * @return Number
     */
    public function getAmount(): Number
    {
        $data = $this->getData();
        $amount = $data?->action->details->amount ?? 0;
        $decimals = (new Token($this->getAddress()))->getDecimals();
        return new Number(Units::fromNano($amount, $decimals)->toFloat(), $decimals);
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
