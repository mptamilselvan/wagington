<?php

namespace App\Enums;

enum RoomBookingStatusEnum: string
{
    case PENDING='pending';
    case CONFIRMED='confirmed';
    case CANCELLED='cancelled';
    case COMPLETED='completed';
}
    