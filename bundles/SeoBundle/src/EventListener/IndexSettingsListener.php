<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use OpenDxp\Bundle\AdminBundle\Event\IndexActionSettingsEvent;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;

/**
 * @internal
 */
final class IndexSettingsListener
{
    public function __construct(private readonly SiteDeclarationRegistry $registry)
    {
    }

    public function indexSettings(IndexActionSettingsEvent $settingsEvent): void
    {
        $declarations = [];

        foreach ($this->registry->getDeclarations() as $declaration) {
            $declarations[] = [
                'name' => $declaration->getName(),
                'path' => $declaration->getPath(),
            ];
        }

        $settingsEvent->addSetting('site_declarations', $declarations);
    }
}
