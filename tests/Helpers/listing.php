<?php

declare(strict_types=1);

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
