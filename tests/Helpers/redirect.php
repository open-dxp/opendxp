<?php

declare(strict_types=1);

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use OpenDxp\Db;

function recordHits(Redirect $redirect, int $hits, int $lastHit): void
{
    Db::get()->insert('redirect_hits', [
        'redirectId' => $redirect->getId(),
        'hits' => $hits,
        'lastHit' => $lastHit,
    ]);
}
