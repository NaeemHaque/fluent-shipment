<?php

namespace Dev\Cli\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;

class WPShellCommand extends BaseCommand
{
    protected static $defaultName = 'app:shell';

    protected function configure()
    {
        $this
            ->setAliases(['shell'])
            ->setDescription('Launches an interactive WP-CLI shell.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find('app:activate');

        $arguments = ['command' => $command->getName()];

        $command->run(new ArrayInput($arguments), new BufferedOutput());

        $command = 'wp shell --quiet';

        passthru($command);

        return static::SUCCESS;
    }
}
