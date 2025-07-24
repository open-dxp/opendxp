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

use OpenDxp\Bootstrap;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\Request;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

Bootstrap::setProjectRoot();

return function (Request $request, array $context) {

    // set current request as property on tool as there's no
    // request stack available yet
    Tool::setCurrentRequest($request);

    Bootstrap::bootstrap();
    $kernel = Bootstrap::kernel();

    // reset current request - will be read from request stack from now on
    Tool::setCurrentRequest(null);

    return $kernel;
};
