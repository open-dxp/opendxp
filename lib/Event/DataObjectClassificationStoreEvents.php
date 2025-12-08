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

final class DataObjectClassificationStoreEvents
{
    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     */
    const string COLLECTION_CONFIG_PRE_ADD = 'opendxp.dataobject.classificationstore.collectionConfig.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     */
    const string COLLECTION_CONFIG_POST_ADD = 'opendxp.dataobject.classificationstore.collectionConfig.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     */
    const string COLLECTION_CONFIG_PRE_UPDATE = 'opendxp.dataobject.classificationstore.collectionConfig.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     */
    const string COLLECTION_CONFIG_POST_UPDATE = 'opendxp.dataobject.classificationstore.collectionConfig.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     */
    const string COLLECTION_CONFIG_PRE_DELETE = 'opendxp.dataobject.classificationstore.collectionConfig.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\CollectionConfigEvent")
     */
    const string COLLECTION_CONFIG_POST_DELETE = 'opendxp.dataobject.classificationstore.collectionConfig.postDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     */
    const string GROUP_CONFIG_PRE_ADD = 'opendxp.dataobject.classificationstore.groupConfig.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     */
    const string GROUP_CONFIG_POST_ADD = 'opendxp.dataobject.classificationstore.groupConfig.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     */
    const string GROUP_CONFIG_PRE_UPDATE = 'opendxp.dataobject.classificationstore.groupConfig.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     */
    const string GROUP_CONFIG_POST_UPDATE = 'opendxp.dataobject.classificationstore.groupConfig.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     */
    const string GROUP_CONFIG_PRE_DELETE = 'opendxp.dataobject.classificationstore.groupConfig.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\GroupConfigEvent")
     */
    const string GROUP_CONFIG_POST_DELETE = 'opendxp.dataobject.classificationstore.groupConfig.postDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     */
    const string KEY_CONFIG_PRE_ADD = 'opendxp.dataobject.classificationstore.keyConfig.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     */
    const string KEY_CONFIG_POST_ADD = 'opendxp.dataobject.classificationstore.keyConfig.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     */
    const string KEY_CONFIG_PRE_UPDATE = 'opendxp.dataobject.classificationstore.keyConfig.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     */
    const string KEY_CONFIG_POST_UPDATE = 'opendxp.dataobject.classificationstore.keyConfig.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     */
    const string KEY_CONFIG_PRE_DELETE = 'opendxp.dataobject.classificationstore.keyConfig.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\KeyConfigEvent")
     */
    const string KEY_CONFIG_POST_DELETE = 'opendxp.dataobject.classificationstore.keyConfig.postDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     */
    const string STORE_CONFIG_PRE_ADD = 'opendxp.dataobject.classificationstore.storeConfig.preAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     */
    const string STORE_CONFIG_POST_ADD = 'opendxp.dataobject.classificationstore.storeConfig.postAdd';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     */
    const string STORE_CONFIG_PRE_UPDATE = 'opendxp.dataobject.classificationstore.storeConfig.preUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     */
    const string STORE_CONFIG_POST_UPDATE = 'opendxp.dataobject.classificationstore.storeConfig.postUpdate';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     */
    const string STORE_CONFIG_PRE_DELETE = 'opendxp.dataobject.classificationstore.storeConfig.preDelete';

    /**
     * @Event("OpenDxp\Event\Model\DataObject\ClassificationStore\StoreConfigEvent")
     */
    const string STORE_CONFIG_POST_DELETE = 'opendxp.dataobject.classificationstore.storeConfig.postDelete';
}
