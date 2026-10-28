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

namespace OpenDxp\Tests\Feature\ClassDefinition;

use OpenDxp\Model\DataObject\Fieldcollection;
use OpenDxp\Model\DataObject\Objectbrick;

afterEach(fn () => unlink(OPENDXP_CLASS_DEFINITION_DIRECTORY . '/outside.php'));

dataset('definitions', [
    'a field collection' => [Fieldcollection\Definition::class],
    'an object brick' => [Objectbrick\Definition::class],
]);

it('loads no file outside of the definition directory', function (string $definitionClass) {
    file_put_contents(
        OPENDXP_CLASS_DEFINITION_DIRECTORY . '/outside.php',
        sprintf('<?php return new \%s();', $definitionClass),
    );

    $definition = $definitionClass::getByKey('../outside');

    expect($definition)->toBeNull();
})->with('definitions');
