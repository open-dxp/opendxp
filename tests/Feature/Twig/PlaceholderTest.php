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

use OpenDxp\TestFoundation\Container;
use Twig\Loader\ArrayLoader;

beforeEach(fn () => $this->twig = Container::get('opendxp.templating'));

it('prints the text it was given at every placeholder', function () {
    $this->twig->setLoader(new ArrayLoader([
        'twig' => <<<'TWIG'
            {%- do opendxp_placeholder('foo').set('Some text for later') -%}
            {{ opendxp_placeholder('foo') }}|{{ opendxp_placeholder('foo') }}
            TWIG,
    ]));

    $result = $this->twig->render('twig');

    expect($result)->toBe('Some text for later|Some text for later');
});

it('wraps appended items with the prefix, separator, indent and postfix', function () {
    $this->twig->setLoader(new ArrayLoader([
        'twig' => <<<'TWIG'
            {%- do opendxp_placeholder('foo')
                .setPrefix("<ul>\n<li>")
                .setSeparator("</li>\n<li>")
                .setIndent(4)
                .setPostfix("</li>\n</ul>")
            -%}
            {%- for title in titles %}{% do opendxp_placeholder('foo').append(title) %}{% endfor -%}
            {{ opendxp_placeholder('foo') }}
            TWIG,
    ]));

    $result = $this->twig->render('twig', [
        'titles' => [
            'oh',
            'my',
            'list',
        ],
    ]);

    expect($result)->toBe(<<<'HTML'
            <ul>
            <li>oh</li>
            <li>my</li>
            <li>list</li>
            </ul>
        HTML);
});

it('prints a captured block as markup at every placeholder', function () {
    $this->twig->setLoader(new ArrayLoader([
        'twig' => <<<'TWIG'
            {%- set teaser %}<h2>{{ title }}</h2>{% endset -%}
            {%- do opendxp_placeholder('foo').set(teaser) -%}
            {{ opendxp_placeholder('foo') }}|{{ opendxp_placeholder('foo') }}
            TWIG,
    ]));

    $result = $this->twig->render('twig', ['title' => 'Title & more']);

    expect($result)->toBe('<h2>Title &amp; more</h2>|<h2>Title &amp; more</h2>');
});
