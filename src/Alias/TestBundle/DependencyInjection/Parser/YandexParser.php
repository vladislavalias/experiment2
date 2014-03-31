<?php

namespace Alias\TestBundle\DependencyInjection\Parser;

use Alias\TestBundle\DependencyInjection\Parser\BaseParser;
use Alias\TestBundle\DependencyInjection\SimpleHtmlDom;

class YandexParser extends BaseParser
{
  const CONFIG_YANDEX_URL = 'http://market.yandex.ua';
  const CONFIG_CATEGORY_URL_TEMPLATE = '/catalog.xml?hid=';
  const CONFIG_MAX_ERRORS = 3;
  
  static $productSelector = 'a[href^=/model.xml]';
  static $categorySelector = array(
    'a[href^=/catalog.xml]',
    'a[href^=/guru.xml]',
  );
  
  static $statuses = array(
    'start'   => 'Started',
    'abort'   => 'Aborted',
    'finish'  => 'Finished',
  );
  
  public $products;
  protected $htmlDom;
  protected $last = array(
    'link'    => false,
    'page'    => false,
    'product' => false,
  );
  
  protected function formatStartLink($category)
  {
    return sprintf(
      '%s%s%s',
      self::CONFIG_YANDEX_URL,
      self::CONFIG_CATEGORY_URL_TEMPLATE,
      $category);
  }

  protected function getStartUrl()
  {
    return $this->last['link'] ? : $this->formatStartLink($this->getParam('category'));
  }
  
  protected function findFrom($page, $selectors)
  {
    if (!$selectors) return $page;
    
    foreach ($selectors as $selector)
    {
      $page->find($selector);
    }
  }

  public function run()
  {
    $page   = $this->getHtml($this->getStartUrl());
    
    dump($page->find(self::$productSelector));
    
    while (!$page->find(self::$productSelector))
    {
      
      $url = $this->findFrom($page, self::$categorySelector);
      
      if ($url == $this->getLastUrl())
      {
        $this->setStatus(self::$statuses['abort']);
        break;
      }
    }
    
  }

  public function getStatus()
  {
    return $this->status;
  }          
  public function setStatus($status)
  {
    return $this->status = $status;
  }          
  
  public function serialize()
  {
    return serialize(array(
      'params'  => $this->params,
      'last'    => $this->getLastParams(),
    ));
  }
  
  public function unserialize($data)
  {
    $data = unserialize($data);
    $this->params = $data['params'];
    $this->last   = $data['last'];
  }
          
  function getHtml($url)
  {
    return $this->file_get_html($this->setLastUrl($url));
  }
  
  protected function getDom()
  {
    return $this->htmlDom;
  }
  
  protected function getLastParams()
  {
    return array(
      'page'    => $this->last['page'],
      'product' => $this->last['product'],
      'link'    => $this->last['link'],
    );
  }
  
  protected function setLastUrl($url)
  {
    return $this->last['link'] = $url;
  }
  protected function getLastUrl()
  {
    return $this->last['link'];
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