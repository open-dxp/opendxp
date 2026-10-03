<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Redirect;

use OpenDxp\Bundle\SeoBundle\Model\Redirect;
use Transliterator;

/**
 * The active redirects, arranged for the lookup of a request.
 *
 * A request is checked in two stages. Before routing, only redirects with priority 99 apply, so they win over existing
 * pages. When nothing answers the request, every exact source applies and every regular expression below priority 99.
 * Exact sources are kept in hash maps by the part of the URL they compare. Regular expressions are kept in the order
 * they are tried in.
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
     * @param array{installed: bool, exact: array<string, array<string, array<string, array<string, list<array<string, mixed>>>>>>, regex: array<string, list<array<string, mixed>>>} $data
     */
    private function __construct(private readonly array $data)
    {
    }

    /**
     * @param iterable<array<string, mixed>> $rows the rows of the active redirects
     */
    public static function fromRows(iterable $rows): self
    {
        $data = ['installed' => true, 'exact' => [], 'regex' => []];

        foreach ($rows as $row) {
            // The model ignores empty values, so the table leaves them out.
            $row = array_filter($row, static fn (mixed $value): bool => $value !== null);

            $part = self::PART_OF_TYPE[$row['type'] ?? ''] ?? null;
            if ($part === null) {
                continue;
            }

            $overrides = (int) ($row['priority'] ?? 0) === self::OVERRIDE_PRIORITY;

            if (!empty($row['regex'])) {
                $data['regex'][$overrides ? self::BEFORE_ROUTING : self::NOT_FOUND][] = $row;

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

        foreach ($data['regex'] as &$candidates) {
            usort($candidates, self::compareByPriority(...));
        }
        unset($candidates);

        return new self($data);
    }

    public static function notInstalled(): self
    {
        return new self(['installed' => false, 'exact' => [], 'regex' => []]);
    }

    /**
     * @param array{installed: bool, exact: array<string, mixed>, regex: array<string, mixed>} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * @return array{installed: bool, exact: array<string, mixed>, regex: array<string, mixed>}
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
     * Finds the exact source with the highest priority among the parts of the request URL.
     *
     * @return array<string, mixed>|null
     */
    public function exactMatch(string $stage, ?int $siteId, RedirectUrlPartResolver $request, int $now): ?array
    {
        $best = null;

        foreach ($this->data['exact'][$stage][(string) ($siteId ?? '')] ?? [] as $part => $sources) {
            foreach ($sources[self::normalize($request->getRequestUriPart($part))] ?? [] as $row) {
                if (isset($row['expiry']) && (int) $row['expiry'] <= $now) {
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
    public function regularExpressions(string $stage): array
    {
        return $this->data['regex'][$stage] ?? [];
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

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private static function compareByPriority(array $a, array $b): int
    {
        return [(int) ($b['priority'] ?? 0), (int) $a['id']] <=> [(int) ($a['priority'] ?? 0), (int) $b['id']];
    }
}
