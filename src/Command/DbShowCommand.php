<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbShowCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('db:show')
            ->setDescription('show database')
            ->addArgument(
                'dbname',
                InputArgument::REQUIRED,
                'Name of database'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbname = $input->getArgument('dbname');

        $url = getenv('DBDB_URL');
        $username = getenv('DBDB_USERNAME');
        $password = getenv('DBDB_PASSWORD');

        if (!$url || !$username || !$password) {
            throw new RuntimeException('Configuration incomplete');
        }

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url.'/api/v1/dbs/'.$dbname, [
             'auth' => [$username, $password],
        ]);

        echo $res->getBody();
    }
}
