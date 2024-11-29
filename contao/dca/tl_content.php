<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [$GLOBALS['responsive'], 'getDefaults'];

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveCols'] = [
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive'], 'getCols'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['responsiveCols']['options'],
    'eval' => array('tl_class' => 'clr'),
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveOffsets'] = [
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive'], 'getOffsets'],
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

$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveAlignSelf'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'options' => ['auto', 'stretch', 'baseline', 'flex-start', 'center', 'flex-end'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexItems'],
    'default' => ['xs'=>'auto'],
    'eval' => ['tl_class'=>"w50"],
    'sql' => "blob NULL"
];

PaletteManipulator::create()
    ->addField('responsiveCols,responsiveOffsets,responsiveOrder,responsiveAlignSelf', 'template_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('text', 'tl_content');