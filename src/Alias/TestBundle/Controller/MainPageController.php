<?php

namespace Alias\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class MainPageController extends Controller
{
  /**
   * @Route("/", name="_main_page")
   * @Template(vars={"test"})
   */
  public function indexAction()
  {
    $products = $this->getDoctrine()
      ->getRepository('AliasTestBundle:Product')
      ->findAll();
    
    return $this->render(
        'AliasTestBundle:MainPage:index.html.twig',
        array('products' => $products)
    );
  }
}
