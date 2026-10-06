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

use OpenDxp\Bundle\SimpleBackendSearchBundle\Model\Search;
use OpenDxp\Model\Element\ElementInterface;

/**
 * Writes the search entry an element needs to be found by the backend search. Nothing writes one outside a
 * request, so a test that searches has to.
 *
 * @template T of ElementInterface
 *
 * @param T $element
 *
 * @return T
 */
function index(ElementInterface $element): ElementInterface
{
    (new Search\Backend\Data($element))->save();

    return $element;
}
