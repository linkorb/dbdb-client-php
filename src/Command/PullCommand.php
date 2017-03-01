<?php

namespace DbDb\Client\Command;

use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use RuntimeException;
use PDO;

class PullCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('pull')
            ->setDescription('Pull database from dbdb and load localy')
            ->addArgument(
                'dbname',
                InputArgument::OPTIONAL,
                'DbName'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeout = 60*30;

        $dbName = $input->getArgument('dbname');

        $output->writeLn("Loading db: " . $dbName);
        $url = getenv('DBDB_URL');
        $username = getenv('DBDB_USERNAME');
        $password = getenv('DBDB_PASSWORD');
        if (!$url || !$username || !$password) {
            throw new RuntimeException("Configuration incomplete");
        }


        $client = new \GuzzleHttp\Client();
        $res = $client->request('GET', $url . '/api/v1/dbs/' . $dbName . '/snapshots', [
            'auth' => [$username, $password]
        ]);
        $json = $res->getBody();
        $rows = json_decode($json, true);
        if (!$rows) {
            throw new RuntimeException("Snapshot index parsing failed");
        }
        $snapshot = null;
        foreach ($rows as $row) {
            $snapshot = $row;
        }
        if (!$snapshot) {
            throw new RuntimeException("Can't determine latest snapshot");
        }
        $tmpFilename = '/tmp/' . $dbName . '.sql.gz';
        $output->writeLn("Downloading snapshot #" . $snapshot['id'] . " - " . $snapshot['name']  . " to " . $tmpFilename);

        $res = $client->request('GET', $url . '/api/v1/snapshots/' . $snapshot['id'] . '/download', [
            'auth' => [$username, $password]
        ]);
        $data = $res->getBody();
        file_put_contents($tmpFilename, $data);


        $output->writeLn("Ensuring db");

        $cmd = 'mysql -e "create database ' . $dbName . '"';

        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->run();
        if ($process->isSuccessful()) {
            $output->writeLn("   Created");
        } else {
            $output->writeLn("   Already exists");
        }


        $cmd = 'gunzip < ' . $tmpFilename . ' | mysql ' . $dbName;
        $output->writeLn("Loading data");

        $process = new Process($cmd);
        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $output->writeLn("Done");

    }
}
