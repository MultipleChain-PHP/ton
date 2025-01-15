<?php

declare(strict_types=1);

namespace MultipleChain\TON\Assets;

use Olifanton\Ton\SendMode;
use Olifanton\Interop\Units;
use MultipleChain\TON\Address;
use MultipleChain\Utils\Number;
use Olifanton\Ton\AddressState;
use MultipleChain\Enums\ErrorType;
use Olifanton\Interop\Boc\SnakeString;
use Olifanton\Ton\Contracts\Wallets\Transfer;
use Olifanton\Ton\Contracts\Jetton\JettonMinter;
use MultipleChain\Interfaces\Assets\TokenInterface;
use MultipleChain\TON\Services\TransactionSigner;
use Olifanton\Ton\Contracts\Jetton\JettonWallet;
use Olifanton\Ton\Contracts\Jetton\JettonWalletOptions;
use Olifanton\Ton\Contracts\Jetton\TransferJettonOptions;

class Token extends Contract implements TokenInterface
{
    /**
     * @var object|null
     */
    private ?object $metadata = null;

    /**
     * @return object
     */
    public function getMetadata(): object
    {
        if ($this->metadata) {
            return $this->metadata;
        }

        $master = $this->getJettonMaster();

        return ($this->metadata = $master->jetton_content);
    }

    /**
     * @return object|null
     */
    public function getJettonMaster(): ?object
    {
        $result = $this->provider->client->get('jetton/masters', [
            'address' => [$this->getAddress()]
        ]);

        return $result->jetton_masters[0];
    }
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->getMetadata()->name;
    }

    /**
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->getMetadata()->symbol;
    }

    /**
     * @return int
     */
    public function getDecimals(): int
    {
        return (int) $this->getMetadata()->decimals;
    }

    /**
     * @param string $owner
     * @return Number
     */
    public function getBalance(string $owner): Number
    {
        $response = $this->provider->client->get('jetton/wallets', [
            'owner_address' => [$owner],
            'jetton_address' => $this->getAddress()
        ]);
        $decimals = $this->getDecimals();
        $balance = $response->jetton_wallets[0]->balance;
        return new Number(Units::fromNano($balance, $decimals)->toFloat(), $decimals);
    }

    /**
     * @return Number
     */
    public function getTotalSupply(): Number
    {
        $decimals = $this->getDecimals();
        $master = $this->getJettonMaster();
        $totalSupply = $master->total_supply;
        return new Number(Units::fromNano($totalSupply, $decimals)->toFloat(), $decimals);
    }

    /**
     * @param string $owner
     * @param string $spender
     * @return Number
     */
    public function getAllowance(string $owner, string $spender): Number
    {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $owner
     * @return Address|null
     */
    public function getJettonWalletAddress(string $owner): ?Address
    {
        $root = JettonMinter::fromAddress(
            $this->provider->transport,
            new Address($this->getAddress()),
        );

        return new Address($root->getJettonWalletAddress($this->provider->transport, new Address($owner)));
    }

    /**
     * @param float $amount
     * @return \Brick\Math\BigInteger
     */
    public function formatAmount(float $amount): \Brick\Math\BigInteger
    {
        $decimals = $this->getDecimals();
        return Units::toNano($amount, $decimals);
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

        $senderAddress = Address::parse($sender);
        $receiverAddress = Address::parse($receiver);
        $formattedAmount = $this->formatAmount($amount);
        $senderJettonAddress = $this->getJettonWalletAddress($sender);

        $jettonWallet = new JettonWallet(new JettonWalletOptions(
            address: $senderJettonAddress,
        ));

        $state = $this->provider->transport->getState($senderJettonAddress);

        if (AddressState::ACTIVE !== $state) {
            throw new \RuntimeException('Your Jetton Wallet is not active.');
        }

        return new TransactionSigner(
            new Transfer(
                bounce: true,
                dest: $senderJettonAddress,
                amount: Units::toNano("0.05"),
                payload: $jettonWallet->createTransferBody(
                    new TransferJettonOptions(
                        toAddress: $receiverAddress,
                        jettonAmount: $formattedAmount,
                        responseAddress: $senderAddress,
                        forwardAmount: Units::toNano("0.000000001"),
                        forwardPayload: $body ? SnakeString::fromString($body)->cell(true) : null
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
     * @param float $amount
     * @return TransactionSigner
     */
    public function transferFrom(
        string $spender,
        string $owner,
        string $receiver,
        float $amount
    ): TransactionSigner {
        throw new \Exception('Method not implemented.');
    }

    /**
     * @param string $owner
     * @param string $spender
     * @param float $amount
     * @return TransactionSigner
     */
    public function approve(string $owner, string $spender, float $amount): TransactionSigner
    {
        throw new \Exception('Method not implemented.');
    }
}
