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

/**
 * Asks a question for every path the expected answers name and keys the answers by that path.
 *
 * @param array<string, mixed> $expected
 * @param callable(string): mixed $answer
 *
 * @return array<string, mixed>
 */
function answersByPath(array $expected, callable $answer): array
{
    $paths = array_keys($expected);

    return array_combine(
        $paths,
        array_map($answer, $paths),
    );
}

/**
 * A permission the element does not report comes back as null, so a strict comparison fails on it.
 *
 * @param array<string, mixed> $permissions
 * @param list<string> $names
 *
 * @return array<string, mixed>
 */
function permissionsNamed(array $permissions, array $names): array
{
    return array_combine(
        $names,
        array_map(
            static fn (string $name): mixed => $permissions[$name] ?? null,
            $names,
        ),
    );
}
