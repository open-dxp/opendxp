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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Event;

final class UrlSlugEvents
{
    /**
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    public const string POST_SAVE = 'opendxp.model.dataobject.classdefinition.urlslug.postsave';

    /**
     * Narrows the sites a slug of a language can belong to, or takes the language out of the field.
     *
     * @Event("OpenDxp\Event\Model\DataObject\ClassDefinition\UrlSlugAvailableSitesEvent")
     */
    public const string AVAILABLE_SITES = 'opendxp.model.dataobject.classdefinition.urlslug.availablesites';
}
