<?php

declare(strict_types=1);

namespace OpenDxp\Test;

use OpenDxp\Config;

final class Settings
{
    /**
     * @param array<string, mixed> $settings
     */
    public static function override(array $settings): void
    {
        Config::setSystemConfiguration(array_replace_recursive(Config::getSystemConfiguration(), $settings));
    }
}
