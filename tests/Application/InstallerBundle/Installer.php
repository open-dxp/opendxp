<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\InstallerBundle;

use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Override;

final class Installer extends SettingsStoreAwareInstaller
{
    #[Override]
    public function install(): void
    {
        $this->markMigrationsAsExecuted();

        parent::install();
    }

    #[Override]
    public function uninstall(): void
    {
        $this->markMigrationsAsNotExecuted();

        parent::uninstall();
    }
}
