<?php

declare(strict_types=1);

namespace MultipleChain\TON\Tests\Assets;

use MultipleChain\TON\Assets\Coin;
use MultipleChain\TON\Tests\BaseTest;
use MultipleChain\TON\Models\Transaction;

class CoinTest extends BaseTest
{
    /**
     * @var Coin
     */
    private Coin $coin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->coin = new Coin();
    }

    /**
     * @return void
     */
    public function testName(): void
    {
        $this->assertEquals('Toncoin', $this->coin->getName());
    }

    /**
     * @return void
     */
    public function testSymbol(): void
    {
        $this->assertEquals('TON', $this->coin->getSymbol());
    }

    /**
     * @return void
     */
    public function testDecimals(): void
    {
        $this->assertEquals(9, $this->coin->getDecimals());
    }

    /**
     * @return void
     */
    public function testBalance(): void
    {
        $this->assertEquals(
            $this->data->coinBalanceTestAmount,
            $this->coin->getBalance($this->data->balanceTestAddress)->toFloat()
        );
    }

    /**
     * @return void
     */
    public function testTransfer(): void
    {
        $signer = $this->coin->transfer(
            $this->data->senderTestAddress,
            $this->data->receiverTestAddress,
            $this->data->transferTestAmount,
            'ton-transfer-test'
        );

        $signer = $signer->sign($this->data->senderSeedPhrase);

        if (!$this->data->coinTransferTestIsActive) {
            $this->assertTrue(true);
            return;
        }

        $this->assertEquals(
            (new Transaction($signer->send()))->wait(),
            \MultipleChain\Enums\TransactionStatus::CONFIRMED
        );
    }
}
