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

use OpenDxp;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Model\DataObject\Service;
use OpenDxp\Tests\Factory\InheritanceFactory;

function reloaded(DataObject\Concrete $object): Inheritance
{
    return Inheritance::getById($object->getId(), ['force' => true]);
}

/**
 * There is one query table per language, and it holds a row for every object that carries the value
 * or inherits it. A listing reads that table.
 */
function inQueryTable(DataObject\Concrete $object, string $language): ?string
{
    $row = Db::get()->fetchAssociative(
        sprintf('SELECT input FROM object_localized_query_%s_%s WHERE ooo_id = ?', $object->getClassId(), $language),
        [$object->getId()],
    );

    return $row['input'] ?? null;
}

function countedIn(string $language): int
{
    $listing = new Inheritance\Listing();
    $listing->setCondition('input LIKE ?', ['%text of the parent%']);
    $listing->setLocale($language);

    return count($listing->load());
}

beforeEach(function () {
    // Only the admin is handed an object that holds nothing of its own.
    OpenDxp::setAdminMode();

    $this->parent = InheritanceFactory::createOne();
    $this->parent->setInput('text of the parent in english', 'en');
    $this->parent->setInput('text of the parent', 'de');
    $this->parent->save();

    $this->child = InheritanceFactory::createOne(['parentId' => $this->parent->getId()]);
    $this->child->setInput('text of the child', 'de');
    $this->child->save();
});

it('hands a language down that the object below holds nothing for', function () {
    expect(reloaded($this->child)->getInput('en'))->toBe('text of the parent in english');
});

it('keeps the language the object below holds a text for', function () {
    expect(reloaded($this->child)->getInput('de'))->toBe('text of the child');
});

it('hands the language down once the object below holds nothing for it either', function () {

    $this->child->setInput(null, 'de');
    $this->child->save();

    expect(reloaded($this->child)->getInput('de'))->toBe('text of the parent');
});

it('finds both objects in a listing of the language that is inherited', function () {

    $this->child->setInput(null, 'de');
    $this->child->save();

    expect(countedIn('de'))->toBe(2);
});

it('finds one object in a listing of the language the object below holds its own text for', function () {
    expect(countedIn('de'))->toBe(1);
});

it('finds both objects in a listing of a language neither holds its own text for', function () {
    expect(countedIn('en'))->toBe(2);
});

it('hands nothing down while inherited values are turned off', function () {

    Service::useInheritedValues(false, function () {
        expect(reloaded($this->child)->getInput('en'))
            ->toBeNull()
            ->and(reloaded($this->child)->getInput('de'))
            ->toBe('text of the child');
    });
});

it('loses the inherited language when it is moved out and regains it when moved back', function () {

    $this->child->setParentId(1);
    $this->child->save();

    expect($this->child->getInput('en'))
        ->toBeNull()
        ->and($this->child->getInput('de'))
        ->toBe('text of the child');

    $this->child->setParentId($this->parent->getId());
    $this->child->save();

    expect($this->child->getInput('en'))
        ->toBe('text of the parent in english')
        ->and($this->child->getInput('de'))
        ->toBe('text of the child');
});

it('hands the new text down once the object above changed it', function () {

    $this->parent->setInput('another text in english', 'en');
    $this->parent->save();

    expect(reloaded($this->child)->getInput('en'))->toBe('another text in english');
});

it('writes a text into the query table of its own language only', function () {

    $alone = InheritanceFactory::createOne();
    $alone->setInput('only in german', 'de');
    $alone->save();

    expect(inQueryTable($alone, 'de'))
        ->toBe('only in german')
        ->and(inQueryTable($alone, 'en'))
        ->toBeNull();
});

it('writes an inherited text into the query table of the object below', function () {
    expect(inQueryTable($this->child, 'en'))->toBe('text of the parent in english');
});

it('reaches a language through the fallback of another one', function () {

    $alone = InheritanceFactory::createOne();
    $alone->setInput('only in english', 'en');
    $alone->save();

    // The test application lets german fall back to english, and french to nothing.
    expect(inQueryTable($alone, 'de'))
        ->toBe('only in english')
        ->and(inQueryTable($alone, 'fr'))
        ->toBeNull();
});
