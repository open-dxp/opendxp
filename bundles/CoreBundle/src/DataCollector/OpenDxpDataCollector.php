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

namespace OpenDxp\Bundle\CoreBundle\DataCollector;

use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Version;
use Override;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * @internal
 */
class OpenDxpDataCollector extends DataCollector implements ResetInterface
{
    public function __construct(
        protected OpenDxpContextResolver $contextResolver
    ) {
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
        $this->data = [
            'version' => Version::getVersion(),
            'revision' => Version::getRevision(),
            'context' => $this->contextResolver->getOpenDxpContext($request),
        ];
    }

    #[Override]
    public function reset(): void
    {
        $this->data = [];
    }

    public function getName(): string
    {
        return 'opendxp';
    }

    public function getContext(): ?string
    {
        return $this->data['context'];
    }

    public function getVersion(): string
    {
        return $this->data['version'];
    }

    public function getRevision(): string
    {
        return $this->data['revision'];
    }
}
