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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Http;

use Symfony\Component\HttpFoundation\Response;
use UnderflowException;

/**
 * This stack can be used to collect responses to be sent from parts which cannot
 * directly influence the request-response cycle (e.g. templating parts). For example
 * this is used to read responses from an areabrick's action() method which is pushed
 * to this stack.
 *
 * The ResponseStackListener takes care of sending back the response set on this stack.
 *
 * @internal
 */
class ResponseStack
{
    /**
     * @var Response[]
     */
    private array $responses = [];

    public function push(Response $response): void
    {
        $this->responses[] = $response;
    }

    public function hasResponses(): bool
    {
        return $this->responses !== [];
    }

    /**
     * @return Response[]
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    public function pop(): Response
    {
        if ($this->responses === []) {
            throw new UnderflowException('There are no responses on the stack.');
        }

        return array_pop($this->responses);
    }

    public function getLastResponse(): Response
    {
        if ($this->responses === []) {
            throw new UnderflowException('There are no responses on the stack.');
        }

        return end($this->responses);
    }
}
