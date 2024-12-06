<?php

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Wrappers;

//Set default values dynamically
$GLOBALS['TL_DCA']['tl_form_field']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

Controller::loadDataContainer('responsive');
$GLOBALS['TL_DCA']['tl_form_field']['fields'] += $GLOBALS['TL_DCA']['column']['fields'];

PaletteManipulatorExtended::create()
    ->addLegend('layout_legend', ['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
    ->addField('responsiveCols,responsiveOffsets,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToAllPalettes('tl_form_field', $GLOBALS['responsive']['tl_form_field']['excludePalettes']['column']);



// Apply Container-Option to Elementgroup
$GLOBALS['TL_DCA']['tl_form_field']['config']['onload_callback'][] = [Wrappers::class, 'addContainerSubpalette'];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['responsiveContainer'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr",'submitOnChange' => true, 'chosen'=>true],
    'options_callback' => function () {
        return ['default'] + (new $GLOBALS['responsive']['config'])->getContainerSizes();
    },
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContainer'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'responsiveContainer';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['responsiveContainer_0'] = 'responsiveCols,responsiveOffsets';

// Used for all container sizes (Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Content->addContainerSubpalette())
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['responsiveContainer_responsiveContainerSizes'] = implode(',',array_keys($GLOBALS['TL_DCA']['container']['fields']));

PaletteManipulator::create()
    ->addLegend('layout_legend',['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
    ->addField('responsiveContainer', 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('fieldsetStart', 'tl_form_field');