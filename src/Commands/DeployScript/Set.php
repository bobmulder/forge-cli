<?php

namespace Sven\ForgeCLI\Commands\DeployScript;

use Sven\ForgeCLI\Commands\BaseCommand;
use Sven\ForgeCLI\Contracts\NeedsForge;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Set extends BaseCommand implements NeedsForge
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('deploy-script:set')
            ->addArgument('server', InputArgument::REQUIRED, 'The id of the server the site is on.')
            ->addArgument('site', InputArgument::REQUIRED, 'The id of the site you want to set the deployment script of.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'The file your new deployment script is in.')
            ->setDescription('Set the deployment script of the given site.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->forge->updateSiteDeploymentScript(
            $input->getArgument('server'), $input->getArgument('site'), $this->getFileContent($input, 'file')
        );

        return 0;
    }
}
