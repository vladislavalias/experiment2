<?php

namespace Alias\TestBundle\DependencyInjection\Parser;

require_once realpath(dirname(__FILE__) . '/SimpleHtmlDom.php');

use Alias\TestBundle\DependencyInjection\SimpleHtmlDom;
use Serializable;
use Alias\TestBundle\Entity\Task;

abstract class BaseParser implements Serializable
{
  protected $products, $status, $params, $htmlLastUrl, $output;
  
  static $statuses = array();
  
  /**
   * Конструтор.
   * 
   * @param array $configuration
   */
  public function __construct($configuration)
  { 
    self::$statuses = Task::$statusText;
    
    $this->params = $configuration;
    $this->setStatus(self::$statuses['start']);
  }
  
  abstract function getStatus();
  abstract function run();
  abstract function unserialize($data);
  abstract function serialize();

  /**
   * Установка найденных продуктов переданных от парсера.
   * 
   * @param array $products
   * @return array
   */
  public function setProducts($products)
  {
    return $this->products = $products;
  }
  
  /**
   * Получение найденных продуктов от парсера.
   * 
   * @return array
   */
  public function getProducts()
  {
    return $this->products;
  }
  
  /**
   * Вывод в консоль пользуясь симфониевским выводом.
   * 
   * @param string $message
   * @return null
   */
  public function out($message)
  {
    return $this->getOut()->writeln($message);
  }

  /**
   * Установка экземпляра для симфониевского вывода.
   * 
   * @param OutputInterface $out
   * @return OutputInterface
   */
  public function setOut($out)
  {
    return $this->output = $out;
  } 
  
  /**
   * Выборка экземпляра симфониевского вывода.
   * 
   * @return OutputInterface
   */
  public function getOut()
  {
    return $this->output;
  }
  
  /**
   * Функция финализирования выполнения парсера.
   * В частности - определение статуса выполнения, а так же сериализация
   * объекта.
   * 
   * @return array
   */
  public function finalize()
  {
    return array(
      'status'    => $this->getStatus(),
      'statement' => serialize($this),
    );
  }
  
  /**
   * Вытаскивание данных по имени из конфигурации парсера.
   * 
   * @param string $name
   * @return mixed
   */
  public function getParam($name)
  {
    return isset($this->params[$name]) ? $this->params[$name] : false;
  }


  // helper functions
  // -----------------------------------------------------------------------------
  // get html dom from file
  // $maxlen is defined in the code as PHP_STREAM_COPY_ALL which is defined as -1.
  function file_get_html($url, $use_include_path = false, $context=null, $offset = -1, $maxLen=-1, $lowercase = true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
  {
    // We DO force the tags to be terminated.
    $dom = new SimpleHtmlDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
    // For sourceforge users: uncomment the next line and comment the retreive_url_contents line 2 lines down if it is not already done.
    $contents = file_get_contents($url, $use_include_path, $context, $offset);
    // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
    //$contents = retrieve_url_contents($url);
    if (empty($contents) || strlen($contents) > MAX_FILE_SIZE)
    {
        return false;
    }
    // The second parameter can force the selectors to all be lowercase.
    $dom->load($contents, $lowercase, $stripRN);
    return $dom;
  }

  // get html dom from string
  function str_get_html($str, $lowercase=true, $forceTagsClosed=true, $target_charset = DEFAULT_TARGET_CHARSET, $stripRN=true, $defaultBRText=DEFAULT_BR_TEXT, $defaultSpanText=DEFAULT_SPAN_TEXT)
  {
    $dom = new SimpleHtmlDom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);
    if (empty($str) || strlen($str) > MAX_FILE_SIZE)
    {
        $dom->clear();
        return false;
    }
    $dom->load($str, $lowercase, $stripRN);
    return $dom;
  }

  /**
   * Функция дампа идущая в коплекте с парсером.
   * 
   * @param SimpleHtmlDom $node
   * @param array $show_attr
   * @param integer $deep
   */
  function dump_html_tree($node, $show_attr=true, $deep=0)
  {
    $node->dump($node);
  }
}