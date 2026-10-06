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

use OpenDxp\Model\DataObject\Unittest;

function unittestListing(string $inputPrefix): Unittest\Listing
{
    $listing = new Unittest\Listing();
    $listing->setCondition(
        'input LIKE ?',
        [sprintf('%s%%', $inputPrefix)],
    );
    $listing->setOrderKey('oo_id');
    $listing->setOrder('asc');

    return $listing;
}
