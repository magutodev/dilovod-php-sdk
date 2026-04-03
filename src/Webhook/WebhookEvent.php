<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Webhook;

/**
 * DTO події вебхука від Dilovod.
 */
final class WebhookEvent
{
    /** @var string */
    public $action;

    /** @var string */
    public $objectName;

    /** @var string */
    public $id;

    public function __construct(string $action, string $objectName, string $id)
    {
        $this->action = $action;
        $this->objectName = $objectName;
        $this->id = $id;
    }
}
