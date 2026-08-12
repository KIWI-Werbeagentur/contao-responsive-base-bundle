<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\Configuration\ResponsiveConfiguration;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    protected ResponsiveConfiguration $responsiveConfiguration;

    public function __construct()
    {
        $this->responsiveConfiguration = new $GLOBALS['responsive']['config']();
    }

    public function __invoke(string $strTable): void
    {
        //Copy DCA-entry for responsive widgets to avoid exception in Ajax requests (e.g. fileTree)
        if (!($GLOBALS['TL_DCA'][$strTable]['fields'] ?? false)) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
            if (in_array(($arrField['inputType'] ?? false), ["responsive", "optionalResponsive", 'responsiveSubpalette', 'optionalResponsiveSubpalette'])) {
                $GLOBALS['TL_DCA'][$strTable]['fields'][$strField]['eval']['alwaysSave'] = true;
                foreach ($this->responsiveConfiguration->arrBreakpoints as $arrBreakpoint) {
                    if ($arrBreakpoint['modifier']) {
                        $GLOBALS['TL_DCA'][$strTable]['fields'][$strField . $arrBreakpoint['modifier']] = $GLOBALS['TL_DCA'][$strTable]['fields'][$strField];
                        $GLOBALS['TL_DCA'][$strTable]['fields'][$strField . $arrBreakpoint['modifier']]['exclude'] = false;
                        $GLOBALS['TL_DCA'][$strTable]['fields'][$strField . $arrBreakpoint['modifier']]['eval']['doNotShow'] = true;
                        unset($GLOBALS['TL_DCA'][$strTable]['fields'][$strField . $arrBreakpoint['modifier']]['sql']);
                    }
                }
            }
            if (in_array(($arrField['inputType'] ?? false), ['responsiveSubpalette', 'optionalResponsiveSubpalette'])) {
                foreach ($arrField['subpalettes'] as $fields) {
                    foreach ($fields as $baseFieldName => $value) {
                        foreach ($this->responsiveConfiguration->arrBreakpoints as $arrBreakpoint) {
                            $fieldName = $strField . '-' . $baseFieldName . $arrBreakpoint['modifier'];
                            if (is_string($value)) {
                                $GLOBALS['TL_DCA'][$strTable]['fields'][$fieldName] = $GLOBALS['TL_DCA'][$strTable]['fields'][$value];
                                if($arrBreakpoint['modifier']) {
                                    $GLOBALS['TL_DCA'][$strTable]['fields'][$fieldName]['exclude'] = false;
                                    $GLOBALS['TL_DCA'][$strTable]['fields'][$fieldName]['eval']['doNotShow'] = true;
                                }
                                unset($GLOBALS['TL_DCA'][$strTable]['fields'][$fieldName]['sql']);
                            } elseif (is_array($value)) {
                                $GLOBALS['TL_DCA'][$strTable]['fields'][$fieldName] = $value;
                                if($arrBreakpoint['modifier']) {
                                    $GLOBALS['TL_DCA'][$strTable]['fields'][$fieldName]['exclude'] = false;
                                    $GLOBALS['TL_DCA'][$strTable]['fields'][$fieldName]['eval']['doNotShow'] = true;
                                }
                                unset($arrField['subpalettes'][$fieldName]['sql']);
                            } else {
                                throw new \Exception('DCA error: subpalette entries of "' . $arrField['inputType'] . '" need to contain a string (referencing an existing dca field) or an array (containing a dca field configuration).');
                            }
                        }
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
                ->addLegend('layout_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(['responsiveContainer', 'responsiveOrder', 'responsiveAlignSelf'], 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->addLegend('items_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(array_merge(['responsiveColsItems'], array_keys($GLOBALS['TL_DCA']['container']['fields'] ?? [])), 'items_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalettes($GLOBALS['responsive']['tl_content']['includePalettes']['container'] ?? [], 'tl_content');

            $this->addChildrenSettings('tl_content');

            PaletteManipulatorExtended::create()
                ->addField('responsiveGroupSpacingTop,responsiveGroupSpacingBottom', 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalettes(['element_group'], 'tl_content');
        }
        if ($strTable == 'tl_form_field') {
            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField('responsiveCols,responsiveOffsets,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToAllPalettes('tl_form_field', $GLOBALS['responsive']['tl_form_field']['excludePalettes']['column'] ?? []);

            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(['responsiveContainer', 'responsiveOrder', 'responsiveAlignSelf'], 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->addLegend('items_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(array_keys($GLOBALS['TL_DCA']['container']['fields'] ?? []), 'items_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette('fieldsetStart', 'tl_form_field');
        }
        if ($strTable == 'tl_module') {
            PaletteManipulatorExtended::create()
                ->addLegend('layout_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(['addResponsive'], 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToAllPalettes('tl_module', $GLOBALS['responsive']['tl_module']['excludePalettes']['column'] ?? []);

            PaletteManipulatorExtended::create()
                ->addLegend('items_legend', ['protected_legend', 'expert_legend'], PaletteManipulator::POSITION_BEFORE)
                ->addField(['addResponsiveChildren'], 'items_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalettes(array_keys($GLOBALS['responsive']['tl_module']['includePalettes']['container'] ?? []), 'tl_module');

            $this->addChildrenSettings('tl_module');
        }
    }

    /**
     * Offers the children settings - the addResponsiveChildren selector, whose subpalette holds
     * the settings that lay out a record's own child items - on the palettes registered in
     * $GLOBALS['responsive'][$table]['includePalettes']['children'].
     *
     * This is the counterpart of the "container" list for records that render their own child
     * items without being a container: a list of persons, of teasers, of anything a bundle
     * renders itself instead of nesting fragments. Such a record stays an ordinary column, so -
     * unlike the container list - this adds nothing to the layout_legend and in particular no
     * choice between being a column and being a container.
     */
    protected function addChildrenSettings(string $strTable): void
    {
        $arrChildrenPalettes = (array) ($GLOBALS['responsive'][$strTable]['includePalettes']['children'] ?? []);

        if (!$arrChildrenPalettes) {
            return;
        }

        PaletteManipulatorExtended::create()
            ->addLegend('items_legend', ['layout_legend', 'template_legend'], PaletteManipulator::POSITION_AFTER)
            ->addField('addResponsiveChildren', 'items_legend', PaletteManipulator::POSITION_APPEND)
            ->applyToPalettes($arrChildrenPalettes, $strTable);
    }
}
