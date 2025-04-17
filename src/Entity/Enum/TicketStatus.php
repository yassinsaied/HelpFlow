<?php

namespace App\Entity\Enum;

enum TicketStatus: string
{
    case NEW = 'NEW';
    case ASSIGNED = 'ASSIGNED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
}