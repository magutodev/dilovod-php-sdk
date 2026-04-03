<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Webhook;

/**
 * DTO події вебхука від Dilovod.
 */
final readonly class WebhookEvent
{
    public function __construct(
        public string $action,
        public string $objectName,
        public string $id,
    ) {}
}
