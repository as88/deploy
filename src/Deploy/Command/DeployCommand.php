<?php
namespace Deploy\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Process\Process;

class DeployCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('up')
            ->setDescription('Deploy the application')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Deployment of application</info>");
        $output->writeln("- Reading configuration from file");
   
        $cwd = getcwd();
        $configFileName = $cwd . '/deploy.yml';
        
        if (!file_exists($configFileName)) {
            $output->writeln("<error>An error occured: The file " . $configFileName . " cannot be found.");
            return -1;
        }
 
        $yaml = new Parser();
        $config = $yaml->parse(file_get_contents($configFileName));

        $releaseId = date("YmdHis");
        $releaseDir = "../releases/" . $releaseId . "/";
        $output->writeln("- Release directory: " . $releaseDir);
        $output->writeln("- Release ID: " . $releaseId);

        $output->writeln("Starting deployment process");
        if (false === mkdir($releaseDir)) {
            $output->writeln("<error>Release directory cannot be created!</error>");
            return -1;
        }

        $output->writeln("Checking out the master-revision from the SCM");
        if (isset($config['scm']) && isset($config['scm']['svn'])) {
            (new Process('svn export --force ' . $config['scm']['svn']['url'] . ' ' . $releaseDir))->mustRun();
        }

        $output->writeln("Creating all the symlinks to the shared folders");
        if (isset($config['shared'])) {
            foreach ($config['shared'] as $link) {
               $output->writeln(' - ' . $link);
               $countFolders = substr_count($link, '/');
               $target = str_repeat('../', $countFolders+2) . 'shared/' . $link;
               (new Process('ln -s '. $target . ' ' . $releaseDir . $link))->mustRun();
            }
        }
    
        $output->writeln("Symlinking current to the new release");
        (new Process('ln -s -f releases/' . $releaseId . ' ../current'))->mustRun();
 
        $output->writeln('<info>Successfully deployed new revision</info>');
    }
}

