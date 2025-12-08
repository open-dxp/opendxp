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

namespace OpenDxp\Model\Document\Hardlink;

use Exception;
use OpenDxp\Model\Document;
use OpenDxp\Tool\Serialize;

class Service
{
    /**
     * @throws Exception
     */
    public static function wrap(Document $doc): Wrapper\WrapperInterface|Wrapper\Hardlink|null
    {
        if ($doc instanceof Document\Hardlink) {
            if ($sourceDoc = $doc->getSourceDocument()) {
                /** @var Document\Hardlink\Wrapper\Hardlink $destDoc */
                $destDoc = self::upperCastDocument($sourceDoc);
                $destDoc->setKey($doc->getKey());
                $destDoc->setPath($doc->getRealPath());
                $destDoc->initDao($sourceDoc::class, true);
                $destDoc->setHardLinkSource($doc);
                $destDoc->setSourceDocument($sourceDoc);

                return $destDoc;
            }
        } else {
            $destDoc = self::upperCastDocument($doc);
            $destDoc->initDao($doc::class, true);
            $destDoc->setSourceDocument($doc);

            return $destDoc;
        }

        return null;
    }

    /**
     * @internal
     */
    public static function upperCastDocument(Document $doc): Wrapper\WrapperInterface
    {
        $to_class = 'OpenDxp\\Model\\Document\\Hardlink\\Wrapper\\' . ucfirst($doc->getType());

        $old_serialized_prefix = 'O:'.strlen($doc::class);
        $old_serialized_prefix .= ':"'.$doc::class.'":';

        // unset eventually existing children, because of performance reasons when serializing the document
        $doc->setChildren(null);

        $old_serialized_object = Serialize::serialize($doc);
        $new_serialized_object = 'O:'.strlen($to_class).':"'.$to_class . '":';
        $new_serialized_object .= substr($old_serialized_object, strlen($old_serialized_prefix));

        return Serialize::unserialize($new_serialized_object);
    }

    /**
     * @throws Exception
     *
     * @internal
     *
     * this is used to get children below a hardlink by a path
     * for example: the requested path is /de/service/contact but /de/service is a hardlink to /en/service
     * then $hardlink would be /en/service and $path /de/service/contact and this function returns then /en/service/contact
     */
    public static function getChildByPath(Document\Hardlink $hardlink, string $path): Wrapper\WrapperInterface|Wrapper\Hardlink|null
    {
        if ($hardlink->getChildrenFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace('@^' . preg_quote($hardlink->getRealFullPath(), '@') . '@', $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $hardLinkedDocument = Document::getByPath($hardlinkRealPath);
            if ($hardLinkedDocument instanceof Document) {
                $hardLinkedDocument = self::wrap($hardLinkedDocument);
                $hardLinkedDocument->setHardLinkSource($hardlink);

                $_path = $path !== '/' ? dirname($path) : $path;
                $_path = str_replace('\\', '/', $_path); // windows patch
                $_path .= $_path !== '/' ? '/' : '';

                $hardLinkedDocument->setPath($_path);

                return $hardLinkedDocument;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    public static function getNearestChildByPath(Document\Hardlink $hardlink, string $path): Wrapper\WrapperInterface|Wrapper\Hardlink|null
    {
        if ($hardlink->getChildrenFromSource() && $hardlink->getSourceDocument()) {
            $hardlinkRealPath = preg_replace('@^' . preg_quote($hardlink->getRealFullPath(), '@') . '@', $hardlink->getSourceDocument()->getRealFullPath(), $path);
            $pathes = [];

            $pathes[] = '/';
            $pathParts = explode('/', $hardlinkRealPath);
            $tmpPathes = [];
            foreach ($pathParts as $pathPart) {
                $tmpPathes[] = $pathPart;
                $t = implode('/', $tmpPathes);
                $pathes[] = $t;
            }

            $pathes = array_reverse($pathes);

            foreach ($pathes as $p) {
                $hardLinkedDocument = Document::getByPath($p);
                if ($hardLinkedDocument instanceof Document) {
                    $hardLinkedDocument = self::wrap($hardLinkedDocument);
                    $hardLinkedDocument->setHardLinkSource($hardlink);

                    $_path = $path !== '/' ? dirname($p) : $p;
                    $_path = str_replace('\\', '/', $_path); // windows patch
                    $_path .= $_path !== '/' ? '/' : '';

                    $_path = preg_replace('@^' . preg_quote($hardlink->getSourceDocument()->getRealPath(), '@') . '@', $hardlink->getRealPath(), $_path);

                    $hardLinkedDocument->setPath($_path);

                    return $hardLinkedDocument;
                }
            }
        }

        return null;
    }
}
