<?php

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Wrappers;

/*
    * COLUMNS
*/
//Set default values dynamically
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

//Add multiple reusable fields
Controller::loadDataContainer('responsive');
$GLOBALS['TL_DCA']['tl_content']['fields'] += $GLOBALS['TL_DCA']['column']['fields'];

// Apply Container-Option to Elementgroup
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [Wrappers::class, 'addContainerSubpalette'];

PaletteManipulatorExtended::create()
    ->addLegend('layout_legend', ['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
    ->addField('responsiveCols,responsiveOffsets,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToAllPalettes('tl_content', $GLOBALS['responsive']['tl_content']['excludePalettes']['column']);


/*
    * CONTAINER
*/

//Create choice between column and container for element_group
$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveContainer'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr",'submitOnChange' => true, 'chosen'=>true],
    'options_callback' => function () {
        return ['default'] + (new $GLOBALS['responsive']['config'])->getContainerSizes();
    },
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContainer'],
    'sql' => "blob NULL"
];

//Add multiple reusable fields
$GLOBALS['TL_DCA']['tl_content']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];

//Set palettes
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'responsiveContainer';
// BUG: only working with both 'values':
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_'] = 'responsiveCols,responsiveOffsets';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_0'] = 'responsiveCols,responsiveOffsets';

// Used for all container sizes (Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Content->addContainerSubpalette())
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_responsiveContainerSizes'] = implode(',',array_keys($GLOBALS['TL_DCA']['container']['fields']));

PaletteManipulator::create()
    ->addLegend('layout_legend',['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
    ->addField('responsiveContainer,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('element_group', 'tl_content');