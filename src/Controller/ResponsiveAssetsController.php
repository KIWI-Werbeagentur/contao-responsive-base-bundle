<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Controller;

use Contao\CoreBundle\Framework\ContaoFramework;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveBreakpoints;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class ResponsiveAssetsController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Environment $twig,
        private readonly ResponsiveBreakpoints $responsiveBreakpoints,
    ) {
        $this->framework->initialize();
    }

    /**
     * @deprecated Deprecated since kiwi/contao-responsive-base 1.1, to be removed in 2.0. The
     *             ResponsiveAssetsListener now sets window.arrBreakpoints inline on every page,
     *             so this script no longer needs to be loaded.
     */
    #[Route('/responsive/breakpoints.js', name: ResponsiveAssetsController::class)]
    public function responsiveBreakpoints():Response
    {
        trigger_deprecation('kiwi/contao-responsive-base', '1.1', 'The "/responsive/breakpoints.js" route is deprecated and will be removed in 2.0. "window.arrBreakpoints" is now set inline on every page, so the script no longer needs to be loaded.');

        $objResponse = new Response(
            $this->twig->render('@KiwiResponsiveBase/breakpoints.html.twig', [
                'arrBreakpoints' => $this->responsiveBreakpoints->getBreakpointsJson(),
            ])
        );

        $objResponse->headers->set('Content-Type', 'text/javascript');

        return $objResponse;
    }
}
