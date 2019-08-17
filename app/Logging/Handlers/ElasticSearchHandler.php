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
