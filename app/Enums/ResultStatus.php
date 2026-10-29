<?php

namespace App\Enums;

enum ResultStatus: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case DISPUTED = 'disputed';
}