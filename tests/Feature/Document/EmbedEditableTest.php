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

use OpenDxp\Model\Document\Editable\Embed;

it('renders nothing for a value that is no single web url', function (string $url) {
    $embed = (new Embed())->setDataFromEditmode(['url' => $url]);

    expect($embed->frontend())->toBe('');

})->with([
    'html'               => '<img src=x onerror=alert(1)>',
    'html after the url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ <img src=x onerror=alert(1)>',
    'script url'         => 'javascript:alert(1)',
]);

it('renders a url without a provider as text', function () {
    $embed = (new Embed())->setDataFromEditmode(['url' => 'https://example.com/page?a=1&b=2']);

    expect($embed->frontend())->toBe('https://example.com/page?a=1&amp;b=2');
});
