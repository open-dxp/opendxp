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

namespace OpenDxp\Event;

final class VersionEvents
{
    /**
     * @Event("OpenDxp\Event\Model\VersionEvent")
     */
    const string PRE_SAVE = 'opendxp.version.preSave';

    /**
     * @Event("OpenDxp\Event\Model\VersionEvent")
     */
    const string POST_SAVE = 'opendxp.version.postSave';

    /**
     * @Event("OpenDxp\Event\Model\VersionEvent")
     */
    const string PRE_DELETE = 'opendxp.version.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\VersionEvent")
     */
    const string POST_DELETE = 'opendxp.version.postDelete';
}
