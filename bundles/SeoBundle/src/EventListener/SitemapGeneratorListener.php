<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use IteratorAggregate;
use OpenDxp\Bundle\SeoBundle\OpenDxpSeoBundle;
use OpenDxp\Bundle\SeoBundle\Sitemap\GeneratorInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SitemapGeneratorListener implements EventSubscriberInterface
{
    public function __construct(
        /**
         * @var IteratorAggregate|GeneratorInterface[]
         */
        private readonly array|IteratorAggregate $generators
    )
    {
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => 'onPopulateSitemap',
        ];
    }

    public function onPopulateSitemap(SitemapPopulateEvent $event): void
    {
        if (!OpenDxpSeoBundle::isInstalled()) {
            return;
        }

        $container = $event->getUrlContainer();
        $section = $event->getSection();

        foreach ($this->generators as $generator) {
            $generator->populate($container, $section);
        }
    }
}
