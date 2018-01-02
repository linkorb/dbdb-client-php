<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class DbSearchCommand extends AbstractDbCommand
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

        $this->apiUrl = '/api/v1/dbs/search/'.$keyword;
        $res = parent::execute($input, $output);

        $result = json_decode($res->getBody());

        if ($result) {
            $table = new Table($output);
            $header = array();
            foreach ($result[0] as $key => $value) {
                $header[] = $key;
            }
            $table->setHeaders($header);

            foreach ($result as $row) {
                $rowArray = array();
                foreach ($row as $key => $val) {
                    $rowArray[] = $val;
                }
                $table->addRow($rowArray);
            }

            $table->render();
        } else {
            echo $res->getBody();
        }
    }
}
