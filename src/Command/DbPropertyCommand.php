<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbPropertyCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('snapshot:full')
            ->setDescription('Load database snapshot')
            ->addArgument(
                'dbname',
                InputArgument::REQUIRED,
                'Name of database'
            )
            ->addArgument(
                'key',
                InputArgument::REQUIRED,
                'Name of database property'
            )
            ->addArgument(
                'value',
                InputArgument::REQUIRED,
                'Name of database property value'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbname = $input->getArgument('dbname');
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');

        $url = getenv('DBDB_URL');
        $username = getenv('DBDB_USERNAME');
        $password = getenv('DBDB_PASSWORD');

        if (!$url || !$username || !$password) {
            throw new RuntimeException('Configuration incomplete');
        }

        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url.'/api/v1/dbs/'.$dbname.'/property/'.$key.'/'.$value, [
             'auth' => [$username, $password],
        ]);

        echo $res->getBody();
    }
}
