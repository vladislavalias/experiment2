<?php

namespace Alias\TestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Command
{

  protected function configure()
  {
    $this
      ->setName('worker:start')
      ->setDescription('Parser worker.')
      ->addArgument(
        'site', InputArgument::OPTIONAL, 'From what site need parse?'
      )
      ->addOption(
        'api', null, InputOption::VALUE_NONE, 'Use api or no?'
      )
    ;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln('test test');
  }
}
