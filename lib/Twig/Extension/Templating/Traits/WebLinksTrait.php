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

namespace OpenDxp\Twig\Extension\Templating\Traits;

use stdClass;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;

/**
 * @internal
 */
trait WebLinksTrait
{
    protected WebLinkExtension $webLinkExtension;

    /**
     * Whether to use WebLinks (HTTP/2 push) for every item. Can be
     * overridden on an item level.
     *
     */
    protected bool $webLinksEnabled = false;

    public function webLinksEnabled(?bool $enabled = null): bool
    {
        if (null !== $enabled) {
            $this->webLinksEnabled = $enabled;
        }

        return $this->webLinksEnabled;
    }

    public function enableWebLinks(): static
    {
        $this->webLinksEnabled(true);

        return $this;
    }

    public function getWebLinkAttributes(): array
    {
        return $this->webLinkAttributes;
    }

    public function setWebLinkAttributes(array $webLinkAttributes): void
    {
        $this->webLinkAttributes = $webLinkAttributes;
    }

    protected function handleWebLink(stdClass $item, string $source, array $itemAttributes): void
    {
        if (empty($source)) {
            return;
        }

        if (!$this->webLinksEnabled && !isset($itemAttributes['webLink'])) {
            return;
        }

        $attributes = $this->webLinkAttributes;
        if (isset($itemAttributes['webLink'])) {
            if (is_bool($itemAttributes['webLink'])) {
                // set webLink to false to disable webLink on the item level. this allows to
                // enable web links for the whole helper while disabling them for individual items
                if (!$itemAttributes['webLink']) {
                    return;
                }
                $itemAttributes['webLink'] = [];
            }

            $attributes = [...$attributes, ...$itemAttributes['webLink']];
        }

        $method = 'preload';
        if (isset($attributes['method'])) {
            $method = $attributes['method'];
            unset($attributes['method']);
        }

        call_user_func([$this->webLinkExtension, $method], $source, $attributes);
    }
}
