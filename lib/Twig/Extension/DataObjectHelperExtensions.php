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

namespace OpenDxp\Twig\Extension;

use OpenDxp\Model\DataObject;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @internal
 */
class DataObjectHelperExtensions extends AbstractExtension
{
    #[\Override]
    public function getTests(): array
    {
        return [
            new TwigTest('opendxp_data_object', static fn($object) => $object instanceof DataObject\Concrete),
            new TwigTest('opendxp_data_object_folder', static fn($object) => $object instanceof DataObject\Folder),
            new TwigTest('opendxp_data_object_class', static function ($object, $className) {
                $className = ucfirst($className);
                $className = 'OpenDxp\\Model\\DataObject\\' . $className;

                return class_exists($className) && $object instanceof $className;
            }),
            new TwigTest('opendxp_data_object_gallery', static fn($object) => $object instanceof DataObject\Data\ImageGallery),
            new TwigTest('opendxp_data_object_hotspot_image', static fn($object) => $object instanceof DataObject\Data\Hotspotimage),
        ];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('opendxp_data_object_select_options', static fn($object, $field) => DataObject\Service::getOptionsForSelectField($object, $field)),
        ];
    }
}
