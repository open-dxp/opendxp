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

namespace OpenDxp\Model\DataObject\ClassDefinition;

use OpenDxp\Model\DataObject\ClassDefinition\Data\UrlSlug;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Site;

final readonly class UrlSlugContext
{
    public function __construct(
        public Concrete $object,
        public UrlSlug $fieldDefinition,
        public ?string $language,
        public ?Site $site,
    ) {
    }
}
