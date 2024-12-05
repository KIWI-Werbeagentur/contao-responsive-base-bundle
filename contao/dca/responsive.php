<?php

use Contao\System;

System::loadLanguageFile('responsive');

//CONTAINER
$GLOBALS['TL_DCA']['container']['fields']['responsiveFlexDirection'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveFlexDirection'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "clr w50"],
    'options' => ['row', 'column', 'row-reverse', 'column-reverse'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexDirection'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveJustifyContent'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveJustifyContent'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options' => ['normal', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveAlignItems'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveAlignItems'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50 clr"],
    'options' => ['normal', 'stretch', 'baseline', 'flex-start', 'center', 'flex-end'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexItems'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveAlignContent'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveAlignContent'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options' => ['normal', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveFlexWrap'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveFlexWrap'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "clr w50"],
    'options' => ['wrap', 'nowrap', 'wrap-reverse'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexWrap'],
    'sql' => "blob NULL"
];


//COLUMN
$GLOBALS['TL_DCA']['column']['fields']['responsiveCols'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveCols'],
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getCols'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['responsiveCols']['options'],
    'eval' => ['tl_class' => 'clr'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveOffsets'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveOffsets'],
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getOffsets'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['responsiveOffsets']['options'],
    'eval' => ['tl_class' => 'clr'],
    'sql' => "varchar(255) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveOrder'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveOrder'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'text',
    'default' => ['xs' => 0],
    'eval' => ['tl_class' => "clr w50", 'rgxp' => 'digit'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveAlignSelf'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveAlignSelf'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'options' => ['auto', 'stretch', 'baseline', 'flex-start', 'center', 'flex-end'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexItems'],
    'default' => ['xs' => 'auto'],
    'eval' => ['tl_class' => "w50"],
    'sql' => "blob NULL"
];

//SPACE
$GLOBALS['TL_DCA']['space']['fields']['responsiveSpacingTop'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveSpacingTop'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50 clr"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['spacings'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['space']['fields']['responsiveSpacingBottom'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveSpacingBottom'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['spacings'],
    'sql' => "blob NULL"
];