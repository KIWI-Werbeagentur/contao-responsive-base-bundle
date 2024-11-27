<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveCols'] = [
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive'], 'getCols'],
    'default' => (new $GLOBALS['responsive'])->arrColsDefaults,
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['responsiveCols']['options'],
    'eval' => array('tl_class' => 'clr'),
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveOffsets'] = [
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive'], 'getOffsets'],
    'default' => (new $GLOBALS['responsive'])->arrOffsetsDefaults,
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['responsiveOffsets']['options'],
    'eval' => array('tl_class' => 'clr'),
    'sql' => "varchar(255) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveOrder'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'text',
    'default' => ['xs'=>0],
    'eval' => ['tl_class'=>"clr w50", 'rgxp' => 'digit'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveAlignment'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'options' => ['auto', 'stretch', 'baseline', 'flex-start', 'center', 'flex-end'],
    'default' => ['xs'=>'auto'],
    'eval' => ['tl_class'=>"w50"],
    'sql' => "blob NULL"
];

PaletteManipulator::create()
    ->addField('responsiveOrder,responsiveAlignment,responsiveCols,responsiveOffsets', 'template_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('text', 'tl_content');