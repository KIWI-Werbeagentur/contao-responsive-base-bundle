<?php

use \Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'][] = [$GLOBALS['responsive'], 'getDefaults'];

$GLOBALS['TL_DCA']['container']['fields']['responsiveContainerSize'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveContainerSize'],
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr", 'chosen'=>true],
    'options_callback' => [$GLOBALS['responsive'], 'getContainerSizes'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexContainer'],
    'sql' => "blob NULL"
];

\Contao\Controller::loadDataContainer('container');
$GLOBALS['TL_DCA']['tl_article']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];

PaletteManipulator::create()
    ->addField(['responsiveContainerSize'] + array_keys($GLOBALS['TL_DCA']['container']['fields']), 'template_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_article');
