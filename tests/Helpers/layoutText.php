<?php

declare(strict_types=1);

use OpenDxp\Model\DataObject\ClassDefinition\Layout\Text;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Tests\Factory\UnittestFactory;

function renderedLayoutText(string $html, ?Concrete $object = null): string
{
    $text = new Text();
    $text->setHtml($html);

    return $text
        ->enrichLayoutDefinition($object ?? UnittestFactory::createOne(['key' => 'layout-text-object']))
        ->getHtml();
}
