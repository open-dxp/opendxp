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

namespace OpenDxp\Tests\Support\Test;

use OpenDxp;
use OpenDxp\Tests\Support\ModelTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @property ModelTester $tester
 *
 * @deprecated since OpenDXP 1.5 and will be removed in 2.0
 */
abstract class ModelTestCase extends TestCase
{
    private ?SessionInterface $session = null;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->needsDb()) {
            $this->setUpTestClasses();
        }
    }

    /**
     * Set up test classes before running tests
     */
    protected function setUpTestClasses(): void
    {
    }

    protected function needsDb(): bool
    {
        return true;
    }

    protected function buildSession(): SessionInterface
    {
        if (null === $this->session) {
            $this->session = new Session(new MockArraySessionStorage());

            $requestStack = OpenDxp::getContainer()->get('request_stack');
            if (!$request = $requestStack->getCurrentRequest()) {
                $request = new Request();
                $requestStack->push($request);
            }

            $request->setSession($this->session);
        }

        return $this->session;
    }
}
