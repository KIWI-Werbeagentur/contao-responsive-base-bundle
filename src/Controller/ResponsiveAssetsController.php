<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ResponsiveAssetsController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Environment $twig
    ) {
        $this->framework->initialize();
    }

    #[Route('/responsive/breakpoints.js', name: ResponsiveAssetsController::class)]
    public function responsiveBreakpoints():Response
    {
        $objResponse = new Response(
            $this->twig->render('@KiwiResponsiveBase/breakpoints.html.twig', [
                'arrBreakpoints' => json_encode((new $GLOBALS['responsive']['config']())->arrBreakpoints)
            ])
        );

        $objResponse->headers->set('Content-Type', 'text/javascript');

        return $objResponse;
    }
}