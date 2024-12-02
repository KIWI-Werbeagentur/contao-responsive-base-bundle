<?php

use Contao\Controller;
use \Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

$GLOBALS['TL_DCA']['container']['fields']['responsiveContainerSize'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveContainerSize'],
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr", 'chosen'=>true],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getContainerSizes'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexContainer'],
    'sql' => "blob NULL"
];

Controller::loadDataContainer('responsive');
$GLOBALS['TL_DCA']['tl_article']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];
$GLOBALS['TL_DCA']['tl_article']['fields'] += $GLOBALS['TL_DCA']['space']['fields'];

PaletteManipulator::create()
    ->addField(array_merge(['responsiveContainerSize'], array_keys($GLOBALS['TL_DCA']['container']['fields']), array_keys($GLOBALS['TL_DCA']['space']['fields'])), 'template_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_article');
