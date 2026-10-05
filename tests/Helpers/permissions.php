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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\User;

/**
 * Writes the search entry an element needs to be found by the backend search. Nothing writes one
 * outside a request, so a test that searches has to.
 */
function indexed(ElementInterface $element): ElementInterface
{
    (new Search\Backend\Data($element))->save();

    return $element;
}

function assetWorkspace(string $path, array $rules): User\Workspace\Asset
{
    $element = Asset::getByPath($path);

    return (new User\Workspace\Asset())->setValues([
        'cId' => $element->getId(),
        'cPath' => $element->getRealFullPath(),
        ...$rules,
    ]);
}

function documentWorkspace(string $path, array $rules): User\Workspace\Document
{
    $element = Document::getByPath($path);

    return (new User\Workspace\Document())->setValues([
        'cId' => $element->getId(),
        'cPath' => $element->getRealFullPath(),
        ...$rules,
    ]);
}

function objectWorkspace(string $path, array $rules): User\Workspace\DataObject
{
    $element = DataObject::getByPath($path);

    return (new User\Workspace\DataObject())->setValues([
        'cId' => $element->getId(),
        'cPath' => $element->getRealFullPath(),
        ...$rules,
    ]);
}
