<?php

declare(strict_types=1);

namespace MultipleChain\TON\Models;

use MultipleChain\TON\Address;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\Models\NftTransactionInterface;

class NftTransaction extends ContractTransaction implements NftTransactionInterface
{
    /**
     * @return string
     */
    public function getAddress(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->nft_collection ?? '';
        return Address::parse($source)->toStringContract($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getReceiver(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->new_owner ?? '';
        return Address::parse($source)->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->old_owner ?? '';
        return Address::parse($source)->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @return string
     */
    public function getNftId(): int|string
    {
        $data = $this->getData();
        $source = $data?->action->details->nft_item ?? '';
        return Address::parse($source)->toStringContract($this->provider->isTestnet());
    }

    /**
     * @param AssetDirection $direction
     * @param string $address
     * @param int|string $nftId
     * @return TransactionStatus
     */
    public function verifyTransfer(AssetDirection $direction, string $address, int|string $nftId): TransactionStatus
    {
        $status = $this->getStatus();

        if (TransactionStatus::PENDING === $status) {
            return TransactionStatus::PENDING;
        }

        if ($this->getNftId() !== $nftId) {
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
