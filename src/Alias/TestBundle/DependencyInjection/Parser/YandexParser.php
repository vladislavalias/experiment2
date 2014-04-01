<?php

namespace Alias\TestBundle\DependencyInjection\Parser;

use Alias\TestBundle\DependencyInjection\Parser\BaseParser;
use Alias\TestBundle\DependencyInjection\SimpleHtmlDom;

class YandexParser extends BaseParser
{
  const CONFIG_YANDEX_URL = 'http://market.yandex.ua';
  const CONFIG_CATEGORY_URL_TEMPLATE = '/catalog.xml?hid=';
  
  const CONFIG_MAX_ERRORS       = 3;
  const CONFIG_MAX_PAGES        = 1;
  const CONFIG_MAX_CATEGORIES   = 1;
  
  static $pageSelector   = array(
    '.b-pager__page'
  );
  static $productSelector   = array(
    '.page__b-offers__guru a[href^=/model.xml]'
  );
  static $productTablesSelector   = array(
    'div.b-offers'
  );
  static $categorySelector  = array(
    'td.categories a[href^=/catalog.xml]',
    'div.b-category-pop-vendors a[href^=/guru.xml]',
  );
  
  static $statuses = array(
    'start'   => 'Started',
    'abort'   => 'Aborted',
    'finish'  => 'Finished',
  );
  
  public $products;
  protected $htmlDom;
  protected $categoryCount;
  protected $last = array(
    'link'    => false,
    'page'    => false,
    'product' => false,
  );
  
  static $nest = 0;

  protected function increaseCatagoryCount()
  {
    return ++$this->categoryCount;
  }
  
  protected function isCatagoryMax()
  {
    return $this->categoryCount >= self::CONFIG_MAX_CATEGORIES;
  }

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
  
  protected function urlsFrom($page, $selectors)
  {
    $finded = $this->findFrom($page, $selectors, true);
    $urls   = array();
    
    foreach ($finded as $find)
    {
      $decoded = html_entity_decode($find->href);
      $urls[] = sprintf('%s%s', self::CONFIG_YANDEX_URL, $decoded);
    }
   
    return $urls;
  }

  protected function findFrom($page, $selectors, $onlyFirst = false)
  {
    if (!$selectors) return $page;
    $finded = array();
    
    
    dump($selectors, false);
    
    foreach ($selectors as $selector)
    {
      $temp = $page->find($selector);
      
      if ($onlyFirst && sizeof($temp)) return $temp;
      
      if ($temp)
      {
        $finded[] = $temp;
      }
    }
    
    echo'finded';
    
    return $finded;
  }
  
  protected function getProductPageUrl($page, $currentPage)
  {
    $pagesUrls    = $this->urlsFrom($page, self::$pageSelector);
    $result       = false;
    $currentIndex = ($currentPage - 1) * 10;
    
    if ($pagesUrls)
    {
      $index  = $currentPage - 1;
      $to     = sprintf('-BPOS=%d-', $currentIndex);
      $result = preg_replace(
        '/\-BPOS=[0-9]*\-/u',
        $to,
        $pagesUrls[$index]
      );
    }
    
    return $result;
  }
  
  protected function extractProductsData($url)
  {
    $page           = $this->getHtml($url);
    $productTables  = $this->findFrom($page, self::$productTablesSelector, true);
    $products       = array();
    
    dump($url, false);
    dump(sizeof($productTables));
    
    if ($productTables)
    {
      foreach ($productTables as $table)
      {
        dump($table->find('.b-offers__img')->src);
        
        $products[] = array(
          'link' => $table->find('.b-offers__img')->src,
        );
      } 
    }
    
    dump('fail');
  }

  protected function extractProductsFromUrl($url)
  {
    $products = array();
    
    for ($i = 1; $i <= self::CONFIG_MAX_PAGES; $i++)
    {
      $page     = $this->getProductPageUrl($this->getHtml($url), $i);
      $products += $this->extractProductsData($page);
    }
    
    if ($products) $this->increaseCatagoryCount();
    
    return $products;
  }
  
  protected function extractProducts($urls)
  {
    if (self::$nest > 4) exit();
    
    self::$nest++;
    $urls     = !is_array($urls) ? array($urls) : $urls;
    $products = array();
    
    foreach ($urls as $url)
    {
      if ($this->isCatagoryMax()) break;
      
      $page           = $this->getHtml($url);
      $isProductsHere = $this->urlsFrom($page, self::$productSelector);
      
      if (!$isProductsHere)
      {
        $categoryUrls = $this->urlsFrom($page, self::$categorySelector);
        $temp         = $this->extractProducts($categoryUrls);
      }
      else
      {
        $temp = $this->extractProductsFromUrl($url);
      }
      
      $products += $temp;
    }
          exit();

    return $products;
  }

  public function run()
  {
    
    $products = $this->extractProducts($this->getStartUrl());
    
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