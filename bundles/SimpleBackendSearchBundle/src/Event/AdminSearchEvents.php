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

namespace OpenDxp\Bundle\SimpleBackendSearchBundle\Event;

final class AdminSearchEvents
{
    /**
     * Fired before the request params are parsed.
     *
     * Subject:\OpenDxp\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - requestParams | contains the request parameters
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string SEARCH_LIST_BEFORE_FILTER_PREPARE = 'opendxp.admin.search.list.beforeFilterPrepare';

    /**
     * Allows you to modify the search backend list before it is loaded.
     *
     * Subject:\OpenDxp\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | the search backend list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string SEARCH_LIST_BEFORE_LIST_LOAD = 'opendxp.admin.search.list.beforeListLoad';

    /**
     * Allows you to modify the the result after the list was loaded.
     *
     * Subject:\OpenDxp\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | raw result as an array
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string SEARCH_LIST_AFTER_LIST_LOAD = 'opendxp.admin.search.list.afterListLoad';

    /**
     * Allows you to modify the search backend list before it is loaded.
     *
     * Subject:\OpenDxp\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | the search backend list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string QUICKSEARCH_LIST_BEFORE_LIST_LOAD = 'opendxp.admin.quickSearch.list.beforeListLoad';

    /**
     * Allows you to modify the the result after the list was loaded.
     *
     * Subject:\OpenDxp\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | raw result as an array
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     */
    const string QUICKSEARCH_LIST_AFTER_LIST_LOAD = 'opendxp.admin.quickSearch.list.afterListLoad';
}
