<?php

declare(strict_types=1);

namespace MultipleChain\TON;

use Olifanton\Ton\Network;
use Olifanton\Interop\Bytes;
use MultipleChain\Enums\ErrorType;
use Http\Client\Common\HttpMethodsClient;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Olifanton\Ton\Contracts\Wallets\WalletId;
use MultipleChain\Interfaces\ProviderInterface;
use Olifanton\Ton\Transports\Toncenter\ClientOptions;
use Olifanton\Ton\Transports\Toncenter\ToncenterTransport as Transport;
use Olifanton\Ton\Transports\Toncenter\ToncenterHttpV2Client as Client2;
// wallets
use Olifanton\TypedArrays\Uint8Array;
use Olifanton\Ton\Contracts\Wallets\V3\WalletV3R1;
use Olifanton\Ton\Contracts\Wallets\V3\WalletV3R2;
use Olifanton\Ton\Contracts\Wallets\V4\WalletV4R1;
use Olifanton\Ton\Contracts\Wallets\V4\WalletV4R2;
use Olifanton\Ton\Contracts\Wallets\V5\WalletV5Beta;
use Olifanton\Ton\Contracts\Wallets\AbstractWallet;
use Olifanton\Ton\Contracts\Wallets\V3\WalletV3Options;
use Olifanton\Ton\Contracts\Wallets\V4\WalletV4Options;
use Olifanton\Ton\Contracts\Wallets\V5\WalletV5Options;

class Provider implements ProviderInterface
{
    /**
     * @var NetworkConfig
     */
    public NetworkConfig $network;

    /**
     * @var Client
     */
    public Client $client;

    /**
     * @var Client2
     */
    public Client2 $client2;

    /**
     * @var Transport
     */
    public Transport $transport;

    /**
     * @var Provider|null
     */
    private static ?Provider $instance;

    /**
     * @var string
     */
    private string $mainnetEndpoint = 'https://toncenter.com/api/v2/jsonRPC';

    /**
     * @var string
     */
    private string $testnetEndpoint = 'https://testnet.toncenter.com/api/v2/jsonRPC';

    /**
     * @var string
     */
    private string $tonViewerMainnet = 'https://tonviewer.com/transaction/';

    /**
     * @var string
     */
    private string $tonViewerTestnet = 'https://testnet.tonviewer.com/transaction/';

    /**
     * @var string
     */
    private string $tonScanMainnet = 'https://tonscan.org/tx/';

    /**
     * @var string
     */
    private string $tonScanTestnet = 'https://testnet.tonscan.org/tx/';

    /**
     * @var string
     */
    public string $endpoint;

    /**
     * @var string
     */
    public string $explorerUrl;

    /**
     * @var Network
     */
    public Network $net;

    /**
     * @var array<string,mixed>
     */
    public array $walletStandard = [
        'testOnly' => false,
        'bounceable' => false
    ];

    /**
     * @var array<string,mixed>
     */
    public array $contractStandard = [
        'testOnly' => false,
        'bounceable' => true
    ];

    /**
     * @param array<string,mixed> $network
     */
    public function __construct(array $network)
    {
        $this->update($network);
    }

    /**
     * @return Provider
     */
    public static function instance(): Provider
    {
        if (null === self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_NOT_INITIALIZED->value);
        }
        return self::$instance;
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public static function initialize(array $network): void
    {
        if (null !== self::$instance) {
            throw new \RuntimeException(ErrorType::PROVIDER_IS_ALREADY_INITIALIZED->value);
        }
        self::$instance = new self($network);
    }

    /**
     * @param array<string,mixed> $network
     * @return void
     */
    public function update(array $network): void
    {
        self::$instance = $this;
        $this->network = new NetworkConfig($network);
        $this->client = new Client([
            'apiKey' => $this->network->apiKey,
            'testnet' => $this->network->testnet
        ]);

        $testnet = $this->network->isTestnet();

        $this->endpoint = $testnet ? $this->testnetEndpoint : $this->mainnetEndpoint;

        if ('tonscan' === $this->network->explorer) {
            $this->explorerUrl = $testnet  ? $this->tonScanTestnet : $this->tonScanMainnet;
        } else {
            $this->explorerUrl = $testnet  ? $this->tonViewerTestnet : $this->tonViewerMainnet;
        }

        $this->walletStandard['testOnly'] = $testnet;
        $this->contractStandard['testOnly'] = $testnet;

        $this->net = $testnet ? Network::TEST : Network::MAIN;

        $httpClient = new HttpMethodsClient(
            Psr18ClientDiscovery::find(),
            Psr17FactoryDiscovery::findRequestFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
        );

        $this->client2 = new Client2(
            $httpClient,
            new ClientOptions(
                $testnet ? ClientOptions::TEST_BASE_URL : ClientOptions::MAIN_BASE_URL,
                $this->network->apiKey
            ),
        );

        $this->transport = new Transport($this->client2);
    }

