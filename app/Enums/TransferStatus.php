<?php

namespace App\Enums;

enum TransferStatus: string
{
    case ACTIVE = 'active';
    case PROCESSING = 'processing';
    case SOLD = 'sold';
    case CANCELED = 'canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function availableForPurchase(): array
    {
        return [self::ACTIVE->value];
    }

    public static function inProgress(): array
    {
        return [self::ACTIVE->value, self::PROCESSING->value];
    }

    public static function completed(): array
    {
        return [self::SOLD->value, self::CANCELED->value];
    }

    public function isAvailableForPurchase(): bool
    {
        return $this === self::ACTIVE;
    }

    public function isInProgress(): bool
    {
        return in_array($this, [self::ACTIVE, self::PROCESSING]);
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::SOLD, self::CANCELED]);
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::PROCESSING => 'Processing',
            self::SOLD => 'Sold',
            self::CANCELED => 'Canceled',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ACTIVE => 'Transfer listing is active and available for purchase',
            self::PROCESSING => 'Transfer is being processed',
            self::SOLD => 'Transfer has been completed successfully',
            self::CANCELED => 'Transfer has been canceled',
        };
    }
}
