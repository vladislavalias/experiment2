<?php

namespace Alias\LegacyBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;

class CacheCommand extends CacheClearCommand
{

  protected function configure()
  {
    parent::configure();
    
    $this
      ->setName('cc')
      ->setDescription('Clear cache.')
    ;
  }
}
