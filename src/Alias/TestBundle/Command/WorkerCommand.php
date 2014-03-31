<?php

namespace Alias\TestBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Alias\TestBundle\DependencyInjection\Parser\YandexParser;
use Alias\TestBundle\DependencyInjection\SimpleHtmlDom;

class WorkerCommand extends ContainerAwareCommand
{

  static $defaultConfiguration = array(
    'max_pages'         => 3,
    'max_pages_per_run' => 1
  );
  
  protected $products;
  
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
    $tasks = $this->getTasks();
    
    foreach ($tasks as $task)
    {
      $configuration  = $this->getConfiguration($task);
      $type           = $this->getParserType($configuration);
      $worker         = $this->runTask($configuration, $type);
      
      $this->saveTask($task, $worker);
      $this->saveProducts($this->getProducts());
    }
    
    $output->writeln('test test');
  }
  
  protected function saveProducts($products)
  {
    return true;
  }

  protected function saveTask($task, $worker)
  {
    return true;
  }

  protected function runTask($configuration, $type)
  {
    $className  = sprintf('Alias\TestBundle\DependencyInjection\Parser\%sParser', ucfirst($type));
    
    if (class_exists($className))
    {
      $parser = unserialize(trim($configuration['statement'], '"'));
      
      if (!$parser)
      {
        $parser = new $className($configuration);
      }
      
      $parser->run();
      $this->setProducts($parser->getProducts());
    }
    
    return $parser->finalize();
  }
  
  protected function getConfiguration($task)
  {
    return array_merge(self::$defaultConfiguration, $task->toArray());
  }

  /**
   * Заглушка.
   * 
   * @param array $configuration
   * @return string
   */
  protected function getParserType($configuration)
  {
    return 'Yandex';
  }

  protected function getTasks()
  {
    $doctrine = $this->getContainer()->get('doctrine');
    
    return $doctrine->getRepository('AliasTestBundle:Task')
      ->findAll();
  }
  
  protected function setProducts($products)
  {
    return $this->products = $products;
  }
  protected function getProducts()
  {
    return $this->products;
  }
}


if (!function_exists('dump'))
{
  function dump($values, $exit = true)
  {
    var_dump($values);
    if ($exit) exit();
  }
}