# Elastic Cloud Logger for Laravel / Lumen
Index logs to ElasticCloud


## Install elasticsearch dependency

```bash
$ composer install elasticsearch/elasticsearch
```

## Create the Logger and Handler
Create `app/Logging/ElasticSearchLogger.php`
```php
<?php

namespace App\Logging;

use Elasticsearch\ClientBuilder;
use App\Logging\Handlers\ElasticSearchHandler;
use Monolog\Logger;

class ElasticSearchLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array $config
     * @return \Monolog\Logger
     * @throws \Exception
     */
    public function __invoke(array $config)
    {
        $host = [
            'user'   => $config['user'],
            'pass'   => $config['pass'],
            'host'   => $config['host'] ?? '127.0.0.1',
            'scheme' => $config['scheme'] ?? 'https',
            'port'   => $config['port'] ?? 9200,
        ];

        $client = ClientBuilder::create()
            ->setHosts([ $host ])
            ->build();

        $logger = new Logger(env('APP_NAME'));
        $handler = new ElasticSearchHandler($client, $config['index']);

        $logger->pushHandler(
            $handler
        );

        return $logger;
    }
}

```

Create `app/Logging/Handlers/ElasticSearchHandler.php`


## Config

Now the config...  

Add your user and pass to your `.env`

```
ELASTIC_SEARCH_USER=your-user
ELASTIC_SEARCH_PASS=your-pass
```

Add elastic to your channels in the config file `config/logging.php`

```php
'channels' => [

    [...],
    
    'elastic' => [
        'driver' => 'custom',
        'via' => App\Logging\ElasticSearchLogger::class,
        'scheme' => 'https',
        'host' => 'YOUR-HOST.cloud.es.io',
        'port' => 443,
        'index' => 'YOUR-INDEX-NAME',
        'user' => env('ELASTIC_SEARCH_USER'),
        'pass' => env('ELASTIC_SEARCH_PASS'),
    ],
],
```

## Indexing
To index a log entry, try:

```php
Log::channel('elastic')->info('A log entry');
```

## Indexing all logs
You could set your default log-channel to `elastic`, but when your default 
log-channel is `stack`, just add `elastic` to the channels array in the stack-channel.

```php
'default' => 'stack',
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'name' => env('APP_ENV', 'production'),
        'channels' => ['daily', 'elastic'],
        'ignore_exceptions' => false,
    ],
    
    [...],
    
    'elastic' => [
        'driver' => 'custom',
        'via' => App\Logging\ElasticSearchLogger::class,
        'scheme' => 'https',
        'host' => 'YOUR-HOST.cloud.es.io',
        'port' => 443,
        'index' => 'YOUR-INDEX-NAME',
        'user' => env('ELASTIC_SEARCH_USER'),
        'pass' => env('ELASTIC_SEARCH_PASS'),
    ],
],
```

Your logs should now be indexed by Elastic and you can setup kibana to create some nice dashbaords!
