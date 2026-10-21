<?php

declare(strict_types=1);

namespace OpenDxp\Http\Request\Host;

interface TrustedHostPatternProviderInterface
{
    /**
     * Returns more patterns in the form of `framework.trusted_hosts`.
     *
     * Each pattern of a known host starts with ^ and ends with $.
     *
     * @param list<string> $knownHostPatterns
     *
     * @return list<string>
     */
    public function getPatterns(array $knownHostPatterns): array;
}
