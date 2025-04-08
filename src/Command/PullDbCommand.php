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

class PullDbCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this
            ->setName('pull:db')
            ->setDescription('Pull database from dbdb and load localy')
            ->addArgument(
                'source_dbname',
                InputArgument::REQUIRED,
                'Source DbName'
            )
            ->addArgument(
                'target_dbname',
                InputArgument::OPTIONAL,
                'Target DbName'
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $timeout = 60*30;

        $sourceDbName = $input->getArgument('source_dbname');
        $targetDbName = $input->getArgument('target_dbname') ?? $sourceDbName;

        $output->writeLn("Loading db: " . $sourceDbName);
        $url = getenv('DBDB_URL');
        $username = getenv('DBDB_USERNAME');
        $password = getenv('DBDB_PASSWORD');
        if (!$url || !$username || !$password) {
            throw new RuntimeException("Configuration incomplete");
        }


        $client = new \GuzzleHttp\Client();
        $fullUrl = $url . '/api/v1/dbs/' . $sourceDbName . '/snapshots';
        $res = $client->request('GET', $fullUrl, [
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
        $tmpFilename = '/tmp/' . $sourceDbName . '.sql.gz';
        $output->writeLn("Downloading snapshot #" . $snapshot['id'] . " - " . $snapshot['name']  . " to " . $tmpFilename);

        $lastStamp = time();

        $fullUrl=  $url . '/api/v1/snapshots/' . $snapshot['id'] . '/download';

        $res = $client->request('GET', $fullUrl, [
            'auth' => [$username, $password],
            'sink' => $tmpFilename,
            'progress' => function ($dl_total_size, $dl_size_so_far, $ul_total_size, $ul_size_so_far) use (&$lastStamp){
                if ($lastStamp!=time()) {
                    $p = '?';
                    if ($dl_total_size>0) {
                        $p = round($dl_size_so_far / $dl_total_size * 100, 1);
                    }
                    echo $dl_total_size . '/' . $dl_size_so_far . '=' . $p . "%\n";
                    $lastStamp = time();
                }
            }
        ]);
        //$data = $res->getBody();
        //file_put_contents($tmpFilename, $data);

        $cmd = 'mysql -e "CREATE DATABASE IF NOT EXISTS ' . $targetDbName . '"';
        exec($cmd, $stdout, $return_var);
        print_r($stdout);

        // $process = new Process([$cmd]);
        // $process->setTimeout($timeout);
        // $process->setIdleTimeout($timeout);
        // $process->run();
        // if ($process->isSuccessful()) {
        //     $output->writeLn("Created $targetDbName");
        // } else {
        //     $output->writeLn("$targetDbName already exists");
        // }


        $cmd = 'zcat ' . $tmpFilename . ' | grep -v "\!99999" | mysql ' . $targetDbName;
        $output->writeLn("Importing data");
        echo $cmd . PHP_EOL;

        @exec($cmd, $stdout, $return_var);
        print_r($stdout);

        // TODO: restore use of process component - currently broken
        // $process = new Process([$cmd]);
        // $process->setTimeout($timeout);
        // $process->setIdleTimeout($timeout);
        // $process->run();
        // if (!$process->isSuccessful()) {
        //     throw new ProcessFailedException($process);
        // }

        $output->writeLn("Done");
        return Command::SUCCESS;
    }
}
