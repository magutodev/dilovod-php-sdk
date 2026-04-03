<?php

declare(strict_types=1);

namespace Maguto\Dilovod\Transport;

use Maguto\Dilovod\Exception\ApiException;
use Maguto\Dilovod\Exception\TransportException;
use Maguto\Dilovod\Request\Packet;
use Maguto\Dilovod\Response\ResponseData;

interface TransportInterface
{
    /**
     * Відправляє пакет до Dilovod API та повертає розпарсену відповідь.
     *
     * @throws TransportException при мережевих помилках
     * @throws ApiException при помилках Dilovod API
     */
    public function send(Packet $packet): ResponseData;
}
