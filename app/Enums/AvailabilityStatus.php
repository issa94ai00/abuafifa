<?php

namespace App\Enums;

enum AvailabilityStatus: string
{
    case Available = 'available';
    case SoldOut = 'sold_out';
    case OnOrder = 'on_order';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'متوفر',
            self::SoldOut => 'نفدت الكمية',
            self::OnOrder => 'يُطلب مسبقاً',
        };
    }
}