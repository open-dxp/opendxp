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

namespace OpenDxp\Tests\Feature\LazyLoading;

use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Tests\Factory\LazyLoadingFactory;
use OpenDxp\Tests\Factory\RelationTestFactory;

beforeEach(function () {
    $relations = array_map(
        static fn (Concrete $target) => new ObjectMetadata('advancedObjects', ['metadataUpper'], $target),
        RelationTestFactory::createMany(3),
    );
    $this->object = LazyLoadingFactory::createOne(['advancedObjects' => $relations]);
});

it('keeps an advanced relation of a loaded object clean', function () {
    $loaded = reloaded($this->object);

    expect($loaded->isFieldDirty('advancedObjects'))->toBeFalse();
});

it('marks an advanced relation dirty once its metadata changes', function () {
    $loaded = reloaded($this->object);

    $loaded->getAdvancedObjects()[0]->setMetadataUpper('another note');

    expect($loaded->isFieldDirty('advancedObjects'))->toBeTrue();
});
