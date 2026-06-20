<?php

namespace App\Enums;

enum ListingStatus: string
{
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case SOLD = 'sold';
    case REMOVED = 'removed';
}
