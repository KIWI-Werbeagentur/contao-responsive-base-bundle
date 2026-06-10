<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveModuleClassResolver;


#[AsHook('getAllEvents')]
class GetAllEventsListener
{
    public function __construct(
        protected ResponsiveModuleClassResolver $classResolver
    )
    {
    }

    public function __invoke($arrEvents, $arrCalendars, $intStart, $intEnd, $objModule) {
        // This hook fires during compile(), before the getFrontendModule hook runs (and the
        // reparse there does not recompile) - so the template propagation in
        // GetFrontendModuleListener cannot reach it. Resolve the children source here with the
        // same precedence: outermost wrapper, then CTE, then the module's own settings. Read
        // the backref only - GetFrontendModuleListener consumes it afterwards.
        $arrWrappers = $this->classResolver->collectWrappers($objModule->includedVia ?? null);
        $objSource = $this->classResolver->getWrapperSource($arrWrappers, 'addResponsiveChildren', false);

        if (!$objSource) {
            $objSource = ($objModule->cte ?? null)?->addResponsiveChildren ? $objModule->cte->getModel() : $objModule;
        }

        if(!$objSource->addResponsiveChildren || !$objSource->responsiveColsItems) return $arrEvents;

        // Checks the runtime DCA palette (true source of truth, picks up palettes added by
        // third-party bundles) AND the config-level allow-list. Mirrors the gate idiom of
        // GetFrontendModuleListener.
        $isField = PaletteManipulatorExtended::create()->hasField($objModule->type, 'tl_module', 'addResponsiveChildren');
        $hasResponsiveChildren = in_array($objModule->type, array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container']));
        if (!$isField && !$hasResponsiveChildren) return $arrEvents;

        $strColumnClasses = implode(" ", System::getContainer()->get('kiwi.contao.responsive.frontend')->getColClasses($objSource->responsiveColsItems));

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