    /**
     * @return bool
     */
    public function isTestnet(): bool
    {
        return $this->network->isTestnet();
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkRpcConnection(?string $url = null): bool
    {
        try {
            $result = $this->client->get('masterchainInfo');
            if (is_object($result) && isset($result->last)) {
                return true;
            }
            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * @param string|null $url
     * @return bool
     */
    public function checkWsConnection(?string $url = null): bool
    {
        return boolval($url ?? $this->network->getWsUrl() ?? '');
    }

    /**
     * @param string $address
     * @return WalletVersion
     */
    public function findWalletVersion(string $address): WalletVersion
    {
        $result = $this->client->get('walletInformation', ['address' => $address]);
        if ('uninitialized' === $result->status) {
            throw new \RuntimeException('Wallet is not initialized');
        }

        $type = $result->wallet_type;

        switch ($type) {
            case 'wallet v5 r1':
                return WalletVersion::V5R1;
            case 'wallet v5 beta':
                return WalletVersion::V5_BETA;
            case 'wallet v4 r2':
                return WalletVersion::V4R2;
            case 'wallet v4 r1':
                return WalletVersion::V4R1;
            case 'wallet v3 r2':
                return WalletVersion::V3R2;
            case 'wallet v3 r1':
                return WalletVersion::V3R1;
            default:
                throw new \RuntimeException('Unknown wallet version');
        }
    }

    /**
     * @param Uint8Array $publicKey
     * @param WalletVersion $version
     * @return AbstractWallet
     */
    public function createWalletByVersion(Uint8Array $publicKey, WalletVersion $version): AbstractWallet
    {
        $workchain = $this->network->workchain;

        switch ($version) {
            case WalletVersion::V3R1:
                return new WalletV3R1(new WalletV3Options($publicKey, workchain: $workchain));
            case WalletVersion::V3R2:
                return new WalletV3R2(new WalletV3Options($publicKey, workchain: $workchain));
            case WalletVersion::V4R1:
                return new WalletV4R1(new WalletV4Options($publicKey, workchain: $workchain));
            case WalletVersion::V4R2:
                return new WalletV4R2(new WalletV4Options($publicKey, workchain: $workchain));
            case WalletVersion::V5_BETA:
                return new WalletV5Beta(new WalletV5Options($publicKey, workchain: $workchain, walletId: new WalletId(
                    $this->net,
                    workchain: $workchain,
                )));
            case WalletVersion::V5R1:
                throw new \RuntimeException('Use createWalletV5 method');
        }
    }

    /**
     * @param Uint8Array $publicKey
     * @return WalletV4R2
     */
    public function createWalletV4(Uint8Array $publicKey): WalletV4R2
    {
        return new WalletV4R2(new WalletV4Options($publicKey, workchain: $this->network->workchain));
    }

    /**
     * @param Uint8Array $publicKey
     * @return WalletV5Beta
     */
    public function createWalletV5Beta(Uint8Array $publicKey): WalletV5Beta
    {
        return new WalletV5Beta(new WalletV5Options($publicKey, workchain: $this->network->workchain));
    }


    /**
     * Find transaction hash by body hash
     * @param string $hash - Body hash
     * @return string - Transaction hash
     */
    public function findTxHashByBodyHash(string $hash): string
    {
        static $count = 0;
        $res = $this->client->get('messages', ['body_hash' => $hash]);
        if (isset($res?->messages[0])) {
            return Bytes::bytesToHexString(Bytes::base64ToBytes($res->messages[0]->in_msg_tx_hash));
        } else {
            sleep(1);
            if ($count < 30) {
                $count++;
                return $this->findTxHashByBodyHash($hash);
            } else {
                throw new \RuntimeException('Transaction not found');
            }
        }
    }

    /**
     * Find transaction hash by message hash
     * @param string $hash - Message hash
     * @return string - Transaction hash
     */
    public function findTxHashByMessageHash(string $hash): string
    {
        static $count = 0;
        $res = $this->client->get('messages', ['msg_hash' => $hash]);
        if (isset($res?->messages[0])) {
            return Bytes::bytesToHexString(Bytes::base64ToBytes($res->messages[0]->in_msg_tx_hash));
        } else {
            sleep(1);
            if ($count < 30) {
                $count++;
                return $this->findTxHashByMessageHash($hash);
            } else {
                throw new \RuntimeException('Transaction not found');
            }
        }
    }
}
