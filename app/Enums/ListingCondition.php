<?php

namespace App\Enums;

enum ListingCondition: string
{
    case NEW = 'new';
    case LIKE_NEW = 'like_new';
    case FAIR = 'fair';
    case GOOD = 'good';
}
