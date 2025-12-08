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

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use OpenDxp\Config;

/**
 * @internal
 */
class ClientFactory
{
    public function __construct(protected Config $config)
    {
    }

    public function createClient(array $config = []): Client
    {
        $guzzleConfig = [
            RequestOptions::TIMEOUT => 3600,
            RequestOptions::VERIFY => CaBundle::getSystemCaRootBundlePath(),
        ];

        if (($this->config['httpclient']['adapter'] ?? null) == 'Proxy') {
            $authorization = '';
            if (!empty($this->config['httpclient']['proxy_user'])) {
                $authorization = $this->config['httpclient']['proxy_user'] . ':' . $this->config['httpclient']['proxy_pass'] . '@';
            }

            $protocol = 'tcp';
            if (function_exists('curl_exec')) {
                // this is a workaround for https://github.com/pimcore/pimcore/issues/3835
                $protocol = 'http';
            }

            $proxyUri = $protocol . '://' . $authorization . ($this->config['httpclient']['proxy_host'] ?? '') . ':' . ($this->config['httpclient']['proxy_port'] ?? '');

            $guzzleConfig[RequestOptions::PROXY] = $proxyUri;
        }

        $guzzleConfig = [...$guzzleConfig, ...$config];

        return new Client($guzzleConfig);
    }
}
