<?php

namespace Alias\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

class ProductAdmin extends Admin
{

  /**
   * Конфигурация отображения записи
   *
   * @param \Sonata\AdminBundle\Show\ShowMapper $showMapper
   * @return void
   */
  protected  function configureShowField(ShowMapper $showMapper)
  {
    $showMapper
      ->add('id', null, array('label' => 'Идентификатор'))
      ->add('image', null, array('label' => 'Изображение'))
      ->add('name', null, array('label' => 'Название'))
      ->add('price', null, array('label' => 'Цена'))
      ->add('currency', null, array('label' => 'Валюта'))
      ->add('link', null, array('label' => 'Ссылка'))
      ->add('description', null, array('label' => 'Описание'));
  }

  /**
   * Конфигурация формы редактирования записи
   * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
   * @return void
   */
  protected
          function configureFormFields(FormMapper $formMapper)
  {
    $formMapper
      ->add('image', null, array('label' => 'Изображение'))
      ->add('name', null, array('label' => 'Название'))
      ->add('price', null, array('label' => 'Цена'))
      ->add('currency', null, array('label' => 'Валюта'))
      ->add('link', null, array('label' => 'Ссылка'))
      ->add('description', null, array('label' => 'Описание'));
  }

  /**
   * Конфигурация списка записей
   *
   * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
   * @return void
   */
  protected
          function configureListFields(ListMapper $listMapper)
  {
    $listMapper
      ->addIdentifier('id')
      ->addIdentifier('name', null, array('label' => 'Название'))
      ->add('price', null, array('label' => 'Цена'))
      ->add('currency', null, array('label' => 'Валюта'))
      ->add('link', null, array('label' => 'Ссылка'));
  }

  /**
   * Поля, по которым производится поиск в списке записей
   *
   * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
   * @return void
   */
  protected
          function configureDatagridFilters(DatagridMapper $datagridMapper)
  {
    $datagridMapper
            ->add('name', null, array('label' => 'Название'));
  }
}
