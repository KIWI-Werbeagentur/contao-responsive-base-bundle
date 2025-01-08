<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\StringUtil;
use Contao\System;


#[AsHook('parseTemplate')]
class ParseTemplateListener
{
    public function __invoke($objTemplate)
    {
        if ($objTemplate->typePrefix == 'mod_') {
            self::wrapListItems($objTemplate);
        }

        if(str_starts_with($objTemplate->getName(), 'fe_page')){
            self::setLayoutSizes($objTemplate);
        }
    }

    public static function wrapListItems(&$objTemplate):void
    {
        Controller::loadDataContainer('responsive');

        $objTargetWithClasses = ($objTemplate->cte ?? false) && $objTemplate->cte->addResponsiveChildren ? $objTemplate->cte : $objTemplate;
        if (!$objTargetWithClasses->addResponsiveChildren || !$objTargetWithClasses->responsiveColsItems) return;

        //Checks if DCA-Palette has Settings for children (usually used for lists)
        if (!in_array($objTemplate->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']))) return;

        $strColumnClasses = implode(" ", System::getContainer()->get('kiwi.contao.responsive.frontend')->getColClasses($objTargetWithClasses->responsiveColsItems));
        $varChildren = ($objTemplate->{$GLOBALS['responsive']['tl_module']['includePalettes']['container'][$objTemplate->type]});

        if (is_array($varChildren) && $objTemplate->isResponsive) {
            foreach ($varChildren as &$varChild) {
                $varChild = System::getContainer()->get('twig')->render('@KiwiResponsiveBase/list_child.html.twig', [
                    'baseClass' => $objTemplate->baseClass,
                    'class' => $strColumnClasses,
                    'item' => $varChild
                ]);
            }
        }

        $objTemplate->{$GLOBALS['responsive']['tl_module']['includePalettes']['container'][$objTemplate->type]} = $varChildren;
    }

    public static function mapSidebars(&$objTemplate):array
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

    public static function setLayoutSizes(&$objTemplate):void
    {
        $responsiveFrontendService = System::getContainer()->get('kiwi.contao.responsive.frontend');

        // Columns
        $arrBreakpoints = [];
        $objTemplate->responsiveColsLeft = [];

        foreach (self::mapSidebars($objTemplate) as $sidebar) {
            $arrBreakpointSetting = StringUtil::deserialize($objTemplate->layout->{'responsiveCols' . $sidebar});
            $objTemplate->{'responsiveCols' . $sidebar} = $responsiveFrontendService->getColClasses($objTemplate->layout->{'responsiveCols' . $sidebar});

            $prevVal = 0;
            foreach((new $GLOBALS['responsive']['config']())->arrBreakpoints as $strBreakpoint => $arrBreakpoint){
                if($arrBreakpointSetting[$strBreakpoint] ?? false) $prevVal = $arrBreakpointSetting[$strBreakpoint];
                $arrBreakpoints[$strBreakpoint] = ($arrBreakpoints[$strBreakpoint] ?? 0) + ($arrBreakpointSetting[$strBreakpoint] ?? $prevVal);
            }
        }

        $objTemplate->responsiveColsLeft[] = "order-first";


        $arrMain = [];
        foreach((new $GLOBALS['responsive']['config']())->arrBreakpoints as $strBreakpoint => $arrBreakpoint){
            $arrMain[$strBreakpoint] = 12 - ($arrBreakpoints[$strBreakpoint] % 12);
        }

        $objTemplate->responsiveColsMain = $responsiveFrontendService->getColClasses(serialize($arrMain));


        // Rows
        $arrSections = ['Header', '', 'Footer'];
        foreach ($arrSections as $section) {
            $objTemplate->{"responsiveContainerSize$section"} = $objTemplate->layout->{"responsiveContainerSize$section"};
        }
    }
}
