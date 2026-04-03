# Dilovod PHP SDK

[![CI](https://github.com/magutodev/dilovod-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/magutodev/dilovod-php-sdk/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/maguto/dilovod-sdk/v)](https://packagist.org/packages/maguto/dilovod-sdk)
[![PHP Version](https://img.shields.io/packagist/dependency-v/maguto/dilovod-sdk/php)](https://packagist.org/packages/maguto/dilovod-sdk)
[![License](https://img.shields.io/packagist/l/maguto/dilovod-sdk)](LICENSE)

PHP SDK для роботи з [Dilovod API](https://help.dilovod.ua/uk/article/api-dilovod-1gwt3m0/) — українським онлайн-сервісом бухгалтерського, управлінського обліку та звітності.

> **Версія 1.x** — сумісна з PHP 7.4+. Для PHP 8.2+ з сучасним синтаксисом дивіться гілку [`2.x`](https://github.com/magutodev/dilovod-php-sdk/tree/2.x).

## Встановлення

```bash
composer require maguto/dilovod-sdk
```

SDK потребує реалізації PSR-18 (HTTP client) та PSR-17 (HTTP factories). Рекомендовані пакети:

```bash
# Guzzle (найпопулярніший)
composer require guzzlehttp/guzzle nyholm/psr7

# або тільки Nyholm + Symfony HTTP Client
composer require nyholm/psr7 symfony/http-client
```

## Швидкий старт

```php
use Maguto\Dilovod\Config;
use Maguto\Dilovod\DilovodClient;
use Maguto\Dilovod\Enum\Operator;
use Maguto\Dilovod\Transport\PsrTransport;
use GuzzleHttp\Client as GuzzleClient;
use Nyholm\Psr7\Factory\Psr17Factory;

// Конфігурація
$config = new Config('ваш_api_ключ');
// або з усіма параметрами:
// $config = new Config('ваш_api_ключ', 'https://vps.example.com', '0.25', 'my-app');

// Ініціалізація
$psr17 = new Psr17Factory();
$transport = new PsrTransport(
    $config,
    new GuzzleClient(['timeout' => 30]),
    $psr17,
    $psr17
    // $psrLogger  // опціонально, PSR-3
);

$client = new DilovodClient($config, $transport);
```

## Основні методи

### Отримання об'єкта за ID

```php
$product = $client->getObject('1100300000022632');

$header = $product->get('header');
echo $header['name']['uk'];
```

### Збереження об'єкта

```php
// Створення
$id = $client->saveObject([
    'id' => 'catalogs.goods',
    'name' => ['uk' => 'Новий товар', 'ru' => 'Новый товар'],
]);

// Оновлення
$client->saveObject(['id' => $id, 'name' => ['uk' => 'Оновлена назва']]);

// Створення та проведення документа
use Maguto\Dilovod\Enum\SaveType;

$client->saveObject(
    [
        'id' => 'documents.saleOrder',
        'firm' => '1100400000001001',
        'person' => '1100100000001001',
        'currency' => '1101200000001001',
        'date' => '2026-04-03 14:00:00',
    ],
    [],
    SaveType::register()
);
```

### Позначка на видалення

```php
$client->setDelMark('1100300000022632');
```

## Query Builder

Fluent API для вибірки даних. Підтримує 5 типів запитів.

> **Важливо:** alias, що використовується у `where()`, повинен бути оголошений у `fields()`.

### Прямий запит до довідника

```php
$products = $client->query('catalogs.goods')
    ->fields(['id' => 'good', 'code' => 'code', 'name' => 'name', 'parent.code' => 'parentCode'])
    ->where('parentCode', Operator::equal(), 101010)
    ->limit(50)
    ->get();

foreach ($products as $product) {
    echo $product['name'] . PHP_EOL;
}

// Отримати перший запис
$first = $client->query('catalogs.firms')
    ->fields(['id' => 'id', 'name' => 'name'])
    ->first();

// Отримати значення одного поля з усіх записів
$codes = $client->query('catalogs.goods')
    ->fields(['id' => 'id', 'code' => 'code'])
    ->withoutLinks()
    ->get()
    ->pluck('code');
```

### Товарні залишки на дату

```php
$stock = $client->query()
    ->balance('goods', '2025-01-01 00:00:00', ['good', 'storage'])
    ->fields([
        'good' => 'good',
        'good.code' => 'code',
        'storage' => 'storage',
        'qty' => 'qty',
    ])
    ->where('good', Operator::equal(), '1100300000022619')
    ->get();
```

### Обороти за період

```php
$sales = $client->query()
    ->turnover('saleIncomes', '2025-01-01', '2025-06-30', ['good', 'firm'])
    ->fields([
        'good' => 'good',
        'good.code' => 'code',
        'amountReciept' => 'income',
    ])
    ->get();
```

### Залишки + обороти

```php
$report = $client->query()
    ->balanceAndTurnover('goods', '2025-01-01', '2025-06-30')
    ->fields([
        'good' => 'good',
        'qtyStart' => 'start',
        'qtyReceipt' => 'receipt',
        'qtyExpense' => 'expense',
        'qtyFinal' => 'final',
    ])
    ->get();
```

### Зріз актуальних значень (ціни)

```php
$prices = $client->query()
    ->sliceLast('goodsPrices', new \DateTimeImmutable())
    ->fields([
        'good' => 'good',
        'priceType' => 'priceType',
        'price' => 'price',
        'currency' => 'currency',
    ])
    ->where('priceType', Operator::equal(), '1101300000001002')
    ->get();
```

### Мультимовні поля

При `multilang()` API змінює формат імен полів: `name` → `name__uk`, `name__ru`.

```php
$result = $client->query('catalogs.firms')
    ->fields(['id' => 'id', 'name' => 'name'])
    ->multilang()
    ->get();

// $result->first() = ['id' => '...', 'name__uk' => '...', 'name__ru' => '...']
```

### Швидкий режим (без збірки посилань)

```php
$result = $client->query('catalogs.goods')
    ->fields(['id' => 'id', 'code' => 'code', 'name' => 'name'])
    ->withoutLinks()
    ->get();

// ResultSet містить columns
$result->getColumns(); // ['id', 'code', 'name']
```

## Створення замовлення покупця

```php
$orderId = $client->createOrder()
    ->firm('1100400000001001')
    ->person('1100100000001001')
    ->remarkFromPerson('Зателефонуйте, будь ласка')
    ->addProduct('1100300000022632', 1)
    ->addProduct('1100300000022876', 2, 150.00)
    ->addProductByArticle('ART-001', 3)
    ->withAutoPlacement()
    ->send();
```

## Довільні спецметоди (call)

```php
$response = $client->call('saleOrderCreate', [
    'header' => ['firm' => '1100400000001001', 'person' => '1100100000001001'],
    'goods' => [['good' => '1100300000022632', 'qty' => 1]],
]);
```

## Метадані

```php
// Список усіх об'єктів системи
$objects = $client->listMetadata('uk');

// Опис реквізитів об'єкта
$meta = $client->getMetadata('catalogs.goods');
// або за ID
$meta = $client->getMetadata(null, '1000000000001258');
```

## Партнерська статистика

> **Увага:** метод `getStatistic` реалізовано за документацією, але не верифіковано реальним API (потребує налаштованої партнерської інтеграції).

```php
$stats = $client->getStatistic(
    'ваш_партнерський_ключ',
    new \DateTimeImmutable('2025-01-01'),
    new \DateTimeImmutable('2025-12-31')
);
```

## Webhook

```php
use Maguto\Dilovod\Webhook\WebhookParser;

// У вашому контролері
$packetJson = $_GET['packet'] ?? '';

if (!WebhookParser::isValidSource($_SERVER['REMOTE_ADDR'])) {
    http_response_code(403);
    exit;
}

$event = WebhookParser::parse($packetJson);

echo $event->action;     // 'objectChanged'
echo $event->objectName; // 'documents.saleOrder'
echo $event->id;         // '1109100000001038'
```

## Value Objects

```php
use Maguto\Dilovod\ValueObject\ObjectId;
use Maguto\Dilovod\ValueObject\MultiLangString;

// ObjectId — 16-значний ID з типом та номером
$id = new ObjectId('1100300000022632');
$id->getPrefix(); // '11003' (тип — товари)
$id->getNumber(); // '00000022632'
$id->isSameType(new ObjectId('1100300000022876')); // true

// MultiLangString — мультимовний рядок
$name = MultiLangString::fromArray(['uk' => 'Товар', 'ru' => 'Товар']);
echo $name->get('uk');   // 'Товар'
echo $name;              // 'Товар' (uk за замовчуванням)
```

## Обробка помилок

```php
use Maguto\Dilovod\Exception\ApiException;
use Maguto\Dilovod\Exception\TransportException;
use Maguto\Dilovod\Exception\DilovodException;

try {
    $client->getObject('0000000000000000');
} catch (ApiException $e) {
    // Помилка від Dilovod API
    echo $e->getMessage();        // 'object with id ... not found'
    $e->getRawResponse();         // повний масив відповіді
} catch (TransportException $e) {
    // Мережева помилка або HTTP 5xx
    echo $e->getHttpStatusCode(); // 502, null для мережевих
} catch (DilovodException $e) {
    // Будь-яка помилка SDK
}
```

## Розробка

```bash
# Встановити залежності
composer install

# Запустити всі перевірки (стиль + аналіз + тести)
composer check

# Unit-тести
composer test:unit

# Інтеграційні тести (потребують API-ключ)
cp .env.example .env    # вкажіть DILOVOD_API_KEY
composer test:integration

# Виправити стиль коду
composer cs:fix

# Статичний аналіз (PHPStan level max)
composer analyse
```

## Вимоги

- PHP 7.4+
- Будь-яка реалізація PSR-18 HTTP client
- Будь-яка реалізація PSR-17 HTTP factories

## Посилання

- [Офіційна документація Dilovod API](https://help.dilovod.ua/uk/article/api-dilovod-1gwt3m0/)
- [FAQ по API](https://help.dilovod.ua/uk/article/api-dilovod-zapitannya-i-vidpovidi-lkkiux/)
- [Telegram-канал API](https://t.me/dilovodapi)

## Ліцензія

MIT — дивіться [LICENSE](LICENSE).
