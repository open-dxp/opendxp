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

final class DataObjectEvents
{
    /**
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string PRE_ADD = 'opendxp.dataobject.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_ADD = 'opendxp.dataobject.postAdd';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_ADD_FAILURE = 'opendxp.dataobject.postAddFailure';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string PRE_UPDATE = 'opendxp.dataobject.preUpdate';

    /**
     * Arguments:
     *  - validationExceptions | ValidationException[] | Validation exceptions from field definition validation.
     *  - message | string | Prefix before validation messages. Defaults to 'Validation failed: '.
     *  - separator | string | Separator between validation messages. Defaults to ' / '.
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string PRE_UPDATE_VALIDATION_EXCEPTION = 'opendxp.dataobject.preUpdateValidationException';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - oldPath | the old full path in case the path has changed
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_UPDATE = 'opendxp.dataobject.postUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_UPDATE_FAILURE = 'opendxp.dataobject.postUpdateFailure';

    /**
     * @Event("OpenDxp\Event\Model\DataObjectDeleteInfoEvent")
     */
    const string DELETE_INFO = 'opendxp.dataobject.deleteInfo';

    /**
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string PRE_DELETE = 'opendxp.dataobject.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_DELETE = 'opendxp.dataobject.postDelete';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_DELETE_FAILURE = 'opendxp.dataobject.postDeleteFailure';

    /**
     * Arguments:
     *  - params | array | contains the values that were passed to getById() as the second parameter
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_LOAD = 'opendxp.dataobject.postLoad';

    /**
     * Arguments:
     *  - target_element | OpenDxp\Model\AbstractObject | contains the target object used in copying process
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string PRE_COPY = 'opendxp.dataobject.preCopy';

    /**
     * Arguments:
     *  - base_element | OpenDxp\Model\AbstractObject | contains the base object used in copying process
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_COPY = 'opendxp.dataobject.postCopy';

    /**
     * Arguments:
     *  - objectData | array | contains the export data of the object
     *  - context | array | context information - default ['source' => 'opendxp-export']
     *  - requestedLanguage | string | requested language
     *  - helperDefinitions | array | containing the column definition from the grid view
     *  - localeService | \OpenDxp\Localization\LocaleService
     *  - returnMappedFieldNames | bool | if "true" the objectData is an associative array, otherwise it is an indexed array
     *
     * @Event("OpenDxp\Event\Model\DataObjectEvent")
     */
    const string POST_CSV_ITEM_EXPORT = 'opendxp.dataobject.postCsvItemExport';
}
