<?php

namespace App\Enums;

enum TransactionStatus: string
{
    //'initiated', 'held', 'released', 'refunded', 'failed'
    case INITIATED = 'initiated';
    case HELD = 'held';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';
}
