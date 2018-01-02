<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

abstract class AbstractDbCommand extends Command
{
    protected $apiUrl;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = getenv('DBDB_URL');
        $username = getenv('DBDB_USERNAME');
        $password = getenv('DBDB_PASSWORD');

        if (!$url || !$username || !$password) {
            throw new RuntimeException('Configuration incomplete');
        }

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url.$this->apiUrl, [
             'auth' => [$username, $password],
        ]);

        return $res;
    }
}
