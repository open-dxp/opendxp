<?php

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

namespace OpenDxp\Bundle\CoreBundle\Controller;

use Exception;
use OpenDxp\Controller\Controller;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use PDO;

/**
 * @internal
 */
class WebDavController extends Controller
{
    public function webdavAction(): void
    {
        $homeDir = Asset::getById(1);

        try {
            $publicDir = new Asset\WebDAV\Folder($homeDir);
            $objectTree = new Asset\WebDAV\Tree($publicDir);
            $server = new \Sabre\DAV\Server($objectTree);
            $server->setBaseUri($this->generateUrl('opendxp_webdav', ['path' => '/']));

            // lock plugin
            /** @var PDO $pdo */
            $pdo = \OpenDxp\Db::get()->getNativeConnection();
            $lockBackend = new \Sabre\DAV\Locks\Backend\PDO($pdo);
            $lockBackend->tableName = 'webdav_locks';

            $lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
            $server->addPlugin($lockPlugin);

            // browser plugin
            $server->addPlugin(new \Sabre\DAV\Browser\Plugin());

            $server->start();
        } catch (Exception $e) {
            Logger::error((string)$e);
        }

        exit;
    }
}
