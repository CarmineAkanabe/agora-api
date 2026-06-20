<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case PAID = 'paid';
    case COMPLETED = 'completed';
    case DISPUTED = 'disputed';
    case CANCELLED = 'cancelled';
}
