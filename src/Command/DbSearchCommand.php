<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbSearchCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('db:search')
            ->setDescription('Search database')
            ->addArgument(
                'keyword',
                InputArgument::REQUIRED,
                'Name of database'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $keyword = $input->getArgument('keyword');

        $url = getenv('DBDB_URL');
        $username = getenv('DBDB_USERNAME');
        $password = getenv('DBDB_PASSWORD');

        if (!$url || !$username || !$password) {
            throw new RuntimeException('Configuration incomplete');
        }

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url.'/api/v1/dbs/search/'.$keyword, [
             'auth' => [$username, $password],
        ]);

        echo $res->getBody();
    }
}
