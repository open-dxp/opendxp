<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Application\InstallerBundle;

use OpenDxp\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Override;

final class SchemaInstaller extends SettingsStoreAwareInstaller
{
    #[Override]
    public function install(): void
    {
        $this->updateEntitySchema();

        parent::install();
    }
}
