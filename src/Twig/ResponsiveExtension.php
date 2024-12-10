<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Twig;

use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ResponsiveExtension extends AbstractExtension
{
    public function __construct(protected ResponsiveFrontendService $responsiveFrontendService){}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getColClasses', fn($strData) => $this->responsiveFrontendService->getResponsiveClasses($strData, 'arrCols')),
            new TwigFunction('getOffsetClasses', fn($strData) => $this->responsiveFrontendService->getResponsiveClasses($strData, 'arrOffsets')),
            new TwigFunction('getResponsiveClasses', [$this, 'getResponsiveClasses']),
        ];
    }
}