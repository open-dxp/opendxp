<?php

declare(strict_types=1);

use OpenDxp\Db;
use OpenDxp\Model\DataObject\Concrete;

/**
 * The query table holds what a listing filters on, which can differ from what the getter returns.
 */
function queryTableValue(Concrete $object, string $column): mixed
{
    return Db::get()->fetchOne(
        sprintf('SELECT `%s` FROM object_query_%s WHERE oo_id = ?', $column, $object->getClassId()),
        [$object->getId()],
    );
}
