<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbPropertyCommand extends AbstractDbCommand
{
    protected function configure()
    {
        $this
            ->setName('db:property')
            ->setDescription('update database property')
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

        $this->apiUrl = '/api/v1/dbs/'.$dbname.'/property/'.$key.'/'.$value;
        $res = parent::execute($input, $output);

        echo $res->getBody();
    }
}
