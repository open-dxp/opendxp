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

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\DataObject\Data\UrlSlug;
use OpenDxp\Model\DataObject\TestArticle;

/**
 * @extends AbstractDataObjectFactory<TestArticle>
 */
final class TestArticleFactory extends AbstractDataObjectFactory
{
    public static function class(): string
    {
        return TestArticle::class;
    }

    public function withLocalizedSlug(string $language, string $slug): static
    {
        return $this->withLocalizedValues('localizedSlug', [$language => [new UrlSlug($slug)]]);
    }
}
