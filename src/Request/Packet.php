<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Request;

use JsonSerializable;
use Maguto\Dilovod\Enum\Action;

/**
 * Незмінний пакет запиту до Dilovod API.
 */
final readonly class Packet implements JsonSerializable
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public string $version,
        public string $key,
        public Action $action,
        public array $params,
        public ?string $clientId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $data = [
            'version' => $this->version,
            'key' => $this->key,
            'action' => $this->action->value,
            'params' => $this->params,
        ];

        if ($this->clientId !== null) {
            $data['clientID'] = $this->clientId;
        }

        return $data;
    }

    /**
     * Повертає копію пакета з замаскованим API-ключем (для логування).
     */
    public function withMaskedKey(): self
    {
        $masked = str_repeat('*', max(0, \strlen($this->key) - 4))
            . substr($this->key, -4);

        return new self(
            $this->version,
            $masked,
            $this->action,
            $this->params,
            $this->clientId,
        );
    }
}
