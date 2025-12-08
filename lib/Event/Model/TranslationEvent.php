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

namespace OpenDxp\Event\Model;

use OpenDxp\Event\Traits\ArgumentsAwareTrait;
use OpenDxp\Model\Translation;
use Symfony\Contracts\EventDispatcher\Event;

class TranslationEvent extends Event
{
    use ArgumentsAwareTrait;

    /**
     * AssetEvent constructor.
     *
     * @param array $arguments additional parameters (e.g. "versionNote" for the version note)
     */
    public function __construct(protected Translation $translation, array $arguments = [])
    {
        $this->arguments = $arguments;
    }

    public function getTranslation(): Translation
    {
        return $this->translation;
    }

    public function setTranslation(Translation $translation): void
    {
        $this->translation = $translation;
    }
}
