<?php

declare(strict_types=1);

namespace OpenDxp\Tests\Unit\Cache;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

beforeEach(function () {
    $this->handler = cacheHandler(new TagAwareAdapter(new ArrayAdapter()));
});

it('caches a tag again that an earlier request cleared', function () {
    $this->handler->clearTags(['cleared_tag']);
    $this->handler->save('refusedKey', 'refused-data', ['cleared_tag']);
    $this->handler->reset();
    $this->handler->save('acceptedKey', 'accepted-data', ['cleared_tag']);

    expect($this->handler->load('refusedKey'))
        ->toBeFalse()
        ->and($this->handler->load('acceptedKey'))
        ->toBe('accepted-data');
});
