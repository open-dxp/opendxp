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

namespace OpenDxp\Bundle\GenericExecutionEngineBundle\Model;

final readonly class JobRunResult
{
    /**
     * @param array<string, scalar|null> $parameters
     * @param list<string> $artifacts
     * @param list<string> $downloadedArtifacts
     */
    public function __construct(
        public string $message,
        public array $parameters = [],
        public array $artifacts = [],
        public array $downloadedArtifacts = [],
    ) {
    }

    public function hasArtifact(string $filename): bool
    {
        return in_array($filename, $this->artifacts, true);
    }

    public function isEveryArtifactDownloaded(): bool
    {
        return array_diff($this->artifacts, $this->downloadedArtifacts) === [];
    }

    public function withDownloadedArtifact(string $filename): self
    {
        return new self(
            $this->message,
            $this->parameters,
            $this->artifacts,
            array_values(array_unique([...$this->downloadedArtifacts, $filename])),
        );
    }

    /**
     * @param array{
     *     message?: string,
     *     parameters?: array<string, scalar|null>,
     *     artifacts?: list<string>,
     *     downloadedArtifacts?: list<string>
     * } $result
     */
    public static function fromArray(array $result): self
    {
        return new self(
            $result['message'] ?? '',
            $result['parameters'] ?? [],
            $result['artifacts'] ?? [],
            $result['downloadedArtifacts'] ?? [],
        );
    }

    /**
     * @return array{
     *     message: string,
     *     parameters: array<string, scalar|null>,
     *     artifacts: list<string>,
     *     downloadedArtifacts: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'parameters' => $this->parameters,
            'artifacts' => $this->artifacts,
            'downloadedArtifacts' => $this->downloadedArtifacts,
        ];
    }
}
