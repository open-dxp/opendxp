<?php

declare(strict_types=1);

namespace OpenDxp\Test;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Element\ElementInterface;

final class Elements
{
    /**
     * @template T of ElementInterface
     *
     * @param T $element
     *
     * @return T
     */
    public static function refresh(ElementInterface $element): ElementInterface
    {
        RuntimeCache::clear();

        return $element::getById($element->getId(), ['force' => true]);
    }
}
