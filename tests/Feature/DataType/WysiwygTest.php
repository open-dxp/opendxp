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

namespace OpenDxp\Tests\Feature\DataType;

use OpenDxp\Model\DataObject\ClassDefinition\Data\Wysiwyg;

it('keeps the alignment of a heading and the size of an image', function () {
    $html = '<h2 style="text-align: center;">Heading</h2><p><img src="/image.jpg" width="200" height="100" /></p>';

    expect((new Wysiwyg())->getDataForResource($html))->toBe($html);
});
