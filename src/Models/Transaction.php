<?php

declare(strict_types=1);

namespace MultipleChain\TON\Models;

use Olifanton\Interop\Units;
use MultipleChain\TON\Address;
use MultipleChain\Utils\Number;
use MultipleChain\TON\Provider;
use MultipleChain\Enums\ErrorType;
use MultipleChain\TON\Assets\Coin;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\Interfaces\ProviderInterface;
use MultipleChain\Interfaces\Models\TransactionInterface;

class Transaction implements TransactionInterface
{
    /**
     * @var string
     */
    private string $id;

    /**
     * @var mixed
     */
    private mixed $data = null;

    /**
     * @var Provider
     */
    protected Provider $provider;

    /**
     * @param string $id
     * @param Provider|null $provider
     */
    public function __construct(string $id, ?ProviderInterface $provider = null)
    {
        $this->id = $id;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getData(): mixed
    {
        try {
            if (null !== $this->data) {
                return $this->data;
            }

            $res = $this->provider->client->get('transactions', ['hash' => $this->id]);

            if (null === $res) {
                return null;
            }

            $transaction = $res->transactions[0] ?? null;

            if (null === $transaction) {
                return null;
            }

            $action = $this->provider->client->get('actions', [
                'trace_id' => [$transaction->trace_id]
            ])->actions[0] ?? null;

            if (null === $action) {
                return null;
            }

            return $this->data = (object) ['transaction' => $transaction, 'action' => $action];
        } catch (\Throwable $th) {
            throw new \RuntimeException(ErrorType::RPC_REQUEST_ERROR->value);
        }
    }

    /**
     * @param int|null $ms
     * @return TransactionStatus
     */
    public function wait(?int $ms = 4000): TransactionStatus
    {
        try {
            $status = $this->getStatus();
            if (TransactionStatus::PENDING != $status) {
                return $status;
            }

            sleep($ms / 1000);

            return $this->wait($ms);
        } catch (\Throwable $th) {
            return TransactionStatus::FAILED;
        }
    }

    /**
     * @return TransactionType
     */
    public function getType(): TransactionType
    {
        $data = $this->getData();

        if (null === $data) {
            return TransactionType::GENERAL;
        }

        $type = $data->action->type;

        if ('ton_transfer' === $type) {
            return TransactionType::COIN;
        }

        if ('jetton_transfer' === $type) {
            return TransactionType::TOKEN;
        }

        if ('nft_transfer' === $type) {
            return TransactionType::NFT;
        }

        return TransactionType::CONTRACT;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->provider->explorerUrl . $this->id;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->getData()?->action->details->comment ?? '';
    }

    /**
     * @return string
     */
    public function getSigner(): string
    {
        $data = $this->getData();
        $account = $data?->transaction->account;
        return Address::parse($account)->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @return Number
     */
    public function getFee(): Number
    {
        $data = $this->getData();
        $fee = Units::fromNano($data?->transaction->total_fees ?? 0);
        return new Number($fee->toFloat(), (new Coin())->getDecimals());
    }

    /**
     * @return int
     */
    public function getBlockNumber(): int
    {
        return $this->getData()?->transaction->block_ref->seqno ?? 0;
    }

    /**
     * @return int
     */
    public function getWorkchain(): int
    {
        return $this->getData()?->transaction->block_ref->workchain ?? 0;
    }

    /**
     * @return string
     */
    public function getShard(): string
    {
        return $this->getData()?->transaction->block_ref->shard ?? '';
    }

    /**
     * @return string
     */
    public function getBlockId(): string
    {
        $data = $this->getData();
        $ref = $data?->transaction->block_ref;
        return "{$ref->workchain}:{$ref->shard}:{$ref->seqno}";
    }

    /**
     * @return int
     */
    public function getBlockTimestamp(): int
    {
        return $this->getData()?->transaction->now ?? 0;
    }

    /**
     * @return int
     */
    public function getBlockConfirmationCount(): int
    {
        $blockNumber = $this->getBlockNumber();
        $blocks = $this->provider->client->get('blocks', [
            'workchain' => (string) $this->provider->network->getWorkchain(),
            'sort' => 'desc'
        ])->blocks;
        return $blocks[0]->seqno - $blockNumber;
    }

    /**
     * @return TransactionStatus
     */
    public function getStatus(): TransactionStatus
    {
        $data = $this->getData();
        if ($data?->transaction->prev_trans_hash) {
            return $data->action->success
                ? TransactionStatus::CONFIRMED
                : TransactionStatus::FAILED;
        }
        return TransactionStatus::PENDING;
    }
}
