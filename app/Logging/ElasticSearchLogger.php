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
