<?php

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Wrappers;

//Set default values dynamically
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

//Add settings to multiple palette
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'addToPalettes'];

Controller::loadDataContainer('responsive');
$GLOBALS['TL_DCA']['tl_content']['fields'] += $GLOBALS['TL_DCA']['column']['fields'];

// Apply Container-Option to Elementgroup
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [Wrappers::class, 'addContainerSubpalette'];

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveContainer'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr",'submitOnChange' => true, 'chosen'=>true],
    'options_callback' => function () {
        return ['default'] + (new $GLOBALS['responsive']['config'])->getContainerSizes();
    },
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContainer'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];

$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'responsiveContainer';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_0'] = 'responsiveCols,responsiveOffsets';

// Used for all container sizes (Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Content->addContainerSubpalette())
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_responsiveContainerSizes'] = implode(',',array_keys($GLOBALS['TL_DCA']['container']['fields']));

PaletteManipulator::create()
    ->addField('responsiveContainer', 'customTpl', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('element_group', 'tl_content');