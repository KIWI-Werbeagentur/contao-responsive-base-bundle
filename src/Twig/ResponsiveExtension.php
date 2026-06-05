<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Twig;

use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveModuleClassResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ResponsiveExtension extends AbstractExtension
{
    public function __construct(
        protected ResponsiveFrontendService $responsiveFrontendService,
        protected ResponsiveModuleClassResolver $moduleClassResolver,
    ){}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getModuleResponsiveClasses', [$this->moduleClassResolver, 'resolveColumnClasses']),
            new TwigFunction('getAllResponsiveClasses', [$this->responsiveFrontendService, 'getAllResponsiveClasses']),
            new TwigFunction('getColClasses', [$this->responsiveFrontendService, 'getColClasses']),
            new TwigFunction('getOrderClasses', [$this->responsiveFrontendService, 'getOrderClasses']),
            new TwigFunction('getAlignSelfClasses', [$this->responsiveFrontendService, 'getAlignSelfClasses']),
            new TwigFunction('getRowClass', [$this->responsiveFrontendService, 'getRowClass']),
            new TwigFunction('getOffsetClasses', fn($strData) => $this->responsiveFrontendService->getResponsiveClasses($strData, 'arrOffsets')),
            new TwigFunction('getResponsiveClasses', [$this->responsiveFrontendService, 'getResponsiveClasses']),
            new TwigFunction('getContainerClasses', [$this->responsiveFrontendService, 'getContainerClasses']),
            new TwigFunction('getAllInnerContainerClasses', [$this->responsiveFrontendService, 'getAllInnerContainerClasses']),
            new TwigFunction('getSpacingClasses', [$this->responsiveFrontendService, 'getSpacingClasses']),
            new TwigFunction('getAllContainerClasses', [$this->responsiveFrontendService, 'getAllContainerClasses']),
        ];
    }
}
