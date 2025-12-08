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

namespace OpenDxp\Bundle\XliffBundle\AttributeSet;

class Attribute
{
    const TYPE_PROPERTY = 'property';

    const TYPE_TAG = 'tag';

    const TYPE_SETTINGS = 'settings';

    const TYPE_LOCALIZED_FIELD = 'localizedfield';

    const TYPE_BRICK_LOCALIZED_FIELD = 'localizedbrick';

    const TYPE_BLOCK = 'block';

    const TYPE_BLOCK_IN_LOCALIZED_FIELD = 'blockinlocalizedfield';

    const TYPE_BLOCK_IN_LOCALIZED_FIELD_COLLECTION = 'blockinlocalizedfieldcollection';

    const TYPE_FIELD_COLLECTION_LOCALIZED_FIELD = 'localizedfieldcollection';

    const TYPE_ELEMENT_KEY = 'key';

    /**
     * DataExtractorResultAttribute constructor.
     *
     * @param string[] $targetContent
     */
    public function __construct(private readonly string $type, private readonly string $name, private readonly string $content, private readonly bool $isReadonly = false, private readonly array $targetContent = [])
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string[]
     */
    public function getTargetContent(): array
    {
        return $this->targetContent;
    }

    /**
     * Readonly attributes should not be translated - relevant for information purposes only.
     *
     */
    public function isReadonly(): bool
    {
        return $this->isReadonly;
    }
}
