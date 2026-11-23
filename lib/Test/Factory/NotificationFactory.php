<?php

declare(strict_types=1);

namespace OpenDxp\Test\Factory;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Notification;
use OpenDxp\Model\User;

/**
 * @extends AbstractSavingFactory<Notification>
 */
final class NotificationFactory extends AbstractSavingFactory
{
    public static function class(): string
    {
        return Notification::class;
    }

    public function forRecipient(User $recipient): static
    {
        return $this->with(['recipient' => $recipient]);
    }

    public function withLinkedElement(ElementInterface $element): static
    {
        return $this->with(['linkedElement' => $element]);
    }

    protected function defaults(): array
    {
        return [
            'recipient' => UserFactory::new(),
            'title'     => self::faker()->sentence(),
            'message'   => self::faker()->paragraph(),
        ];
    }
}
