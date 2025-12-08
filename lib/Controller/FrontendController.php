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

namespace OpenDxp\Controller;

use Exception;
use OpenDxp\Http\Request\Resolver\DocumentResolver;
use OpenDxp\Http\Request\Resolver\EditmodeResolver;
use OpenDxp\Http\Request\Resolver\ResponseHeaderResolver;
use OpenDxp\Model\Document;
use OpenDxp\Templating\Renderer\EditableRenderer;
use Override;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @property Document|null $document
 * @property bool $editmode
 */
abstract class FrontendController extends AbstractController
{
    /**
     * @return string[]
     */
    #[Override]
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services[EditmodeResolver::class] = '?'.EditmodeResolver::class;
        $services[DocumentResolver::class] = '?'.DocumentResolver::class;
        $services[ResponseHeaderResolver::class] = '?'.ResponseHeaderResolver::class;
        $services[EditableRenderer::class] = '?'.EditableRenderer::class;

        return $services;
    }

    /**
     * document and editmode as properties and proxy them to request attributes through
     * their resolvers.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if ('document' === $name) {
            return $this->container->get(DocumentResolver::class)->getDocument();
        }

        if ('editmode' === $name) {
            return $this->container->get(EditmodeResolver::class)->isEditmode();
        }

        throw new RuntimeException(sprintf('Trying to read undefined property "%s"', $name));
    }

    public function __set(string $name, mixed $value): void
    {
        $requestAttributes = ['document', 'editmode'];
        if (in_array($name, $requestAttributes)) {
            throw new RuntimeException(sprintf(
                'Property "%s" is a request attribute and can\'t be set on the controller instance',
                $name
            ));
        }

        throw new RuntimeException(sprintf('Trying to set unknown property "%s"', $name));
    }

    /**
     * We don't have a response object at this point, but we can add headers here which will be
     * set by the ResponseHeaderListener which reads and adds this headers in the kernel.response event.
     */
    protected function addResponseHeader(string $key, array|string $values, bool $replace = false, ?Request $request = null): void
    {
        if (!$request instanceof \Symfony\Component\HttpFoundation\Request) {
            $request = $this->container->get('request_stack')->getCurrentRequest();
        }

        $this->container->get(ResponseHeaderResolver::class)->addResponseHeader($request, $key, $values, $replace);
    }

    /**
     * Loads a document editable
     *
     * e.g. `$this->getDocumentEditable('input', 'foobar')`
     *
     * @throws Exception
     */
    public function getDocumentEditable(string $type, string $inputName, array $options = [], ?Document\PageSnippet $document = null): Document\Editable\EditableInterface
    {
        if (!$document instanceof \OpenDxp\Model\Document\PageSnippet) {
            $document = $this->document;
            if (!$document instanceof Document\PageSnippet) {
                throw new Exception('FrontendController::getDocumentEditable() needs a Document\PageSnippet instance');
            }
        }

        return $this->container->get(EditableRenderer::class)->getEditable($document, $type, $inputName, $options);
    }

    protected function renderTemplate(string $view, array $parameters = [], ?Response $response = null): Response
    {
        return $this->render($view, $parameters, $response);
    }
}
