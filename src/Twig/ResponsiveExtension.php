<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Twig;

use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveModuleClassResolver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ResponsiveExtension extends AbstractExtension
{
    /**
     * @param object|null $designerFrontendService kiwi/contao-designer's frontend service when that bundle is installed, otherwise null.
     */
    public function __construct(
        protected ResponsiveFrontendService $responsiveFrontendService,
        protected ResponsiveModuleClassResolver $moduleClassResolver,
        protected object|null $designerFrontendService = null,
    ){}

    public function getFunctions(): array
    {
        $arrFunctions = [
            new TwigFunction('getModuleResponsiveClasses', [$this->moduleClassResolver, 'resolveColumnClasses']),
            new TwigFunction('getModuleChildrenClasses', [$this->moduleClassResolver, 'resolveChildrenClasses']),
            new TwigFunction('getAllResponsiveClasses', [$this->responsiveFrontendService, 'getAllResponsiveClasses']),
            new TwigFunction('getColClasses', [$this->responsiveFrontendService, 'getColClasses']),
            new TwigFunction('getOrderClasses', [$this->responsiveFrontendService, 'getOrderClasses']),
            new TwigFunction('getAlignSelfClasses', [$this->responsiveFrontendService, 'getAlignSelfClasses']),
            new TwigFunction('getRowClass', [$this->responsiveFrontendService, 'getRowClass']),
            new TwigFunction('getOffsetClasses', [$this->responsiveFrontendService, 'getOffsetClasses']),
            new TwigFunction('getResponsiveClasses', [$this->responsiveFrontendService, 'getResponsiveClasses']),
            new TwigFunction('getContainerClasses', [$this->responsiveFrontendService, 'getContainerClasses']),
            new TwigFunction('getAllInnerContainerClasses', [$this->responsiveFrontendService, 'getAllInnerContainerClasses']),
            new TwigFunction('getSpacingClasses', [$this->responsiveFrontendService, 'getSpacingClasses']),
            new TwigFunction('getGroupSpacingClasses', [$this->responsiveFrontendService, 'getGroupSpacingClasses']),
            new TwigFunction('getAllContainerClasses', [$this->responsiveFrontendService, 'getAllContainerClasses']),
        ];

        // Only without kiwi/contao-designer, so content_element/_base.html.twig still compiles; with it,
        // the designer registers hasBackground itself and must not be shadowed by this one
        if (!$this->designerFrontendService) {
            $arrFunctions[] = new TwigFunction('hasBackground', static fn (mixed $varBackground = null): bool => false);
        }

        return $arrFunctions;
    }
}
