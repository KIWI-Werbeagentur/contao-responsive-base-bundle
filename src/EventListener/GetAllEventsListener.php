<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;


#[AsHook('getAllEvents')]
class GetAllEventsListener
{
    public function __invoke($arrEvents, $arrCalendars, $intStart, $intEnd, $objModule) {
        if(!$objModule->addResponsiveChildren || !$objModule->responsiveColsItems) return $arrEvents;

        //Checks if DCA-Palette has Resposnive-Settings for children activated (usually used for lists)
        if(!in_array($objModule->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']))) return $arrEvents;

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