<?php

namespace Alias\LegacyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('AliasLegacyBundle:Default:index.html.twig', array('name' => $name));
    }
}
