<?php

namespace Alias\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class MainPageController extends Controller
{
  public function indexAction()
  {
    return new Response('<html><body>Hello 123!</body></html>');
    return $this->render('TestBundle:Default:index.html.twig', array('name' => $name));
  }
}
