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

namespace OpenDxp\Bundle\SeoBundle\Sitemap\Document;

use InvalidArgumentException;
use OpenDxp\Bundle\SeoBundle\Sitemap\Element\GeneratorContext;
use OpenDxp\Model\Site;
use Presta\SitemapBundle\Service\UrlContainerInterface;

class DocumentGeneratorContext extends GeneratorContext
{
    public function __construct(
        UrlContainerInterface $urlContainer,
        ?string $section = null,
        ?Site $site = null,
        array $parameters = []
    ) {
        if ($site instanceof \OpenDxp\Model\Site) {
            $parameters['site'] = $site;
        }

        if (isset($parameters['site']) && !$parameters['site'] instanceof Site) {
            throw new InvalidArgumentException(sprintf('Site parameter must be an instance of %s', Site::class));
        }

        parent::__construct($urlContainer, $section, $parameters);
    }

    public function hasSite(): bool
    {
        return $this->has('site');
    }

    public function getSite(): ?Site
    {
        return $this->get('site');
    }
}
