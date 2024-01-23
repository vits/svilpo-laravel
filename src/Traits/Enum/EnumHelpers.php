<?php

declare(strict_types=1);

namespace Vits\Svilpo\Traits\Enum;

trait EnumHelpers
{
    public function label(): string
    {
        return strval($this->value);
    }

    public static function options(): array
    {
        return array_map(
            fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            self::cases()
        );
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
