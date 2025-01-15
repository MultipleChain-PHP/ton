<?php

declare(strict_types=1);

namespace MultipleChain\TON\Tests\Models;

use MultipleChain\TON\Tests\BaseTest;
use MultipleChain\Enums\TransactionType;
use MultipleChain\Enums\TransactionStatus;
use MultipleChain\TON\Models\Transaction;

class TransactionTest extends BaseTest
{
    /**
     * @var Transaction
     */
    private Transaction $tx;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->tx = new Transaction($this->data->coinTransferTx);
    }

    /**
     * @return void
     */
    public function testId(): void
    {
        $this->assertEquals($this->data->coinTransferTx, $this->tx->getId());
    }

    /**
     * @return void
     */
    public function testData(): void
    {
        $this->assertIsObject($this->tx->getData());
    }

    /**
     * @return void
     */
    public function testType(): void
    {
        $this->assertEquals(TransactionType::COIN, $this->tx->getType());
    }

    /**
     * @return void
     */
    public function testWait(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->wait());
    }

    /**
     * @return void
     */
    public function testUrl(): void
    {
        $this->assertEquals(
            'https://testnet.tonscan.org/tx/6f97ca02d8f20151210ca2bef32340804214e4f74eebf6a9edf13b727ac2527e',
            $this->tx->getUrl()
        );
    }

    /**
     * @return void
     */
    public function testSender(): void
    {
        $this->assertEquals(strtolower($this->data->senderTestAddress), strtolower($this->tx->getSigner()));
    }

    /**
     * @return void
     */
    public function testFee(): void
    {
        $this->assertEquals(0.002830538, $this->tx->getFee()->toFloat());
    }

    /**
     * @return void
     */
    public function testBlockNumber(): void
    {
        $this->assertEquals(28607062, $this->tx->getBlockNumber());
    }

    /**
     * @return void
     */
    public function getBlockId(): void
    {
        $this->assertEquals('0:6000000000000000:28607062', $this->tx->getBlockId());
    }

    /**
     * @return void
     */
    public function testBlockTimestamp(): void
    {
        $this->assertEquals(1736323418, $this->tx->getBlockTimestamp());
    }

    /**
     * @return void
     */
    public function testBlockConfirmationCount(): void
    {
        $this->assertGreaterThan(77696, $this->tx->getBlockConfirmationCount());
    }

    /**
     * @return void
     */
    public function testStatus(): void
    {
        $this->assertEquals(TransactionStatus::CONFIRMED, $this->tx->getStatus());
    }
}
