<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SnapshotFullCommand extends AbstractDbCommand
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbname = $input->getArgument('dbname');

        $this->apiUrl = '/api/v1/snapshots/'.$dbname;
        $res = parent::execute($input, $output);

        echo $res->getBody();
    }
}
