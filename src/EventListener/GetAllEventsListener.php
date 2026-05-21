<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;


#[AsHook('getAllEvents')]
class GetAllEventsListener
{
    public function __invoke($arrEvents, $arrCalendars, $intStart, $intEnd, $objModule) {
        if(!$objModule->addResponsiveChildren || !$objModule->responsiveColsItems) return $arrEvents;

        // Checks the runtime DCA palette (true source of truth, picks up palettes added by
        // third-party bundles) AND the config-level allow-list. Mirrors the gate idiom of
        // GetFrontendModuleListener.
        $isField = PaletteManipulatorExtended::create()->hasField($objModule->type, 'tl_module', 'addResponsiveChildren');
        $hasResponsiveChildren = in_array($objModule->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']));
        if (!$isField && !$hasResponsiveChildren) return $arrEvents;

        $strColumnClasses = implode(" ", System::getContainer()->get('kiwi.contao.responsive.frontend')->getColClasses($objModule->responsiveColsItems));

        foreach($arrEvents as &$events){
            foreach($events as &$event){
                foreach($event as &$detail){
                    $detail['class'] .= " $strColumnClasses";
                }
            }
        }

        return $arrEvents;
    }
}