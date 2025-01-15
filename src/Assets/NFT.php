<?php

declare(strict_types=1);

namespace MultipleChain\TON\Assets;

use Olifanton\Ton\SendMode;
use Olifanton\Interop\Units;
use MultipleChain\TON\Address;
use MultipleChain\Utils\Number;
use MultipleChain\Enums\ErrorType;
use Olifanton\Interop\Boc\SnakeString;
use Olifanton\Ton\Contracts\Nft\NftItem;
use Olifanton\Ton\Contracts\Wallets\Transfer;
use MultipleChain\Interfaces\Assets\NftInterface;
use MultipleChain\TON\Services\TransactionSigner;
use Olifanton\Ton\Contracts\Nft\NftTransferOptions;

class NFT extends Contract implements NftInterface
{
    /**
     * @var array<mixed>|null
     */
    private ?array $metadata = null;

    /**
     * @return array<mixed>
     */
    public function getMetadata(): array
    {
        if ($this->metadata) {
            return $this->metadata;
        }

        $result = $this->provider->client->get('nft/collections', [
            'collection_address' => [$this->getAddress()]
        ]);

        $collectionUri = $result->nft_collections[0]->collection_content->uri;

        $data = json_decode(file_get_contents($collectionUri) ?: '');

        return ($this->metadata = [
            'name' => $data->name,
            'image' => $data->image,
            'description' => $data->description
        ]);
    }

    /**
     * @param int|string $tokenId
     * @return array<mixed>
     */
    public function getNftItem(int|string $tokenId): array
    {
        $result = $this->provider->client->get('nft/items', [
            'collection_address' => $this->getAddress(),
            'address' => [(string) $tokenId]
        ]);

        return (array) $result->nft_items[0];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getMetadata()['name'];
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->getMetadata()['description'];
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $result = $this->provider->client->get('nft/items', [
            'collection_address' => $this->getAddress(),
            'owner_address' => [$owner]
        ]);

        return new Number(count($result->nft_items), 0);
    }

    /**
     * @param int|string $tokenId
     * @return string
     */
    public function getOwner(int|string $tokenId): string
    {
        return Address::parse(
            $this->getNftItem($tokenId)['owner_address']
        )->toStringWallet($this->provider->isTestnet());
    }

    /**
     * @param int|string $tokenId
     * @return string
     */
    public function getTokenURI(int|string $tokenId): string
    {
        return $this->getNftItem($tokenId)['content']->uri;
    }

    /**
     * @param int|string $tokenId
     * @return string|null
     */
    public function getApproved(int|string $tokenId): ?string
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $sender
     * @param string $receiver
     * @param int|string $tokenId
     * @param string|null $body
     * @return TransactionSigner
     */
    public function transfer(
        string $sender,
        string $receiver,
        int|string $tokenId,
        ?string $body = null
    ): TransactionSigner {
        if ($this->getBalance($sender)->toFloat() <= 0) {
            throw new \RuntimeException(ErrorType::INSUFFICIENT_BALANCE->value);
        }

        $originalOwner = $this->getOwner($tokenId);

        if (strtolower($originalOwner) !== strtolower($sender)) {
            throw new \RuntimeException(ErrorType::UNAUTHORIZED_ADDRESS->value);
        }

        $itemAddress = Address::parse((string) $tokenId);

        return new TransactionSigner(
            new Transfer(
                bounce: true,
                dest: $itemAddress,
                amount: Units::toNano("0.05"),
                payload: NftItem::createTransferBody(
                    new NftTransferOptions(
                        Address::parse($receiver),
                        Address::parse($sender),
                        Units::toNano("0.000000001"),
                        $body ? SnakeString::fromString($body)->cell(true) : null
                    ),
                ),
                sendMode: SendMode::PAY_GAS_SEPARATELY,
            )
        );
    }

    /**
     * @param string $spender
     * @param string $owner
     * @param string $receiver
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function transferFrom(
        string $spender,
        string $owner,
        string $receiver,
        int|string $tokenId
    ): TransactionSigner {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $owner
     * @param string $spender
     * @param int|string $tokenId
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, int|string $tokenId): TransactionSigner
    {
        throw new \Exception('Method not implemented.');
    }
}
