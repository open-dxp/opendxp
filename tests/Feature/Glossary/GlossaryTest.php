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

namespace OpenDxp\Tests\Feature\Glossary;

use OpenDxp\Bundle\GlossaryBundle\Tool\Processor;
use OpenDxp\Test\Factory\GlossaryFactory;
use OpenDxp\TestFoundation\Container;

beforeEach(fn () => $this->processor = Container::get(Processor::class));

it('turns the term it finds into a link to the glossary entry', function (string $term, string $link, string $source, string $expected) {

    GlossaryFactory::createOne(['text' => $term, 'link' => $link]);

    // Depending on the libxml version, the processor keeps an entity or decodes it, so both sides are compared decoded.
    expect(html_entity_decode($this->processor->parse($source, [], 'en', null, null)))->toBe(html_entity_decode($expected));
})->with([
    'a term in a sentence' => [
        'Glossary',
        '/test',
        '<head></head><body><p>This is a Test for the Glossary</p></body>',
        '<head></head><body><p>This is a Test for the <a class="opendxp_glossary" href="/test">Glossary</a></p></body>',
    ],
    'a term behind a non breaking space' => [
        'Entity',
        '/test',
        '<head></head><body><p>This is a Test for the&nbsp;Entity &copy;</p></body>',
        html_entity_decode('<head></head><body><p>This is a Test for the&nbsp;<a class="opendxp_glossary" href="/test">Entity</a> &copy;</p></body>'),
    ],
    'a term between a non breaking space and a copyright sign' => [
        'Eintrag',
        '/test',
        '<head></head><body><p>Test &nbsp; Eintrag ©</p></body>',
        html_entity_decode('<head></head><body><p>Test &nbsp; <a class="opendxp_glossary" href="/test">Eintrag</a> &copy;</p></body>'),
    ],
    'a term next to an ampersand' => [
        'hans',
        '/hans',
        '<p>hans &amp; gretl</p>',
        '<p><a class="opendxp_glossary" href="/hans">hans</a> &amp; gretl</p>',
    ],
    'a term next to an angle bracket' => [
        'huber',
        '/huber',
        '<p>Huber &lt;&gt; is the best</p>',
        '<p><a class="opendxp_glossary" href="/huber">huber</a> &lt;&gt; is the best</p>',
    ],
    'a term deep inside markup' => [
        'HTML',
        '/test',
        <<<HTML
        <section class="c-content" id="c-20-content-0">
                <div class="container">
                    <div class="text-content">
                        <h2 class="text-content__title text-center">Seit&nbsp; 1909</h2>
                        <p>Another &nbsp; HTML &copy;</p>
                    </div>
                </div>
            </section>
        HTML,
        html_entity_decode(<<<HTML
        <section class="c-content" id="c-20-content-0">
                <div class="container">
                    <div class="text-content">
                        <h2 class="text-content__title text-center">Seit&nbsp; 1909</h2>
                        <p>Another &nbsp; <a class="opendxp_glossary" href="/test">HTML</a> &copy;</p>
                    </div>
                </div>
            </section>
        HTML),
    ],
]);
