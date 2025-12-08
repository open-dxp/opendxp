<?php

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

namespace OpenDxp\Controller\Traits;

use OpenDxp\Serializer\Serializer as OpenDxpSerializer;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @property ContainerInterface $container
 */
trait JsonHelperTrait
{
    protected OpenDxpSerializer $openDxpSerializer;

    #[Required]
    public function setOpenDxpSerializer(OpenDxpSerializer $openDxpSerializer): void
    {
        $this->openDxpSerializer = $openDxpSerializer;
    }

    /**
     * Returns a JsonResponse that uses the admin serializer
     *
     * @param mixed $data    The response data
     * @param int $status    The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     *
     */
    public function jsonResponse(mixed $data, int $status = 200, array $headers = [], array $context = [], bool $useOpenDxpSerializer = true): JsonResponse
    {
        $json = $this->encodeJson($data, $context, JsonResponse::DEFAULT_ENCODING_OPTIONS, $useOpenDxpSerializer);

        return new JsonResponse($json, $status, $headers, true);
    }

    /**
     * Encodes data into JSON string
     *
     * @param mixed $data    The data to be encoded
     * @param array $context Context to pass to serializer when using serializer component
     * @param int $options   Options passed to json_encode
     */
    public function encodeJson(mixed $data, array $context = [], int $options = JsonResponse::DEFAULT_ENCODING_OPTIONS, bool $useOpenDxpSerializer = true): string
    {
        $serializer = $useOpenDxpSerializer ? $this->openDxpSerializer : $this->container->get('serializer');

        return $serializer->serialize($data, 'json', ['json_encode_options' => $options, ...$context]);
    }

    /**
     * Decodes a JSON string into an array/object
     *
     * @param mixed $json       The data to be decoded
     * @param bool $associative Whether to decode into associative array or object
     * @param array $context    Context to pass to serializer when using serializer component
     */
    public function decodeJson(mixed $json, bool $associative = true, array $context = [], bool $useOpenDxpSerializer = true): mixed
    {
        $serializer = $useOpenDxpSerializer ? $this->openDxpSerializer : $this->container->get('serializer');

        if ($associative) {
            $context['json_decode_associative'] = true;
        }

        // @phpstan-ignore-next-line
        return $serializer->decode($json, 'json', $context);
    }
}
