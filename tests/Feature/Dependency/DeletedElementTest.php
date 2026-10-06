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

namespace OpenDxp\Tests\Feature\Dependency;

use OpenDxp\Db;
use OpenDxp\Messenger\Handler\SanityCheckHandler;
use OpenDxp\Messenger\SanityCheckMessage;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tests\Factory\UnittestFactory;
use Symfony\Component\Messenger\Handler\Acknowledger;

function dependenciesOn(Asset $target): int
{
    return (int) Db::get()->fetchOne(
        'SELECT COUNT(*) FROM dependencies WHERE targettype = ? AND targetid = ?',
        [
            'asset',
            $target->getId(),
        ],
    );
}

/**
 * Runs the queued sanity checks as a worker of the queue opendxp_core does.
 */
function runSanityChecks(): void
{
    $transport = Container::get('messenger.transport.opendxp_core');
    $handler = Container::get(SanityCheckHandler::class);

    while ([] !== $envelopes = iterator_to_array($transport->get())) {
        $envelope = $envelopes[0];
        $transport->ack($envelope);

        if ($envelope->getMessage() instanceof SanityCheckMessage) {
            $handler(
                $envelope->getMessage(),
                new Acknowledger(SanityCheckHandler::class),
            );
        }
    }

    $handler->flush(force: true);
}

it('forgets the dependencies on a deleted element at once', function (ElementInterface $source) {
    $target = AssetImageFactory::createOne();
    referencing($source, $target);

    $target->delete();

    expect(dependenciesOn($target))->toBe(0);
})->with('sources');

it('forgets the dependencies on a deleted element after the sanity checks', function (ElementInterface $source) {
    $target = AssetImageFactory::createOne();
    referencing($source, $target);

    $target->delete();
    runSanityChecks();

    expect(dependenciesOn($target))->toBe(0);
})->with('sources');

it('forgets the dependencies even when the source fails to save', function () {
    $target = AssetImageFactory::createOne();
    $object = UnittestFactory::createOne();
    // An import may save a published object without its mandatory fields. The sanity check then fails to save it.
    $object->setMandatoryInputWithDefault(null);
    $object->setOmitMandatoryCheck(true);
    referencing($object, $target);

    $target->delete();
    runSanityChecks();

    expect(dependenciesOn($target))->toBe(0);
});
