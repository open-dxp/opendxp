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

namespace OpenDxp\Bundle\XliffBundle\Event;

final class XliffEvents
{
    /**
     * @Event("OpenDxp\Bundle\XliffBundle\Event\Model\TranslationXliffEvent")
     */
    const string XLIFF_ATTRIBUTE_SET_EXPORT = 'opendxp.translation.xliff.attribute_set_export';

    /**
     * @Event("OpenDxp\Bundle\XliffBundle\Event\Model\TranslationXliffEvent")
     */
    const string XLIFF_ATTRIBUTE_SET_IMPORT = 'opendxp.translation.xliff.attribute_set_import';
}
