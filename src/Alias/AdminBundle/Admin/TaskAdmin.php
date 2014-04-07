<?php

namespace Alias\AdminBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Show\ShowMapper;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;

use Alias\TestBundle\Entity\Task;

class TaskAdmin extends Admin
{
  
  static $categories = array(
    '198119' => 'Электроника',
    '198118' => 'Бытовая техника',
  );

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
      ->add('category', null, array('label' => 'Категория'))
      ->add('status', null, array('label' => 'Статус'));
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
      ->add('category', 'choice', array(
        'choices' => array(
            self::$categories
        ),
        'label' => 'Категория'))
      ->add('status', 'choice', array(
        'choices' => array(
            array_flip(Task::$statusText)
        ),
        'label' => 'Категория'));
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
      ->add('category', null, array('label' => 'Категория'))
      ->add('status', null, array('label' => 'Статус'));
  }
}
