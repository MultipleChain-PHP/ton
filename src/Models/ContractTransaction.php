<?php

declare(strict_types=1);

namespace MultipleChain\TON\Models;

use MultipleChain\TON\Address;
use MultipleChain\Interfaces\Models\ContractTransactionInterface;

class ContractTransaction extends Transaction implements ContractTransactionInterface
{
    /**
     * @return string
     */
    public function getAddress(): string
    {
        $data = $this->getData();
        $source = $data?->action->details->asset ?? '';
        return Address::parse($source)->toStringContract($this->provider->isTestnet());
    }
}
