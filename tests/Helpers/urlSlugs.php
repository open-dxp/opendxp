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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

use OpenDxp\Model\DataObject\ClassDefinition\Data\Localizedfields;
use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug as UrlSlugField;
use OpenDxp\Model\DataObject\Data\UrlSlug;

/**
 * @param UrlSlug[]|null $slugs
 *
 * @return array<int, string>
 */
function slugsBySite(?array $slugs): array
{
    $bySite = [];

    foreach ($slugs ?? [] as $slug) {
        $bySite[$slug->getSiteId() ?? 0] = $slug->getSlug();
    }

    ksort($bySite);

    return $bySite;
}

function slugField(?string $generator): UrlSlugField
{
    $field = new UrlSlugField();
    $field->setName('slug');
    $field->setSlugGeneratorClass($generator);

    return $field;
}

function localizedFieldsWith(UrlSlugField $field): Localizedfields
{
    $localizedFields = new Localizedfields();
    $localizedFields->setName('localizedfields');
    $localizedFields->addChild($field);

    return $localizedFields;
}
