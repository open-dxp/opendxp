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
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Model\Document\Editable;
use OpenDxp\Test\Factory\AssetDocumentFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\AssetVideoFactory;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\Tests\Factory\UnittestFactory;

function savedAndLoaded(Editable $editable): Editable
{
    $page = DocumentPageFactory::new()
        ->withEditables(['field' => $editable])
        ->create();

    return reloaded($page)->getEditable('field');
}

it('keeps the value of an editable', function (Editable $editable) {
    $reloaded = savedAndLoaded($editable);

    expect($reloaded)
        ->toBeInstanceOf($editable::class)
        ->and($reloaded->getValue())
        ->toEqual($editable->getValue());
})->with([
    'a line of text' => [fn () => (new Editable\Input())->setDataFromEditmode('content1')],
    'several lines of text' => [fn () => (new Editable\Textarea())->setDataFromEditmode('content<br />1')],
    'formatted text' => [fn () => (new Editable\Wysiwyg())->setDataFromEditmode('content<br />1')],
    'a number' => [fn () => (new Editable\Numeric())->setDataFromEditmode(124)],
    'a checkbox' => [fn () => (new Editable\Checkbox())->setDataFromResource(true)],
    'a selected option' => [fn () => (new Editable\Select())->setDataFromEditmode(2)],
    'several selected options' => [
        fn () => (new Editable\Multiselect())->setDataFromEditmode([
            '1',
            '2',
        ]),
    ],
    'a table' => [
        fn () => (new Editable\Table())->setDataFromEditmode([
            [
                'a1',
                'b1',
                'c1',
            ],
            [
                2,
                3,
                4,
            ],
        ]),
    ],
    'an areablock' => [
        fn () => (new Editable\Areablock())->setDataFromEditmode([
            [
                'key' => 4,
                'type' => 'standard-teaser',
                'hidden' => false,
            ],
            [
                'key' => 1,
                'type' => 'wysiwyg',
                'hidden' => true,
            ],
        ]),
    ],
    'a scheduled block' => [
        fn () => (new Editable\Scheduledblock())->setDataFromEditmode([
            [
                'key' => 4,
                'date' => 1613383346,
            ],
            [
                'key' => 1,
                'date' => 1613383352,
            ],
        ]),
    ],
]);

it('keeps the url of an embed', function () {
    $embed = (new Editable\Embed())->setDataFromEditmode(['url' => 'https://someurl1']);

    $reloaded = savedAndLoaded($embed);

    expect($reloaded->getUrl())->toBe('https://someurl1');
});

it('keeps the date of a date editable as a point in time', function () {
    $date = (new Editable\Date())->setDataFromEditmode('2021-02-11');

    $value = savedAndLoaded($date)->getValue();

    expect($value)
        ->toBeInstanceOf(Carbon::class)
        ->and($value->getTimestamp())
        ->toBe(strtotime('2021-02-11'));
});

it('keeps the image of an image editable', function () {
    $image = AssetImageFactory::createOne();
    $editable = (new Editable\Image())->setDataFromEditmode(['id' => $image->getId()]);

    $reloaded = savedAndLoaded($editable);

    expect($reloaded->getImage()->getId())->toBe($image->getId());
});

it('keeps the pdf of a pdf editable', function () {
    $pdf = AssetDocumentFactory::createOne();
    $editable = (new Editable\Pdf())->setDataFromEditmode(['id' => $pdf->getId()]);

    $reloaded = savedAndLoaded($editable);

    expect($reloaded->getElement()->getId())->toBe($pdf->getId());
});

it('keeps a video with its poster, title and description', function () {
    $video = AssetVideoFactory::createOne();
    $poster = AssetImageFactory::createOne();
    $editable = (new Editable\Video())->setDataFromEditmode([
        'id' => $video->getId(),
        'path' => $video->getFullPath(),
        'title' => 'some title',
        'description' => 'some description',
        'poster' => $poster->getFullPath(),
        'type' => 'asset',
    ]);

    $reloaded = savedAndLoaded($editable);

    expect($reloaded->getVideoAsset()->getId())
        ->toBe($video->getId())
        ->and($reloaded->getPosterAsset()->getId())
        ->toBe($poster->getId())
        ->and($reloaded->getTitle())
        ->toBe('some title')
        ->and($reloaded->getDescription())
        ->toBe('some description');
});

it('keeps a link with its text, title and target', function () {
    $target = AssetImageFactory::createOne();
    $editable = (new Editable\Link())->setDataFromEditmode([
        'internalType' => 'asset',
        'linktype' => 'internal',
        'path' => $target->getFullPath(),
        'text' => 'some text',
        'title' => 'some title',
        'target' => '_blank',
    ]);

    $reloaded = savedAndLoaded($editable);

    expect($reloaded->getHref())
        ->toBe($target->getFullPath())
        ->and($reloaded->getText())
        ->toBe('some text')
        ->and($reloaded->getTitle())
        ->toBe('some title')
        ->and($reloaded->getTarget())
        ->toBe('_blank');
});

it('keeps the element of a relation', function () {
    $target = UnittestFactory::createOne();
    $editable = (new Editable\Relation())->setDataFromEditmode([
        'id' => $target->getId(),
        'type' => 'object',
    ]);

    $reloaded = savedAndLoaded($editable);

    expect($reloaded->getElement()->getId())->toBe($target->getId());
});

it('keeps every element of a list of relations', function () {
    $targets = UnittestFactory::createMany(4);
    $related = array_map(
        static fn (Unittest $target): array => [
            'id' => $target->getId(),
            'type' => 'object',
        ],
        $targets,
    );
    $editable = (new Editable\Relations())->setDataFromEditmode($related);

    $reloaded = savedAndLoaded($editable);

    expect(elementIds($reloaded->getElements()))->toBe(elementIds($targets));
});

it('keeps the editables of every index of a block', function () {
    $image = AssetImageFactory::createOne();
    $page = DocumentPageFactory::new()
        ->withEditables([
            'field' => (new Editable\Block())->setDataFromEditmode([
                1,
                2,
            ]),
            'field:1.input' => (new Editable\Input())->setDataFromResource('first text'),
            'field:1.image' => (new Editable\Image())->setDataFromEditmode(['id' => $image->getId()]),
            'field:2.input' => (new Editable\Input())->setDataFromResource('second text'),
            'field:2.image' => (new Editable\Image())->setDataFromEditmode(['id' => $image->getId()]),
        ])
        ->create();

    [$first, $second] = reloaded($page)->getEditable('field')->getElements();

    expect($first->getEditable('input')->getValue())
        ->toBe('first text')
        ->and($first->getEditable('image')->getImage()->getId())
        ->toBe($image->getId())
        ->and($second->getEditable('input')->getValue())
        ->toBe('second text')
        ->and($second->getEditable('image')->getImage()->getId())
        ->toBe($image->getId());
});
