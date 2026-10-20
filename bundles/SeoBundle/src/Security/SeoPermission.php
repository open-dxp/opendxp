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

namespace OpenDxp\Bundle\SeoBundle\Security;

enum SeoPermission: string
{
    /**
     * @deprecated since 1.5 and will be removed in 2.0, use SiteDeclarations instead
     */
    case RobotsTxt          = 'opendxp:security:permission:robots.txt';

    case SeoDocumentEditor  = 'opendxp:security:permission:seo_document_editor';
    case HttpErrors         = 'opendxp:security:permission:http_errors';
    case ProtectedRedirects = 'opendxp:security:permission:redirects_protected';
    case SiteDeclarations   = 'opendxp:security:permission:site_declarations';
}
