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
  protected $usedPages = array();
  protected $categoryCount;
  protected $categories = array();
  
  static $nest = 0;

  /**
   * Увеличить счетчик максимального числа возможного количества
   * обработанных категорий за раз.
   * 
   * @return integer
   */
  protected function increaseCategoryCount()
  {
    return $this->categoryCount += 1;
  }
  
  /**
   * Превышен ли лимит допустимого количества обработанных категорий.
   * 
   * @return boolean
   */
  protected function isCatagoryMaxCount()
  {
    return $this->categoryCount >= self::CONFIG_MAX_CATEGORIES;
  }

  /**
   * Форматирование старторой страницы.
   * 
   * @param integer $category
   * @return string
   */
  protected function formatStartLink($category)
  {
    return sprintf(
      '%s%s%s',
      self::CONFIG_YANDEX_URL,
      self::CONFIG_CATEGORY_URL_TEMPLATE,
      $category);
  }

  /**
   * Получить страницу с которой надо начинать работу.
   * 
   * @return string
   */
  protected function getStartUrl()
  {
    return $this->formatStartLink($this->getParam('category'));
  }
  
  /**
   * Извлечь урлов из страницы по селекторам.
   * 
   * @param SimpeHtmlDom $page
   * @param array $selectors
   * @return array
   */
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

  /**
   * Поиск из объекта SimpleHtmlDom по множеству селекторов.
   * В соответствии с опцией возврата первого попавшегося.
   * 
   * @param SimpleHtmlDom $page
   * @param array $selectors
   * @param boolean $onlyFirst
   * @return array
   */
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
  
  /**
   * Получить страницу продуктов категории нужной страницы.
   * 
   * @param string $url
   * @param integer $currentPage
   * @return string
   */
  protected function getProductPageUrl($url, $currentPage)
  {
    $page       = $this->getHtml($url);
    $pagesUrls  = $this->urlsFrom($page, self::$pageSelector);
    $result     = false;
    $pageIndex  = $currentPage - 1;
    
    if ($pagesUrls)
    {
      $firstPage  = preg_replace(
        '/\-BPOS=[0-9]*\-/u',
        '-BPOS=0-',
        $pagesUrls[0]
      );
      $pages      = array_merge(array($firstPage), $pagesUrls);
      $result     = $pages[$pageIndex];
    }
    else
    {
      $result = $url;
    }
    
    return $result;
  }
  
  /**
   * Извлечь данные продукта с нужной урлы.
   * 
   * @param string $url
   * @return array
   */
  protected function extractProductDatas($url)
  {
    if ($this->checkUsedPage($url)) return array();
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
    
    $this->addUsedPage($url);
    
    return $this->filterFromEmpty($products);
  }

  /**
   * Извлечь продукты по урле страницы на которой они есть.
   * 
   * @param string $url
   * @return array
   */
  protected function extractProductsFromUrl($url)
  {
    $products = array();
    
    for ($i = 1; $i <= self::CONFIG_MAX_PAGES; $i++)
    {
      $pageUrl  = $this->getProductPageUrl($url, $i);
      $products = $products + $this->extractProductDatas($pageUrl);
    }
    
    if ($products)
    {
      $this->markAsFinishedCat($url);
      $this->increaseCategoryCount();
    }
    
    return $products;
  }
  
  /**
   * Извлечение продуктов по урлу с прохождение от заданной
   * категории до нужной страницы с продуктами.
   * 
   * @param string|array $urls
   * @return array
   */
  protected function extractProducts($urls)
  {
    $urls     = !is_array($urls) ? array($urls) : $urls;
    $products = array();
    
    foreach ($urls as $url)
    {
      $this->out('Work with ' . $url);
      if ($this->isFinishedCat($url) ||
          $this->isCatagoryMaxCount()) break;
      
      if ($this->isNeedToGoDeeper($url))
      {
        $this->out('Going deeper ... |');
        $subCategories  = $this->getSubCategories($url);
        $temp           = $this->extractProducts($subCategories);
      }
      else
      {
        $temp = $this->extractProductsFromUrl($url);
      }
      
      $this->markCatIfFinished($url);
      $products += $temp;
    }

    return $products;
  }

  /**
   * Запуск парсера.
   * 
   * @return array
   */
  public function run()
  {
    $this->setStatus(self::$statuses['start']);
    
    if (!$products = $this->extractProducts($this->getStartUrl()))
    {
      $this->setStatus(self::$statuses['finish']);
    }
    
    return $this->setProducts($products);
  }
  
  /**
   * Получить текущий статус парсинга.
   * 
   * @param string $status
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }
  
  /**
   * Установить текущий статус парсинга.
   * 
   * @param string $status
   * @return string
   */
  public function setStatus($status)
  {
    return $this->status = $status;
  }          
  
  /**
   * Сериализация.
   * 
   * @return string
   */
  public function serialize()
  {
    return serialize(array(
      'usedPages'     => array_unique($this->usedPages),
      'categories'    => $this->categories,
      'params'        => $this->params,
    ));
  }
  
  /**
   * Десиарлизация.
   * 
   * @param string $data
   */
  public function unserialize($data)
  {
    $data = unserialize($data);
    
    $this->usedPages      = $data['usedPages'];
    $this->categories     = $data['categories'];
    $this->params         = $data['params'];
  }
  
  /**
   * Создание объекта дома по урлу.
   * 
   * @param string $url
   * @return \SimpleHtmlDom
   */
  function getHtml($url)
  {
    if (!$url)
    {
      $this->setStatus(self::$statuses['abort']);
      return false;
    }
    
    if ($url == $this->getLastUrl()) return $this->getDom();
    
    return $this->setDom($this->file_get_html($this->setLastUrl($url)));
  }
  
  /****************************************************************************/
  /*                          SECONDARY FUNCTIONS                             */
  /****************************************************************************/
  
  /**
   * Взять объект дома.
   * 
   * @param SimpleHtmlDom $dom
   * @return \SimpleHtmlDom
   */
  protected function getDom()
  {
    return $this->htmlDom;
  }
  
  /**
   * Установить объект дома.
   * 
   * @param SimpleHtmlDom $dom
   * @return \SimpleHtmlDom
   */
  protected function setDom($dom)
  {
    return $this->htmlDom = $dom;
  }
  
  /**
   * Установить последний спрашиваемый урл.
   * 
   * @param string $url
   * @return string
   */
  protected function setLastUrl($url)
  {
    return $this->htmlLastUrl = $url;
  }
  
  /**
   * Получить последний спрашиваемый урл.
   * 
   * @return string
   */
  protected function getLastUrl()
  {
    return $this->htmlLastUrl;
  }
  
  /**
   * Добавить обработанную страницу.
   * 
   * @param string $url
   * @return string
   */
  protected function addUsedPage($url)
  {
    return $this->usedPages[] = $url;
  }
  
  /**
   * Получить обработанные страницы.
   * 
   * @return string
   */
  protected function getUsedPages()
  {
    return $this->usedPages;
  }

  /**
   * Проверить была ли уже обработана страница.
   * 
   * @param string $url
   * @return boolean
   */
  protected function checkUsedPage($url)
  {
    return in_array($url, $this->usedPages);
  }

  /**
   * Фильтровать вухмерный массив о полностью пустых.
   * 
   * @param array $products
   * @return array
   */
  protected function filterFromEmpty($products)
  {
    foreach ($products as $productKey => $product)
    {
      if (!array_diff($product, array('', false, null)))
      {
        unset($products[$productKey]);
      }
    }
    
    return $products;
  }
  
  /**
   * Нужно ли идти дальше в обходе категорий что бы добраться до товаров.
   * 
   * @param string $url
   * @return boolean
   */
  protected function isNeedToGoDeeper($url)
  {
    return !$this->isProductsHere($url);
  }

  /**
   * Проверить есть ли на запрошенном урле товары.
   * 
   * @param string $url
   * @return array
   */
  protected function isProductsHere($url)
  {
    $page = $this->getHtml($url);
    
    return $this->urlsFrom($page, self::$productSelector);
  }
  
  /**
   * Получить подкатегории категории по урлу.
   * 
   * @param string $url
   * @return array
   */
  protected function getSubCategories($url)
  {
    $categoryUrls = $this->searchSubCategoriesFromState($url);
    if (!$categoryUrls)
    {
      $page         = $this->getHtml($url);
      $categoryUrls = $this->urlsFrom($page, self::$categorySelector);
      $this->saveSubCategories($url, $categoryUrls);
    }
    
    return $categoryUrls;
  }
  
  /**
   * Поиск подкатегорий категории в уже сохраненном списке.
   * 
   * @param string $url
   * @return array
   */
  protected function searchSubCategoriesFromState($url)
  {
    $isInState = in_array($url, $this->categories);
    
    return $isInState ? $this->categories[$url] : array();
  }
  
  /**
   * Сохранить подкатегории категории.
   * 
   * @param string $category
   * @param array $sub
   * @return array
   */
  protected function saveSubCategories($category, $sub)
  {
    $this->categories[]           = $category;
    $this->categories[$category]  = $sub;
    
    return $this->categories;
  }
  
  /**
   * Отметить категорию как отработанную если все суб категории
   * тоже уже отработаны.
   * 
   * @param string $url
   * @return boolean
   */
  protected function markCatIfFinished($url)
  {
    if ($subCats = $this->searchSubCategoriesFromState($url))
    {
      $diff = array_diff($subCats, $this->getUsedPages());
      if (!$diff)
      {
        $this->markAsFinishedCat($url);
      }
    }
    
    return true;
  }

  /**
   * Запомнить категорию как обработанную.
   * alias - addUsedPage.
   * 
   * @param string $url
   * @return string
   */
  protected function markAsFinishedCat($url)
  {
    return $this->addUsedPage($url);
  }
  
  /**
   * ЗАвершена ли работа с данной категорией?
   * 
   * @param string $url
   * @return boolean
   */
  protected function isFinishedCat($url)
  {
    return in_array($url, $this->usedPages);
  }

  /****************************************************************************/
  /*                        PRODUCT DATA EXTRACTORS                           */
  /****************************************************************************/
  
  /**
   * Получить данные из найденых таблиц товаров.
   * 
   * @param simple_html_node $finded
   * @param string $type
   * @return string
   */
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