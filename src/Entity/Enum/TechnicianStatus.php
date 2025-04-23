<?php


namespace App\Entity\Enum;

enum TechnicianStatus: string
{
    case AVAILABLE = 'available'; // 0-1 tickets
    case ACTIVE = 'active';       // 2 tickets
    case BUSY = 'busy';           // 3+ tickets
}