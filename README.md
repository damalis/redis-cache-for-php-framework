# RedisCache

Redis cache for php framework.

## Installation

composer require damalis/redis-cache-for-php-framework

## Usage

Cache every successful HTTP response for 24 hours in the local Redis server.

Example:

slim framework;

```
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

//...

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

$client = new \Predis\Client('tcp://localhost:6379', [
	'prefix' => $request->getUri()->getHost()
]);

$app->add(new \damalis\RedisCache\Cache($client, [
	'timeout' => 86400
]));

// ...

```
