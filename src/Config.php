<?php

declare(strict_types=1);

namespace Maguto\Dilovod;

use Maguto\Dilovod\Exception\InvalidArgumentException;

final class Config
{
    /** @var string */
    public $apiKey;

    /** @var string */
    public $apiUrl;

    /** @var string */
    public $version;

    /** @var string|null */
    public $clientId;

    /**
     * @param string $apiKey API-ключ Dilovod
     * @param string $apiUrl URL ендпоінту API (або адреса VPS-сервера)
     * @param string $version Версія API
     * @param string|null $clientId Ідентифікатор клієнтського додатка (для партнерської статистики)
     */
    public function __construct(
        string $apiKey,
        string $apiUrl = 'https://api.dilovod.ua',
        string $version = '0.25',
        ?string $clientId = null
    ) {
        if ($apiKey === '') {
            throw new InvalidArgumentException('API key must not be empty.');
        }

        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->version = $version;
        $this->clientId = $clientId;
    }
}
