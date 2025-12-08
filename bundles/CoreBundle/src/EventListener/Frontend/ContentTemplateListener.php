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

namespace OpenDxp\Bundle\CoreBundle\EventListener\Frontend;

use Exception;
use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\Request\Resolver\TemplateResolver;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * If a contentTemplate attribute was set on the request (done by router when building a document route), extract the
 * value and set it on the Template annotation. This handles custom template files being configured on documents.
 *
 * @internal
 */
class ContentTemplateListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public function __construct(protected TemplateResolver $templateResolver, protected Environment $twig)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 16],
        ];
    }

    /**
     * If there's a contentTemplate attribute set on the request, it was read from the document template setting from
     * the router or from the sub-action renderer and takes precedence over the auto-resolved and manually configured
     * template.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        $attribute = $event->controllerArgumentsEvent?->getAttributes()[Template::class][0] ?? null;
        $resolvedTemplate = $this->templateResolver->getTemplate($request);
        if (null === $resolvedTemplate) {
            // no contentTemplate on the request -> nothing to do
            return;
        }

        $parameters = $this->resolveParameters($event, $attribute->vars ?? []);
        $status = 200;

        if (interface_exists(\Symfony\Component\Form\FormInterface::class)) {
            foreach ($parameters as $k => $v) {
                if (!$v instanceof \Symfony\Component\Form\FormInterface) {
                    continue;
                }
                if ($v->isSubmitted() && !$v->isValid()) {
                    $status = 422;
                }
                $parameters[$k] = $v->createView();
            }
        }

        $event->setResponse(($attribute instanceof Template && $attribute->stream)
            ? new StreamedResponse(fn () => $this->twig->display($resolvedTemplate, $parameters), $status)
            : new Response($this->twig->render($resolvedTemplate, $parameters), $status)
        );
    }

    private function resolveParameters(ViewEvent $event, array $vars): array
    {
        $controllerArguments = $event->controllerArgumentsEvent?->getNamedArguments() ?? [];
        $controllerResults = is_array($event->getControllerResult()) ? $event->getControllerResult() : [];

        $mergedArray = [...array_keys($controllerArguments), ...array_keys($controllerResults), ...array_keys($vars)];
        $duplicateKeys = array_unique(array_diff_assoc($mergedArray, array_unique($mergedArray)));

        if ($duplicateKeys) {
            throw new Exception('Duplicate keys found: '.implode(', ', array_values($duplicateKeys)).'. Please use unique names for your controller arguments, controller results and template variables.');
        }

        return [...$controllerArguments, ...$controllerResults, ...$vars];
    }
}
