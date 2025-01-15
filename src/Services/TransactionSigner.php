<?php

declare(strict_types=1);

namespace MultipleChain\TON\Services;

use Olifanton\Interop\Bytes;
use Olifanton\Interop\KeyPair;
use Olifanton\Interop\Boc\Cell;
use MultipleChain\TON\Provider;
use Olifanton\Mnemonic\TonMnemonic;
use Olifanton\Ton\Contracts\Wallets\Transfer;
use MultipleChain\Interfaces\ProviderInterface;
use Olifanton\Ton\Contracts\Wallets\AbstractWallet;
use Olifanton\Ton\Contracts\Wallets\TransferOptions;
use MultipleChain\Interfaces\Services\TransactionSignerInterface;

class TransactionSigner implements TransactionSignerInterface
{
    /**
     * @var Transfer
     */
    private Transfer $rawData;

    /**
     * @var Cell
     */
    private Cell $signedData;

    /**
     * @var Provider
     */
    private Provider $provider;

    /**
     * @var AbstractWallet
     */
    private AbstractWallet $wallet;

    /**
     * @var KeyPair
     */
    private KeyPair $keyPair;

    /**
     * @param mixed $rawData
     * @param Provider|null $provider
     * @return void
     */
    public function __construct(mixed $rawData, ?ProviderInterface $provider = null)
    {
        $this->rawData = $rawData;
        $this->provider = $provider ?? Provider::instance();
    }

    /**
     * @param string $privateKey
     * @return TransactionSignerInterface
     */
    public function sign(string $privateKey): TransactionSignerInterface
    {
        $this->keyPair = TonMnemonic::mnemonicToKeyPair(explode(' ', $privateKey));
        $this->wallet = $this->provider->createWalletV4($this->keyPair->publicKey);

        $message = $this->wallet->createTransferMessage([
            $this->rawData
        ], new TransferOptions(
            seqno: (int) $this->wallet->seqno($this->provider->transport),
        ));

        $this->signedData = $message->sign($this->keyPair->secretKey);

        return $this;
    }

    /**
     * @return string Transaction id
     */
    public function send(): string
    {
        $this->provider->transport->send($this->signedData->toBoc(false));
        $messageHash = Bytes::bytesToHexString($this->signedData->hash());
        return $this->provider->findTxHashByMessageHash($messageHash);
    }

    /**
     * @return Transfer
     */
    public function getRawData(): mixed
    {
        return $this->rawData;
    }

    /**
     * @return Cell
     */
    public function getSignedData(): mixed
    {
        return $this->signedData;
    }
}
