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

namespace OpenDxp\Element;

use OpenDxp\Marshaller\MarshallerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

final readonly class MarshallerService
{
    private ServiceLocator $marshallerLocator;

    public function __construct(ServiceLocator $marshallerLocator)
    {
        $this->marshallerLocator = $marshallerLocator;
    }

    public function buildFieldefinitionMarshaller(string $format, string $name): MarshallerInterface
    {
        return $this->marshallerLocator->get($format . '_' . $name);
    }

    public function supportsFielddefinition(string $format, string $name): bool
    {
        return $this->marshallerLocator->has($format . '_' . $name);
    }
}
