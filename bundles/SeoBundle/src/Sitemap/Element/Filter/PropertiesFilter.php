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

namespace OpenDxp\Bundle\SeoBundle\Sitemap\Element\Filter;

use OpenDxp\Bundle\SeoBundle\Sitemap\Element\FilterInterface;
use OpenDxp\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use OpenDxp\Model\Element\ElementInterface;

/**
 * Filters element based on the sitemaps_exclude and sitemaps_exclude_children properties.
 */
class PropertiesFilter implements FilterInterface
{
    const PROPERTY_EXCLUDE = 'sitemaps_exclude';

    const PROPERTY_EXCLUDE_CHILDREN = 'sitemaps_exclude_children';

    public function canBeAdded(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        return !$this->getBoolProperty($element, self::PROPERTY_EXCLUDE);
    }

    public function handlesChildren(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        return !$this->getBoolProperty($element, self::PROPERTY_EXCLUDE_CHILDREN);
    }

    private function getBoolProperty(ElementInterface $document, string $property): bool
    {
        if (!$document->hasProperty($property)) {
            return false;
        }

        return (bool)$document->getProperty($property);
    }
}
