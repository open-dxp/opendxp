<?php

declare(strict_types=1);

use OpenDxp\Db;

/**
 * Counts the database queries of the callable. The second status query counts itself, so it is taken off.
 */
function queriesOf(callable $work): int
{
    $questions = static fn (): int => (int) Db::get()
        ->fetchAssociative("SHOW SESSION STATUS LIKE 'Questions'")['Value'];

    $before = $questions();
    $work();

    return $questions() - $before - 1;
}
