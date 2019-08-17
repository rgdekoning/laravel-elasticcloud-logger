# Elastic Cloud Logger for Laravel / Lumen
Index logs to ElasticCloud


## Install elasticsearch dependency

```bash
$ composer install elasticsearch/elasticsearch
```

## Create the Logger and Handler

Create `app/Logging/HandlersElasticSearchHandler.php`  
[see here](https://github.com/rgdekoning/laravel-elasticcloud-logger/blob/master/app/Logging/Handlers/ElasticSearchHandler.php)

```php
<?php

namespace App\Logging\Handlers;

use Elasticsearch\Client;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * @see    https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/indexing_documents.html
 */
class ElasticSearchHandler extends AbstractProcessingHandler
{
    /**
     * @var string
     */
    private $index;

    /**
     * @var Client
     */
    private $client;

    /**
     * @param Client $client Elastic Search Client
     * @param string $index In which index is are the logs stored
     * @param int|string $level The minimum logging level to trigger this handler.
     * @param bool $bubble Whether or not messages that are handled should bubble up the stack.
     */
    public function __construct(
        Client $client,
        string $index,
        $level = Logger::INFO,
        bool $bubble = true
    ) {
        $this->client = $client;
        $this->index = $index;

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $this->send([
            'index' => $this->index,
            'type' => 'weird-deprecated-but-mandatory',
            'body' => $this->format($record),
        ]);
    }

    /**
     * Appends the '@timestamp' parameter
     *
     * @param array $record
     * @return array
     */
    protected function format(array $record)
    {
        if (isset($record["datetime"]) && ($record["datetime"] instanceof \DateTimeInterface)) {
            $record["@timestamp"] = $record["datetime"]->format(\DateTimeInterface::W3C);
            unset($record["datetime"]);
        }

        return $record;
    }

    /**
     * Send logging data to server
     *
     * @param array $data
     * @return void
     */
    protected function send(array $data)
    {
        $this->client->index($data);
    }
}
```

Create `app/Logging/ElasticSearchLogger.php`  
[see here](https://github.com/rgdekoning/laravel-elasticcloud-logger/blob/master/app/Logging/ElasticSearchLogger.php)
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
