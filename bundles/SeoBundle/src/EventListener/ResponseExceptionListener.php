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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\SeoBundle\EventListener;

use Doctrine\DBAL\Connection;
use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectCache;
use OpenDxp\Bundle\SeoBundle\Redirect\RedirectHandler;
use OpenDxp\Http\Exception\ResponseException;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

/**
 * Logs the requests that end in an HTTP error to the HTTP error log.
 *
 * The entry is written once the response has been sent, so a visitor never waits for it. A single upsert keyed by the
 * hash of the URI counts repeated errors, which keeps concurrent requests from creating duplicates.
 *
 * @internal
 */
class ResponseExceptionListener implements EventSubscriberInterface, ResetInterface
{
    use OpenDxpContextAwareTrait;

    /**
     * @var array{uri: string, code: int, parametersGet: string}|null
     */
    private ?array $error = null;

    public function __construct(
        protected Connection $db,
        private readonly RedirectCache $redirectCache,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // run with high priority before handling real errors
            KernelEvents::EXCEPTION => ['onKernelException', 64],
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // handle ResponseException (can be used from any context)
        if ($exception instanceof ResponseException) {
            return;
        }

        if ($this->debug || !$event->isMainRequest()) {
            return;
        }

        // further checks are only valid for default context
        $request = $event->getRequest();
        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$this->redirectCache->get()->installed) {
            return;
        }

        $this->error = [
            'uri' => $request->getUri(),
            'code' => $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500,
            'parametersGet' => serialize($request->query->all()),
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $error = $this->error;
        $this->error = null;

        // A redirect answers the request after this listener saw the error.
        if ($error === null || $event->getResponse()->headers->has(RedirectHandler::RESPONSE_HEADER_NAME_ID)) {
            return;
        }

        try {
            $this->db->executeStatement(
                'INSERT INTO http_error_log (uri, uriHash, code, parametersGet, date, count)
                    VALUES (:uri, :uriHash, :code, :parametersGet, :date, 1)
                    ON DUPLICATE KEY UPDATE count = count + 1, date = VALUES(date)',
                [...$error, 'uriHash' => sha1($error['uri'], true), 'date' => time()]
            );
        } catch (Throwable $exception) {
            $this->logger->warning('Could not log the HTTP error of {uri}: {message}', [
                'uri' => $error['uri'],
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function reset(): void
    {
        $this->error = null;
    }
}
