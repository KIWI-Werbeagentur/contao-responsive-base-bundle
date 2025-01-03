<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    public function __invoke(string $strTable): void
    {
        //Copy DCA-entry for responsive widgets to avoid exception in Ajax requests (e.g. fileTree)
        if (!($GLOBALS['TL_DCA'][$strTable]['fields'] ?? false)) {
            return;
        }
        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
            if (($arrField['inputType'] ?? false) == "responsive") {
                foreach ((new $GLOBALS['responsive']['config'])->arrBreakpoints as $arrBreakpoint) {
                    if($arrBreakpoint['modifier']){
                        $GLOBALS['TL_DCA'][$strTable]['fields'][$strField . $arrBreakpoint['modifier']] = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];
                        unset($GLOBALS['TL_DCA'][$strTable]['fields'][$strField . $arrBreakpoint['modifier']]['sql']);
                    }
                }
            }
        }

        //add Responsiveness to DCA
        if ($strTable == 'tl_content') {
            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField('responsiveCols,responsiveOffsets,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToAllPalettes('tl_content', $GLOBALS['responsive']['tl_content']['excludePalettes']['column'] ?? []);

            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend',['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
                ->addField('responsiveContainer,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette('element_group', 'tl_content');
        }
        if ($strTable == 'tl_form_field') {
            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend', ['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
                ->addField('responsiveCols,responsiveOffsets,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToAllPalettes('tl_form_field', $GLOBALS['responsive']['tl_form_field']['excludePalettes']['column'] ?? []);

            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend',['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
                ->addField('responsiveContainer', 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette('fieldsetStart', 'tl_form_field');
        }
        if($strTable == 'tl_module'){
            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend', ['protected_legend','expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(['addResponsive'], 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToAllPalettes('tl_module', $GLOBALS['responsive']['tl_module']['excludePalettes']['column'] ?? []);

            PaletteManipulatorExtended::create()
                ->addLegend('items_legend', ['protected_legend','expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(['addResponsiveChildren'], 'items_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalettes(array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container'] ?? []), 'tl_module');
        }
    }
}
