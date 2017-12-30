<?php

namespace Itis\DoctrineEntityBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('ItisDoctrineEntityBundle:Default:index.html.twig');
    }
}
