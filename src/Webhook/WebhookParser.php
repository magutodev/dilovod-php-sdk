<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Webhook;

use JsonException;
use Maguto\Dilovod\Exception\InvalidArgumentException;

/**
 * Парсер вхідних вебхуків від Dilovod.
 *
 * Вебхук приходить GET-запитом з параметром "packet" (JSON).
 * IP відправника: 88.198.50.253
 */
final class WebhookParser
{
    public const DILOVOD_WEBHOOK_IP = '88.198.50.253';

    /**
     * Розпарсити вебхук з GET-параметра "packet".
     *
     * @throws InvalidArgumentException якщо JSON невалідний або відсутні обов'язкові поля
     */
    public static function parse(string $packetJson): WebhookEvent
    {
        try {
            $data = json_decode($packetJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'Invalid webhook JSON: ' . $e->getMessage(),
            );
        }

        if (!\is_array($data)) {
            throw new InvalidArgumentException('Webhook packet must be a JSON object.');
        }

        $action = $data['action'] ?? null;
        $params = $data['params'] ?? null;

        if (!\is_string($action) || !\is_array($params)) {
            throw new InvalidArgumentException(
                'Webhook packet must contain "action" (string) and "params" (object).',
            );
        }

        $objectName = $params['objectName'] ?? null;
        $id = $params['id'] ?? null;

        if (!\is_string($objectName) || !\is_string($id)) {
            throw new InvalidArgumentException(
                'Webhook params must contain "objectName" and "id" strings.',
            );
        }

        return new WebhookEvent($action, $objectName, $id);
    }

    /**
     * Перевірити IP відправника.
     */
    public static function isValidSource(string $ip): bool
    {
        return $ip === self::DILOVOD_WEBHOOK_IP;
    }
}
