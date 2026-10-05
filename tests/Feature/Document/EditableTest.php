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

namespace OpenDxp\Tests\Feature\Document;

use Carbon\Carbon;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Document\Editable;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\AssetDocumentFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

const AREABLOCK = [
    ['key' => 4, 'type' => 'standard-teaser', 'hidden' => false],
    ['key' => 1, 'type' => 'wysiwyg', 'hidden' => true],
];

const SCHEDULEDBLOCK = [
    ['key' => 4, 'date' => 1613383346],
    ['key' => 1, 'date' => 1613383352],
];

const TABLE = [
    ['a1', 'b1', 'c1'],
    [2, 3, 4],
];

function saved(Page $page, Editable $editable): Editable
{
    $page->setEditable($editable);
    $page->save();

    return Page::getById($page->getId(), ['force' => true])->getEditable($editable->getName());
}

function editable(string $type, string $setter, mixed $data): Editable
{
    $editable = new $type();
    $editable->setName('field');
    $editable->{$setter}($data);

    return $editable;
}

beforeEach(fn () => $this->page = DocumentPageFactory::createOne());

it('hands an editable back with the value it was saved with', function (string $type, string $setter, mixed $data, mixed $expected) {

    $reloaded = saved($this->page, editable($type, $setter, $data));

    expect($reloaded)
        ->toBeInstanceOf($type)
        ->and($reloaded->getValue())
        ->toEqual($expected);
})->with([
    'a line of text' => [Editable\Input::class, 'setDataFromEditmode', 'content1', 'content1'],
    'several lines of text' => [Editable\Textarea::class, 'setDataFromEditmode', 'content<br />1', 'content<br />1'],
    'formatted text' => [Editable\Wysiwyg::class, 'setDataFromEditmode', 'content<br />1', 'content<br />1'],
    'a number' => [Editable\Numeric::class, 'setDataFromEditmode', 124, 124],
    'a checkbox' => [Editable\Checkbox::class, 'setDataFromResource', true, true],
    'a selected option' => [Editable\Select::class, 'setDataFromEditmode', 2, 2],
    'several selected options' => [Editable\Multiselect::class, 'setDataFromEditmode', ['1', '2'], ['1', '2']],
    'a table' => [Editable\Table::class, 'setDataFromEditmode', TABLE, TABLE],
    'an areablock' => [Editable\Areablock::class, 'setDataFromEditmode', AREABLOCK, AREABLOCK],
    'a scheduled block' => [Editable\Scheduledblock::class, 'setDataFromEditmode', SCHEDULEDBLOCK, SCHEDULEDBLOCK],
]);

it('hands back the url it embeds', function () {

    $reloaded = saved($this->page, editable(Editable\Embed::class, 'setDataFromEditmode', ['url' => 'https://someurl1']));

    expect($reloaded->getUrl())->toBe('https://someurl1');
});

it('hands a date back as a point in time', function () {

    $reloaded = saved($this->page, editable(Editable\Date::class, 'setDataFromEditmode', '2021-02-11'));

    expect($reloaded->getValue())
        ->toBeInstanceOf(Carbon::class)
        ->and($reloaded->getValue()->getTimestamp())
        ->toBe(strtotime('2021-02-11'));
});

it('hands back the image it points at', function () {

    $image = AssetImageFactory::createOne();

    $reloaded = saved($this->page, editable(Editable\Image::class, 'setDataFromEditmode', ['id' => $image->getId()]));

    expect($reloaded->getImage())
        ->toBeInstanceOf(Asset\Image::class)
        ->and($reloaded->getImage()->getId())
        ->toBe($image->getId());
});

it('hands back the pdf it points at', function () {

    $pdf = AssetDocumentFactory::createOne();

    $reloaded = saved($this->page, editable(Editable\Pdf::class, 'setDataFromEditmode', ['id' => $pdf->getId()]));

    expect($reloaded->getElement()->getId())->toBe($pdf->getId());
});

it('hands back the video, its poster and its wording', function () {

    $video = AssetVideoFactory::createOne();
    $poster = AssetImageFactory::createOne();

    $reloaded = saved($this->page, editable(Editable\Video::class, 'setDataFromEditmode', [
        'id' => $video->getId(),
        'path' => $video->getFullPath(),
        'title' => 'some title',
        'description' => 'some description',
        'poster' => $poster->getFullPath(),
        'type' => 'asset',
    ]));

    expect($reloaded->getVideoAsset()->getId())
        ->toBe($video->getId())
        ->and($reloaded->getPosterAsset()->getId())
        ->toBe($poster->getId())
        ->and($reloaded->getTitle())
        ->toBe('some title')
        ->and($reloaded->getDescription())
        ->toBe('some description');
});

it('hands back the link with its wording and where it opens', function () {

    $target = AssetImageFactory::createOne();

    $reloaded = saved($this->page, editable(Editable\Link::class, 'setDataFromEditmode', [
        'internalType' => 'asset',
        'linktype' => 'internal',
        'path' => $target->getFullPath(),
        'text' => 'some text',
        'title' => 'some title',
        'target' => '_blank',
    ]));

    expect($reloaded->getHref())
        ->toBe($target->getFullPath())
        ->and($reloaded->getText())
        ->toBe('some text')
        ->and($reloaded->getTitle())
        ->toBe('some title')
        ->and($reloaded->getTarget())
        ->toBe('_blank');
});

it('hands back the one element it relates to', function () {

    $target = UnittestFactory::createOne();

    $reloaded = saved($this->page, editable(Editable\Relation::class, 'setDataFromEditmode', [
        'id' => $target->getId(),
        'type' => 'object',
    ]));

    expect($reloaded->getElement()->getId())->toBe($target->getId());
});

it('hands back every element it relates to', function () {

    $targets = UnittestFactory::createMany(4);
    $related = array_map(static fn ($target) => ['id' => $target->getId(), 'type' => 'object'], $targets);

    $reloaded = saved($this->page, editable(Editable\Relations::class, 'setDataFromEditmode', $related));

    expect(array_map(static fn ($element) => $element->getId(), $reloaded->getElements()))
        ->toBe(array_map(static fn ($target) => $target->getId(), $targets));
});

it('hands back the editables of every index of a block', function () {

    $image = AssetImageFactory::createOne();

    foreach ([1, 2] as $index) {
        $this->page->setEditable(editable(Editable\Input::class, 'setDataFromResource', 'block text ' . $index)
            ->setName('field:' . $index . '.input'));

        $this->page->setEditable(editable(Editable\Image::class, 'setDataFromEditmode', ['id' => $image->getId()])
            ->setName('field:' . $index . '.image'));
    }

    $reloaded = saved($this->page, editable(Editable\Block::class, 'setDataFromEditmode', [1, 2]));

    expect($reloaded->getValue())->toBe([1, 2]);

    foreach ($reloaded->getElements() as $position => $element) {
        expect($element->getEditable('input')->getValue())
            ->toBe('block text ' . ($position + 1))
            ->and($element->getEditable('image')->getImage())
            ->toBeInstanceOf(Asset\Image::class);
    }
});
