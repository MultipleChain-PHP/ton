<?php

declare(strict_types=1);

namespace MultipleChain\TON\Tests\Models;

use MultipleChain\TON\Tests\BaseTest;
use MultipleChain\Enums\AssetDirection;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\TON\Models\TokenTransaction;

class TokenTransactionTest extends BaseTest
{
    /**
     * @var TokenTransaction
     */
    private TokenTransaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new TokenTransaction($this->data->tokenTransferTx);
    }

    /**
     * @return void
     */
    public function testReceiver(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getReceiver()),
            strtolower($this->data->receiverTestAddress)
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(
            strtolower($this->tx->getSender()),
            strtolower($this->data->senderTestAddress)
        );
    }

    /**
     * @return void
     */
    public function testAmount(): void
    {
        $this->assertEquals(
            $this->tx->getAmount()->toFloat(),
            $this->data->tokenAmount
        );
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->assertEquals(
            $this->tx->getType(),
            TransactionType::TOKEN
        );
    }

    /**
     * @return void
     */
    public function testVerifyTransfer(): void
    {
        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->receiverTestAddress,
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::OUTGOING,
                $this->data->senderTestAddress,
                $this->data->tokenAmount
            ),
            TransactionStatus::CONFIRMED
        );

        $this->assertEquals(
            $this->tx->verifyTransfer(
                AssetDirection::INCOMING,
                $this->data->senderTestAddress,
                $this->data->tokenAmount
            ),
            TransactionStatus::FAILED
        );
    }
}
