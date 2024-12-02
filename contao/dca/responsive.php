<?php

//CONTAINER
$GLOBALS['TL_DCA']['container']['fields']['responsiveFlexDirection'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveFlexDirection'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "clr w50"],
    'options' => ['row', 'column', 'row-reverse', 'column-reverse'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexDirection'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveJustifyContent'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveJustifyContent'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options' => ['normal', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveAlignItems'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveAlignItems'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50 clr"],
    'options' => ['normal', 'stretch', 'baseline', 'flex-start', 'center', 'flex-end'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexItems'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveAlignContent'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveAlignContent'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options' => ['normal', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveFlexWrap'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveFlexWrap'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "clr w50"],
    'options' => ['wrap', 'nowrap', 'wrap-reverse'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexWrap'],
    'sql' => "blob NULL"
];


//COLUMN
$GLOBALS['TL_DCA']['column']['fields']['responsiveCols'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveCols'],
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getCols'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['responsiveCols']['options'],
    'eval' => ['tl_class' => 'clr'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveOffsets'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveOffsets'],
    'inputType' => 'responsive',
    'responsiveInputType' => 'select',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getOffsets'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['responsiveOffsets']['options'],
    'eval' => ['tl_class' => 'clr'],
    'sql' => "varchar(255) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveOrder'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveOrder'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'text',
    'default' => ['xs' => 0],
    'eval' => ['tl_class' => "clr w50", 'rgxp' => 'digit'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveAlignSelf'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveAlignSelf'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'options' => ['auto', 'stretch', 'baseline', 'flex-start', 'center', 'flex-end'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexItems'],
    'default' => ['xs' => 'auto'],
    'eval' => ['tl_class' => "w50"],
    'sql' => "blob NULL"
];

//SPACE
$GLOBALS['TL_DCA']['space']['fields']['responsiveSpacingTop'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveSpacingTop'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50 clr"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['spacings'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['space']['fields']['responsiveSpacingBottom'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveSpacingBottom'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['spacings'],
    'sql' => "blob NULL"
];