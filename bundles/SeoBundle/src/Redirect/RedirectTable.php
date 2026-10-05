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

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use Transliterator;

/**
 * The active redirects, arranged for the lookup of a request.
 *
 * A domain redirect applies to every request to its host, before anything else. A request is then checked in two
 * stages. Before routing, only redirects with priority 99 apply, so they win over existing pages. When nothing answers
 * the request, every exact source applies and every regular expression below priority 99.
 *
 * Within a stage, protected redirects come first. A protected exact source wins over a protected regular expression,
 * which wins over an exact source that is not protected. That way nobody without the permission can override a
 * protected redirect.
 *
 * Exact sources and hosts are kept in hash maps. Regular expressions are kept in the order they are tried in.
 *
 * A row is the database row of a redirect, so a hit turns into the same model that Redirect::getById() loads.
 *
 * @internal
 */
final class RedirectTable
{
    public const string BEFORE_ROUTING = 'before_routing';

    public const string NOT_FOUND = 'not_found';

    private const int OVERRIDE_PRIORITY = 99;

    /**
     * The part of the URL that a type compares.
     */
    private const array PART_OF_TYPE = [
        Redirect::TYPE_PATH => Redirect::TYPE_PATH,
        Redirect::TYPE_AUTO_CREATE => Redirect::TYPE_PATH,
        Redirect::TYPE_PATH_QUERY => Redirect::TYPE_PATH_QUERY,
        Redirect::TYPE_ENTIRE_URI => Redirect::TYPE_ENTIRE_URI,
    ];

    private static ?Transliterator $accentFolding = null;

    /**
     * @param array{installed: bool, host: array<string, list<array<string, mixed>>>, exact: array<string, array<string, array<string, array<string, list<array<string, mixed>>>>>>, regex: array<string, array<int, list<array<string, mixed>>>>} $data
     */
    private function __construct(private readonly array $data)
    {
    }

    /**
     * @param iterable<array<string, mixed>> $rows the rows of the active redirects
     */
    public static function fromRows(iterable $rows): self
    {
        $data = ['installed' => true, 'host' => [], 'exact' => [], 'regex' => []];

        foreach ($rows as $row) {
            // The model ignores empty values, so the table leaves them out.
            $row = array_filter($row, static fn (mixed $value): bool => $value !== null);

            if (($row['type'] ?? '') === Redirect::TYPE_DOMAIN) {
                $data['host'][self::normalize((string) $row['source'])][] = $row;

                continue;
            }

            $part = self::PART_OF_TYPE[$row['type'] ?? ''] ?? null;
            if ($part === null) {
                continue;
            }

            $overrides = (int) ($row['priority'] ?? 0) === self::OVERRIDE_PRIORITY;

            if (!empty($row['regex'])) {
                // A pattern that does not compile never matches. Leaving it out spares every request the attempt.
                if (!self::compiles((string) ($row['source'] ?? ''))) {
                    continue;
                }

                $data['regex'][$overrides ? self::BEFORE_ROUTING : self::NOT_FOUND][(int) !empty($row['protected'])][] = $row;

                continue;
            }

            $site = (string) ($row['sourceSite'] ?? '');
            $source = self::normalize((string) $row['source']);

            foreach ($overrides ? [self::BEFORE_ROUTING, self::NOT_FOUND] : [self::NOT_FOUND] as $stage) {
                $data['exact'][$stage][$site][$part][$source][] = $row;
            }
        }

        foreach ($data['exact'] as &$sites) {
            foreach ($sites as &$parts) {
                foreach ($parts as &$sources) {
                    foreach ($sources as &$candidates) {
                        usort($candidates, self::compareByPriority(...));
                    }
                }
            }
        }
        unset($sites, $parts, $sources, $candidates);

        foreach ($data['regex'] as &$lists) {
            foreach ($lists as &$candidates) {
                usort($candidates, self::compareByPriority(...));
            }
        }
        unset($lists, $candidates);

        foreach ($data['host'] as &$candidates) {
            usort($candidates, self::compareByPriority(...));
        }
        unset($candidates);

        return new self($data);
    }

    public static function notInstalled(): self
    {
        return new self(['installed' => false, 'host' => [], 'exact' => [], 'regex' => []]);
    }

    /**
     * @param array{installed: bool, host: array<string, mixed>, exact: array<string, mixed>, regex: array<string, mixed>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array{installed: bool, host: array<string, mixed>, exact: array<string, mixed>, regex: array<string, mixed>}
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function isInstalled(): bool
    {
        return $this->data['installed'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function domainMatch(string $host, int $now): ?array
    {
        foreach ($this->data['host'][self::normalize($host)] ?? [] as $row) {
            if (self::hasStarted($row, $now) && (empty($row['expiry']) || (int) $row['expiry'] > $now)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Finds the exact source that comes first among the parts of the request URL: a protected one before any other,
     * then the one with the highest priority.
     *
     * @return array<string, mixed>|null
     */
    public function exactMatch(string $stage, ?int $siteId, RedirectUrlPartResolver $request, int $now): ?array
    {
        $best = null;

        foreach ($this->data['exact'][$stage][(string) ($siteId ?? '')] ?? [] as $part => $sources) {
            foreach ($sources[self::normalize($request->getRequestUriPart($part))] ?? [] as $row) {
                if ((isset($row['expiry']) && (int) $row['expiry'] <= $now) || !self::hasStarted($row, $now)) {
                    continue;
                }

                if ($best === null || self::compareByPriority($row, $best) < 0) {
                    $best = $row;
                }

                break;
            }
        }

        return $best;
    }

    /**
     * @return list<array<string, mixed>> the regular expressions of the stage, in the order they are tried in
     */
    public function regularExpressions(string $stage, bool $protected): array
    {
        return $this->data['regex'][$stage][(int) $protected] ?? [];
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function hasStarted(array $row, int $now): bool
    {
        return empty($row['validFrom']) || (int) $row['validFrom'] <= $now;
    }

    /**
     * The database compared exact sources regardless of case and accents. The table keeps doing so.
     */
    public static function normalize(string $value): string
    {
        if (preg_match('/[^\x00-\x7F]/', $value) !== 1) {
            return strtolower($value);
        }

        self::$accentFolding ??= Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC; Lower()');

        return self::$accentFolding?->transliterate($value) ?: mb_strtolower($value);
    }

    private static function compiles(string $pattern): bool
    {
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match($pattern, '') !== false;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private static function compareByPriority(array $a, array $b): int
    {
        return [(int) !empty($b['protected']), (int) ($b['priority'] ?? 0), (int) $a['id']]
            <=> [(int) !empty($a['protected']), (int) ($a['priority'] ?? 0), (int) $b['id']];
    }
}
