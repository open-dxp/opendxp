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


namespace OpenDxp\Tests\Feature\Twig;

use Carbon\Carbon;
use OpenDxp\Model\Document\Editable\Date;
use OpenDxp\Model\Document\Snippet;
use OpenDxp\TestFoundation\Container;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

beforeEach(function () {
    $this->twig = Container::get('opendxp.templating');
    $this->locale = Carbon::getLocale();
});

afterEach(fn () => Carbon::setLocale($this->locale));

it('writes a date in the language carbon is set to', function (string $locale, string $expected) {

    Carbon::setLocale($locale);

    $this->twig->setLoader(new ArrayLoader([
        'twig' => '{{ opendxp_date("myDate", {"format": "d.m.Y", "outputIsoFormat": "dddd, MMMM D, YYYY h:mm"}) }}',
    ]));

    $snippet = new Snippet();
    $snippet->setEditable((new Date())->setName('myDate')->setDataFromResource(1733954969));

    expect($this->twig->render('twig', ['document' => $snippet]))->toBe($expected);
})->with([
    'english' => ['en', 'Wednesday, December 11, 2024 11:09'],
    'german' => ['de_DE.utf8', 'Mittwoch, Dezember 11, 2024 11:09'],
]);
