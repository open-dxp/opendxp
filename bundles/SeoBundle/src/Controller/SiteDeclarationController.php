<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\SeoBundle\Controller;

use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRegistry;
use OpenDxp\Bundle\SeoBundle\SiteDeclaration\SiteDeclarationRepository;
use OpenDxp\Model\Site;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal
 */
final class SiteDeclarationController
{
    public function __construct(
        private readonly SiteDeclarationRegistry $registry,
        private readonly SiteDeclarationRepository $repository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $declaration = $this->registry->getDeclaration($request->attributes->getString('_site_declaration'));
        $siteId = Site::getByDomain($request->getHost())?->getId() ?? 0;
        $content = $this->repository->loadContent($declaration, $siteId);

        if (trim($content) === '') {
            $content = $declaration->getDefaultContent();
        }

        if ($content === null) {
            throw new NotFoundHttpException();
        }

        $values = [];
        foreach ($declaration->getPlaceholders() as $placeholder) {
            $values[sprintf('%%%s%%', $placeholder->name)] = $placeholder->value;
        }

        return new Response(strtr($content, $values), Response::HTTP_OK, [
            'Content-Type'  => $declaration->getContentType(),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
