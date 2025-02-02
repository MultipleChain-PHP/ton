<?php

declare(strict_types=1);

namespace MultipleChain\TON\Tests;

use PHPUnit\Framework\TestCase;
use MultipleChain\TON\Provider;

class BaseTest extends TestCase
{
    /**
     * @var Provider
     */
    protected Provider $provider;

    /**
     * @var object
     */
    protected object $data;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->data = json_decode(file_get_contents(__DIR__ . '/data.json'));

        $this->provider = new Provider([
            'testnet' => true,
            'apiKey' => $this->data->apiKey,
        ]);
    }

    /**
     * @return void
     */
    public function testExample(): void
    {
        $this->assertTrue(true);
    }
}
