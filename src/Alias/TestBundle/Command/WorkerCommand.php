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
use Alias\TestBundle\Entity\Product;

class WorkerCommand extends ContainerAwareCommand
{
  CONST CONFIG_FILTER_PRODUCT_BY = 'link';

  static $defaultConfiguration = array(
    'max_pages'         => 3,
    'max_pages_per_run' => 1
  );
  
  protected $products;
  protected $output;


  static $statuses = array(
    'start'   => 'Started',
    'abort'   => 'Aborted',
    'finish'  => 'Finished',
  );
  
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
  
  protected function out($message)
  {
    return $this->getOut()->writeln($message);
  }

  protected function setOut($out)
  {
    return $this->output = $out;
  } 
  protected function getOut()
  {
    return $this->output;
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->setOut($output);
    $this->out('Start.');
    
    $tasks = $this->getTasks();
    
    foreach ($tasks as $task)
    {
      if (self::$statuses['finish'] == $task->getStatus()) continue;
      
      $this->out('Start with ' . $task->getCategory() . ' category.');
      $configuration  = $this->getConfiguration($task);
      $type           = $this->getParserType($configuration);
      $worker         = $this->runTask($configuration, $type);
      
      $this->saveTask($task, $worker);
      $this->saveProducts($this->getProducts());
    }
    
    $this->out('Finished.');
  }
  
  protected function saveProducts($products)
  {
    $this->out('Try save ' . sizeof($products) . ' products.');
    $products = $this->filterFromExist($products);
    $em       = $this->getDoctrine()->getManager();
    
    foreach ($products as $one)
    {
      $product = new Product();
      $product = $this->setObjectWithData($product, $one);
      $em->persist($product);
    }
    
    $em->flush();
    
    return true;
  }

  protected function saveTask($task, $workerData)
  {
    $this->out('Save task data.');
    $em   = $this->getDoctrine()->getManager();
    $task = $this->setObjectWithData($task, $workerData);
    $em->flush();
    
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
      
      $this->out('Run ' . $className);
      
      $parser->setOut($this->getOut());
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
    return $this->getDoctrine()->getRepository('AliasTestBundle:Task')
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
  protected function getDoctrine()
  {
    return $this->getContainer()->get('Doctrine');
  }

  protected function setObjectWithData($object, $data)
  {
    if (!$data) return $object;
    
    foreach ($data as $fieldName => $fieldValue)
    {
      $method = sprintf('set%s', ucfirst($fieldName));
      if (!method_exists($object, $method)) continue;

      $object->$method($fieldValue);
    }
    
    return $object;
  }

  protected function filterFromExist($products)
  {
    $whereBy  = self::CONFIG_FILTER_PRODUCT_BY;
    $filterBy = $this->extractFromArrayByName($products, $whereBy);
    
    $existsProducts = $this->getDoctrine()
      ->getRepository('AliasTestBundle:Product')
      ->findAllByMany($whereBy, $filterBy);
    $cleared = $this->extractFromArrayByName($existsProducts, $whereBy);
    
    $this->out('Finded old products ' . sizeof($cleared));
    
    return $this->filterArrayFromValues($products, $cleared, $whereBy);
  }
  
  protected function filterArrayFromValues($array, $values, $byWhat)
  {
    if (!$array || !$values) return $array;
    
    foreach ($array as &$one)
    {
      if (!isset($array[$byWhat]))          $one = false;
      if (in_array($one[$byWhat], $values)) $one = false;
    }
    
    return array_filter($array, function($one) { return false !== $one; });
  }

  protected function extractFromArrayByName($array, $name)
  {
    if (!$array) return array();
    
    foreach ($array as &$one)
    {
      $temp   = false;
      $method = sprintf('get%s', ucfirst($name));
      
      if (!is_array($one) && method_exists($one, $method))
      {
        $temp = $one->$method();
      }
      if (is_array($one) && isset($one[$name]))
      {
        $temp = $one[$name];
      }
      
      $one = $temp;
    }
    
    return $array;
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
