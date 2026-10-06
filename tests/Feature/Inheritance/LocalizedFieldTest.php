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

namespace OpenDxp\Tests\Feature\Inheritance;

use OpenDxp\Db;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Tests\Factory\InheritanceFactory;

/**
 * Every language has a query table of its own. It holds a row for every object that carries the text or
 * inherits it, and a listing reads from it.
 */
function queryTableTextOf(Concrete $object, string $language): ?string
{
    $query = sprintf(
        'SELECT input FROM object_localized_query_%s_%s WHERE ooo_id = ?',
        $object->getClassId(),
        $language,
    );
    $row = Db::get()->fetchAssociative(
        $query,
        [$object->getId()],
    );

    return $row['input'] ?? null;
}

function countListedWithLocalizedText(string $text, string $language): int
{
    $listing = new Inheritance\Listing();
    $listing->setCondition('input = ?', [$text]);
    $listing->setLocale($language);

    return count($listing->load());
}

beforeEach(function () {
    $this->parent = InheritanceFactory::new()
        ->withLocalizedValues('input', [
            'en' => 'text of the parent in english',
            'de' => 'text of the parent',
        ])
        ->create();
    $this->child = InheritanceFactory::new()
        ->withParent($this->parent)
        ->withLocalizedValues('input', ['de' => 'text of the child'])
        ->create();
});

it('gives the child the text of its parent in a language it holds no text for', function () {
    $loaded = reloaded($this->child);

    expect($loaded)->getInput('en')->toBe('text of the parent in english');
});

it('keeps the text of the child in a language it holds its own text for', function () {
    $loaded = reloaded($this->child);

    expect($loaded)->getInput('de')->toBe('text of the child');
});

it('gives the child the text of its parent once it empties its own', function () {
    $this->child->setInput(null, 'de');
    $this->child->save();

    expect(reloaded($this->child))->getInput('de')->toBe('text of the parent');
});

it('lists the parent and the child by a localized text the child inherits', function () {
    $count = countListedWithLocalizedText('text of the parent in english', 'en');

    expect($count)->toBe(2);
});

it('lists only the parent by a localized text the child holds its own text for', function () {
    $count = countListedWithLocalizedText('text of the parent', 'de');

    expect($count)->toBe(1);
});

it('lists the child by the localized text of its parent once it empties its own', function () {
    $this->child->setInput(null, 'de');
    $this->child->save();

    expect(countListedWithLocalizedText('text of the parent', 'de'))->toBe(2);
});

it('gives the child no localized text while inherited values are turned off', function () {
    $text = Service::useInheritedValues(
        false,
        fn () => reloaded($this->child)->getInput('en'),
    );

    expect($text)->toBeNull();
});

it('takes the localized text of the parent away from a child moved to the root', function () {
    $this->child->setParentId(1);
    $this->child->save();

    expect(reloaded($this->child))->getInput('en')->toBeNull();
});

it('gives a moved object the localized text of its new parent', function () {
    $object = InheritanceFactory::createOne();

    $object->setParentId($this->parent->getId());
    $object->save();

    expect(reloaded($object))->getInput('en')->toBe('text of the parent in english');
});

it('gives the child the new localized text once the parent changed it', function () {
    $this->parent->setInput('another text in english', 'en');
    $this->parent->save();

    expect(reloaded($this->child))->getInput('en')->toBe('another text in english');
});

it('writes a text into the query table of its own language only', function () {
    $object = InheritanceFactory::new()
        ->withLocalizedValues('input', ['de' => 'only in german'])
        ->create();

    expect(queryTableTextOf($object, 'de'))
        ->toBe('only in german')
        ->and(queryTableTextOf($object, 'en'))
        ->toBeNull();
});

it('writes an inherited text into the query table of the child', function () {
    $text = queryTableTextOf($this->child, 'en');

    expect($text)->toBe('text of the parent in english');
});

it('writes the text of a fallback language into the query table', function () {
    $object = InheritanceFactory::new()
        ->withLocalizedValues('input', ['en' => 'only in english'])
        ->create();

    // The test application lets german fall back to english, and french to nothing.
    expect(queryTableTextOf($object, 'de'))
        ->toBe('only in english')
        ->and(queryTableTextOf($object, 'fr'))
        ->toBeNull();
});
