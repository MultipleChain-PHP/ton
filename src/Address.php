<?php

declare(strict_types=1);

namespace MultipleChain\TON;

use Olifanton\Interop\Address as InteropAddress;

class Address extends InteropAddress
{
    /**
     * @param string $raw
     * @return Address
     */
    public static function parse(string $raw): Address
    {
        return new Address($raw);
    }

    /**
     * @param bool $testnet
     * @return string
     */
    public function toStringWallet(bool $testnet): string
    {
        return $this->toString(true, true, false, $testnet);
    }

    /**
     * @param bool $testnet
     * @return string
     */
    public function toStringContract(bool $testnet): string
    {
        return $this->toString(true, true, true, $testnet);
    }
}
