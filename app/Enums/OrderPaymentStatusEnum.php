<?php

namespace App\Enums;


enum OrderPaymentStatusEnum: string
{   
    case PROCESSING='processing';
    case COMPLETED='completed';
    case FAILED='failed';
    case PENDING='pending';
    case REFUNDED='refunded';
    case PARTIALLY_REFUNDED='partially_refunded';
    
}

