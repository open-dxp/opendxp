<?php

declare(strict_types=1);

namespace OpenDxp\Test\Document;

use OpenDxp\Model\Document\Editable;

/**
 * A brick is one instance of an areabrick on a document, with the values of its editables.
 */
interface Brick
{
    /**
     * The id the areabrick is registered with.
     */
    public function id(): string;

    /**
     * The editables of the brick, by the name the areabrick gives them in its template.
     *
     * @return array<string, Editable>
     */
    public function editables(): array;
}
