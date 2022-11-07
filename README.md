# RedisCache

Redis cache for slim framework.

## Installation

composer require damalis/redis-cache-for-slim-framework

## Usage

Cache every successful HTTP response for 24 hours in the local Redis server.

Example:

```
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

//...

$client = new \Predis\Client('tcp://localhost:6379', [
	'prefix' => $request->getUri()->getHost()
]);

$app->add(new \damalis\RedisCache\Cache($client, [
	'timeout' => 86400
]));

// ...

```
