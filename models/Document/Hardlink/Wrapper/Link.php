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

namespace OpenDxp\Model\Document\Hardlink\Wrapper;

use OpenDxp\Model;

/**
 * @method \OpenDxp\Model\Document\Hardlink\Dao getDao()
 */
class Link extends Model\Document\Link implements Model\Document\Hardlink\Wrapper\WrapperInterface
{
    use Model\Document\Hardlink\Wrapper;

    #[\Override]
    public function getHref(): string
    {
        if ($this->getLinktype() === 'internal' && $this->getInternalType() === 'document') {
            $element = $this->getElement();
            if (
                $element instanceof Model\Document &&
                (
                    str_starts_with($element->getRealFullPath(), $this->getHardLinkSource()->getSourceDocument()->getRealFullPath() . '/') ||
                    $this->getHardLinkSource()->getSourceDocument()->getRealFullPath() === $element->getRealFullPath()
                )
            ) {
                // link target is child of hardlink source
                $c = Model\Document\Hardlink\Service::wrap($element);
                if ($c instanceof WrapperInterface) {
                    $hardLink = $this->getHardLinkSource();
                    $c->setHardLinkSource($hardLink);

                    if ($hardLink->getSourceDocument()->getRealFullpath() === $c->getRealFullPath()) {
                        $c->setPath($hardLink->getPath());
                        $c->setKey($hardLink->getKey());
                    } else {
                        $c->setPath(preg_replace('@^' . preg_quote($hardLink->getSourceDocument()->getRealFullpath(), '@') . '@', $hardLink->getRealFullpath(), $c->getRealPath()));
                    }

                    $this->setElement($c);
                }
            }
        }

        return parent::getHref();
    }
}
