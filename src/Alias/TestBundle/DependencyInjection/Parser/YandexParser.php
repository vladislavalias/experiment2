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
  
  static $productDataSelectors = array(
    'description' => '.b-offers__spec',
    'image'       => '.b-offers__img',
    'name'        => '.b-offers__name',
    'price'       => '.b-prices__num',
    'currency'    => '.b-prices__currency',
    'link'        => '.b-offers__name',
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

  protected function increaseCategoryCount()
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
    
    foreach ($selectors as $selector)
    {
      $temp = $page->find($selector);
      
      if ($onlyFirst && sizeof($temp)) return $temp;
      
      if ($temp)
      {
        $finded[] = $temp;
      }
    }
    
    return $finded;
  }
  
  protected function getProductPageUrl($page, $currentPage)
  {
    $pagesUrls  = $this->urlsFrom($page, self::$pageSelector);
    $result     = false;
    $pageIndex  = $currentPage - 1;
    
    if ($pagesUrls)
    {
      $firstPage  = preg_replace('/\-BPOS=[0-9]*\-/u', '-BPOS=0-', $pagesUrls[0]);
      $pages      = array_merge(array($firstPage), $pagesUrls);
      $result     = $pages[$pageIndex];
    }
    
    return $result;
  }
  
  protected function extractProductDatas($url)
  {
    $page           = $this->getHtml($url);
    $productTables  = $this->findFrom($page, self::$productTablesSelector, true);
    $products       = array();
    
    if ($productTables)
    {
      foreach ($productTables as $table)
      {
        $product = array();
        foreach (self::$productDataSelectors as $name => $selector)
        {
          $product[$name] = $this->getProductDataFromFinded($table->find($selector, 0), $name);
        }
        $products[] = $product;
      }
    }
    
    return $products;
  }

  protected function extractProductsFromUrl($url)
  {
    $products = array();
    
    for ($i = 1; $i <= self::CONFIG_MAX_PAGES; $i++)
    {
      $page     = $this->getProductPageUrl($this->getHtml($url), $i);
      $products = $products + $this->extractProductDatas($page);
    }
    
    if ($products) $this->increaseCategoryCount();
    
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
  
  /****************************************************************************/
  /*                        PRODUCT DATA EXTRACTORS                           */
  /****************************************************************************/
  
  protected function getProductDataFromFinded($finded, $type)
  {
    if (!$finded) return '';
    
    $methodName = sprintf('extract%sField', ucfirst($type));
    $result     = '';
    
    if (method_exists($this, $methodName))
    {
      $result = $this->$methodName($finded);
    }
    else
    {
      $result = $finded->innertext;
    }
    
    return mb_trim($result);
  }
  
  protected function extractDescriptionField($finded)
  {
    return $finded->innertext;
  }
  protected function extractImageField($finded)
  {
    return $finded->src;
  }
  protected function extractNameField($finded)
  {
    return $finded->innertext;
  }
  protected function extractPriceField($finded)
  {
    return $finded->innertext;
  }
  protected function extractCurrencyField($finded)
  {
    return $finded->innertext;
  }
  protected function extractLinkField($finded)
  {
    return sprintf('%s%s', self::CONFIG_YANDEX_URL, $finded->href);
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

if (!function_exists('mb_trim'))
{
  function mb_trim($string, $trim_chars = '\s')
  {
    return preg_replace('/^['.$trim_chars.']*(?U)(.*)['.$trim_chars.']*$/u', '\\1',$string);
  }
}