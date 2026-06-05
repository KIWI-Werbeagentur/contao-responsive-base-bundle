<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Module;
use Contao\ModuleProxy;
use Contao\StringUtil;
use Contao\System;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveModuleClassResolver;


#[AsHook('parseTemplate')]
class ParseTemplateListener
{
    public function __construct(protected ResponsiveModuleClassResolver $classResolver)
    {
    }

    public function __invoke($objTemplate)
    {
        if ($objTemplate->typePrefix == 'mod_') {
            self::wrapListItems($objTemplate);
        }

        // Fragment modules render through ModuleProxy, so the getFrontendModule hook cannot reach
        // their template. When such a module uses a legacy template it is rendered here via a real
        // FrontendTemplate - inject the resolved responsive column classes into its class attribute.
        // Modern Twig fragment templates are handled by the frontend_module/_base.html.twig override
        // (they never reach this hook); legacy Modules are handled by the getFrontendModule hook and
        // are excluded by the ModuleProxy check below, so classes are never applied twice.
        if (str_starts_with((string) $objTemplate->getName(), 'mod_')) {
            $this->addFragmentModuleClasses($objTemplate);
        }

        if (str_starts_with($objTemplate->getName(), 'fe_page')) {
            self::setLayoutSizes($objTemplate);
        }
    }

    protected function addFragmentModuleClasses($objTemplate): void
    {
        $arrRow = $objTemplate->getData();
        $strType = $arrRow['type'] ?? null;

        if (!$strType || Module::findClass($strType) !== ModuleProxy::class) {
            return;
        }

        $arrClasses = $this->classResolver->resolveColumnClasses($arrRow);

        if ($arrClasses) {
            $objTemplate->class = trim(($objTemplate->class ?? '') . ' ' . implode(' ', $arrClasses));
        }
    }

    public static function wrapListItems(&$objTemplate, $objModel = null): void
    {
        Controller::loadDataContainer('responsive');

        if(!$objModel){
            $objModel = $objTemplate;
        }

        $objTargetWithClasses = ($objModel->cte ?? false) && $objModel->cte->addResponsiveChildren ? $objModel->cte : $objModel;

        if (!$objTargetWithClasses->addResponsiveChildren || !$objTargetWithClasses->responsiveColsItems) return;

        // Require an entry in the config-level allow-list: its value is the template property
        // name that holds the list items (e.g. 'newslist' => 'articles', 'eventlist' => 'events'),
        // and the code below indexes that map unconditionally - no fallback is possible. A
        // hasField()-only path (as in GetFrontendModuleListener) is not enough here for that
        // reason; a third-party module that declares addResponsiveChildren without registering
        // in includePalettes.container has nothing for this listener to wrap.
        $arrIncludePalettes = $GLOBALS['responsive']['tl_module']['includePalettes']['container'] ?? [];
        if (!array_key_exists($objModel->type, $arrIncludePalettes)) return;

        $strColumnClasses = implode(" ", System::getContainer()->get('kiwi.contao.responsive.frontend')->getColClasses($objTargetWithClasses->responsiveColsItems));
        $varChildren = ($objTemplate->{$arrIncludePalettes[$objModel->type]});

        if (is_array($varChildren) && $objModel->isResponsive) {
            foreach ($varChildren as &$varChild) {
                $varChild = System::getContainer()->get('twig')->render('@KiwiResponsiveBase/list_child.html.twig', [
                    'baseClass' => $objModel->baseClass,
                    'class' => $strColumnClasses,
                    'item' => $varChild
                ]);
            }
        }

        $objTemplate->{$arrIncludePalettes[$objModel->type]} = $varChildren;
    }

    public static function mapSidebars(&$objTemplate): array
    {
        $sidebars = [];
        if ($objTemplate->layout->cols == '3cl') {
            $sidebars[] = "Left";
            $sidebars[] = "Right";
        } elseif ($objTemplate->layout->cols == '2cll') {
            $sidebars[] = "Left";
        } elseif ($objTemplate->layout->cols == '2clr') {
            $sidebars[] = "Right";
        }
        return $sidebars;
    }

    public static function setLayoutSizes(&$objTemplate): void
    {
        $responsiveFrontendService = System::getContainer()->get('kiwi.contao.responsive.frontend');

        // Columns
        $arrBreakpoints = [];
        $objTemplate->responsiveColsLeft = [];

        foreach (self::mapSidebars($objTemplate) as $sidebar) {
            $arrBreakpointSetting = StringUtil::deserialize($objTemplate->layout->{'responsiveCols' . $sidebar}, true);
            $objTemplate->{'responsiveCols' . $sidebar} = $responsiveFrontendService->getColClasses($objTemplate->layout->{'responsiveCols' . $sidebar});

            $prevVal = 0;
            foreach ((new $GLOBALS['responsive']['config']())->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
                if ($arrBreakpointSetting[$strBreakpoint] ?? false) $prevVal = $arrBreakpointSetting[$strBreakpoint];
                if(!is_numeric($arrBreakpointSetting[$strBreakpoint] ?? 0) || !is_numeric($arrBreakpoints[$strBreakpoint] ?? $prevVal)){
                    $arrBreakpoints[$strBreakpoint] = 'auto';
                }
                else{
                    $arrBreakpoints[$strBreakpoint] = ($arrBreakpoints[$strBreakpoint] ?? 0) + ($arrBreakpointSetting[$strBreakpoint] ?? $prevVal);
                }
            }
        }

        $responsiveColsLeft = $objTemplate->responsiveColsLeft;
        $objTemplate->responsiveColsLeft = array_merge($responsiveColsLeft,["order-first"]);


        $arrMain = [];
        foreach ((new $GLOBALS['responsive']['config']())->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            if(!is_numeric($arrBreakpoints[$strBreakpoint] ?? 0)){
                $arrMain[$strBreakpoint] = 'fill';
            }
            else{
                $arrMain[$strBreakpoint] = 12 - (($arrBreakpoints[$strBreakpoint] ?? 12) % 12);
            }
        }

        $objTemplate->responsiveColsMain = $responsiveFrontendService->getColClasses(serialize($arrMain));


        // Rows
        $arrSections = ['Header', '', 'Footer'];
        foreach ($arrSections as $section) {
            $objTemplate->{"responsiveContainerSize$section"} = $objTemplate->layout->{"responsiveContainerSize$section"};
        }
    }
}
