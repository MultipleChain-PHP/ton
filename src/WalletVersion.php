<?php

declare(strict_types=1);

namespace MultipleChain\TON;

enum WalletVersion: int
{
    case V3R1 = 0;
    case V3R2 = 1;
    case V4R1 = 2;
    case V4R2 = 3;
    case V5_BETA = 4;
    case V5R1 = 5;
}
