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


use Carbon\Carbon;
use OpenDxp\Model\DataObject\Data;

/**
 * Every localized field type, with a value per language. The value takes the language, because the
 * original test wrote a different text into each one to tell them apart.
 */
dataset('localized field values', [
    'a line of text' => ['linput', fn (string $language) => $language . 'content1'],
    'a text area' => ['ltextarea', fn (string $language) => $language . 'content1'],
    'formatted text' => ['lwysiwyg', fn (string $language) => $language . 'content1'],
    'a number' => ['lnumber', fn () => 124],
    'a slider' => ['lslider', fn () => 8],
    'a checkbox' => ['lcheckbox', fn () => true],
    'a select' => ['lselect', fn () => '2'],
    'several selected values' => ['lmultiselect', fn () => ['1', '2']],
    'several countries' => ['lcountries', fn () => ['1', '2']],
    'several languages' => ['llanguages', fn () => ['1', '2']],
    'a date' => ['ldate', fn () => Carbon::create(2000, 12, 24)],
    'a date and a time' => ['ldatetime', fn () => Carbon::create(2000, 12, 24)],
    'a time of day' => ['ltime', fn () => '06:41'],
    'a table' => ['ltable', fn () => [['eins', 'zwei', 'drei'], [1, 2, 3], ['a', 'b', 'c']]],
    'an image' => ['limage', fn () => anImage('image.jpg')],
    'a link to a document' => ['llink', fn () => aLink(aPage('document1'))],
    'a slug' => ['lurlSlug', fn (string $language) => [new Data\UrlSlug('/' . $language . '/content1')]],
    // The original test wrote a different number of objects per language, to tell the languages apart.
    'the objects it relates to' => ['lobjects', fn (string $language) => someObjects($language === 'de' ? 6 : 5)],
    'the elements it relates to lazily' => ['lmultihrefLazy', fn (string $language) => someObjects($language === 'de' ? 6 : 5)],
]);
