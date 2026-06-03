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

namespace OpenDxp\Bundle\CoreBundle\Controller;

use Exception;
use OpenDxp\Bundle\SeoBundle\Config;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Site;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @internal
 */
class PublicServicesController extends AbstractController
{
    public function thumbnailAction(Request $request): RedirectResponse|StreamedResponse
    {
        $filename = $request->attributes->getString('filename');
        $requestedFileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $config = [
            'prefix' => $request->attributes->getString('prefix', ''),
            'type' => $request->attributes->getString('type'),
            'asset_id' => $request->attributes->getInt('assetId'),
            'thumbnail_name' => $request->attributes->getString('thumbnailName'),
            'filename' => $filename,
            'file_extension' => $requestedFileExtension,
        ];

        try {
            $response = Asset\Service::getStreamedResponseForThumbnail($config, $request->getPathInfo());
            if ($response) {
                return $response;
            }

            throw new Exception('Unable to generate '.$config['type'].' thumbnail, see logs for details.');
        } catch (Exception $e) {
            Logger::error($e->getMessage());

            return new RedirectResponse('/bundles/opendxpadmin/img/filetype-not-supported.svg');
        }
    }

    public function robotsTxtAction(Request $request): Response
    {
        // check for site
        $domain = \OpenDxp\Tool::getHostname();
        $site = Site::getByDomain($domain);

        $config = [];

        if (class_exists(Config::class)) {
            $config = Config::getRobotsConfig();
        }

        $siteId = 0;
        if ($site instanceof Site) {
            $siteId = $site->getId();
        }

        // send correct headers
        header('Content-Type: text/plain; charset=utf8');
        while (@ob_end_flush()) ;

        // check for configured robots.txt in opendxp
        $content = '';
        if (array_key_exists($siteId, $config)) {
            $content = $config[$siteId];
        }

        if (empty($content)) {
            // default behavior, allow robots to index everything
            $content = "User-agent: *\nDisallow:";
        }

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function commonFilesAction(Request $request): Response
    {
        return new Response("HTTP/1.1 404 Not Found\nFiltered by common files filter", 404);
    }

    public function versionAction(Request $request): Response
    {
        $version = htmlspecialchars(\OpenDxp\Version::getVersion(), ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="73" height="18" role="img" aria-label="{$version}">
                <title>{$version}</title>
                <filter id="blur">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="16" />
                </filter>
                <linearGradient id="s" x2="0" y2="100%">
                    <stop offset="0" stop-color="#fff" stop-opacity=".7" />
                    <stop offset=".1" stop-color="#aaa" stop-opacity=".1" />
                    <stop offset=".9" stop-color="#000" stop-opacity=".3" />
                    <stop offset="1" stop-color="#000" stop-opacity=".5" />
                </linearGradient>
                <clipPath id="r">
                    <rect width="73" height="18" rx="4" fill="#fff" />
                </clipPath>
                <g clip-path="url(#r)">
                    <rect width="18" height="18" fill="#DBFF00" />
                    <circle cx="9" cy="9" r="4.378" fill="black"/>
                    <rect x="18" width="55" height="18" fill="#555" />
                    <rect width="73" height="18" fill="url(#s)" />
                </g>
                <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif"
                    text-rendering="geometricPrecision" font-size="110">
                    <text aria-hidden="true" x="459" y="140" fill="#010101" fill-opacity=".80"
                        filter="url(#blur)" transform="scale(.1)">{$version}</text>
                    <text aria-hidden="true" x="459" y="140" fill="#010101" fill-opacity=".3"
                        transform="scale(.1)">{$version}</text>
                    <text x="459" y="130" transform="scale(.1)" fill="#fff">{$version}</text>
                </g>
            </svg>
            SVG;

        return new Response($svg, Response::HTTP_OK, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }

    public function customAdminEntryPointAction(Request $request): RedirectResponse
    {
        $params = $request->query->all();

        $url = match (true) {
            isset($params['token'])    => $this->generateUrl('opendxp_admin_login_check', $params),
            isset($params['deeplink']) => $this->generateUrl('opendxp_admin_login_deeplink', $params),
            default                    => $this->generateUrl('opendxp_admin_login', $params)
        };

        $redirect = new RedirectResponse($url);

        $customAdminPathIdentifier = $this->getParameter('opendxp_admin.custom_admin_path_identifier');
        if (!empty($customAdminPathIdentifier) && $request->cookies->get('opendxp_custom_admin') != $customAdminPathIdentifier) {
            $redirect->headers->setCookie(new Cookie('opendxp_custom_admin', $customAdminPathIdentifier, strtotime('+1 year')));
        }

        return $redirect;
    }
}
