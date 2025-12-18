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
            new TwigFunction('getAllResponsiveClasses', [$this->responsiveFrontendService, 'getAllResponsiveClasses']),
            new TwigFunction('getColClasses', [$this->responsiveFrontendService, 'getColClasses']),
            new TwigFunction('getRowClass', [$this->responsiveFrontendService, 'getRowClass']),
            new TwigFunction('getOffsetClasses', fn($strData) => $this->responsiveFrontendService->getResponsiveClasses($strData, 'arrOffsets')),
            new TwigFunction('getResponsiveClasses', [$this->responsiveFrontendService, 'getResponsiveClasses']),
            new TwigFunction('getContainerClasses', [$this->responsiveFrontendService, 'getContainerClasses']),
            new TwigFunction('getAllInnerContainerClasses', [$this->responsiveFrontendService, 'getAllInnerContainerClasses']),
            new TwigFunction('getAllContainerClasses', [$this->responsiveFrontendService, 'getAllContainerClasses']),
        ];
    }
}