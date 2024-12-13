<?php

use Contao\System;

System::loadLanguageFile('responsive');

$GLOBALS['TL_DCA']['containerSize']['fields']['responsiveContainerSize'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveContainerSize'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContainer'],
    'inputType' => 'iconedSelect',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getContainerSizes'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

//CONTAINER
$GLOBALS['TL_DCA']['container']['fields']['responsiveFlexDirection'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveFlexDirection'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'eval' => ['tl_class' => "clr w50"],

    'options_callback' => [$GLOBALS['responsive']['config'], 'getFlexDirections'],
    'icon_callback' => function () {
        return (new $GLOBALS['responsive']['config']())->getIcons('flexDirection');
    },
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexDirection'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveJustifyContent'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveJustifyContent'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getJustifyContent'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveAlignItems'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveAlignItems'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'eval' => ['tl_class' => "w50 clr"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getAlignItems'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexItems'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveAlignContent'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveAlignContent'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getAlignContent'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveFlexWrap'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveFlexWrap'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'eval' => ['tl_class' => "clr w50"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getFlexWrap'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexWrap'],
    'sql' => "blob NULL"
];


$GLOBALS['TL_DCA']['columnActivate']['fields']['addResponsive'] = array(
    'label'                   => &$GLOBALS['TL_LANG']['responsive']['addResponsive'],
    'default'                 => '1',
    'inputType'               => 'checkbox',
    'eval'                    => array('tl_class'=>'m12 w50 clr', 'submitOnChange'=>true),
    'sql'                     => "char(1) NOT NULL default '1'"
);

//COLUMN
$GLOBALS['TL_DCA']['column']['fields']['responsiveCols'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveCols'],
    'inputType' => 'responsive',
    'responsiveInputType' => 'iconedSelect',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getCols'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['responsiveCols']['options'],
    'eval' => ['tl_class' => 'clr'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveOffsets'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveOffsets'],
    'inputType' => 'responsive',
    'responsiveInputType' => 'iconedSelect',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getOffsets'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['responsiveOffsets']['options'],
    'eval' => ['tl_class' => 'clr'],
    'sql' => "varchar(255) COLLATE ascii_bin NOT NULL default ''"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveOrder'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveOrder'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'text',
    'eval' => ['tl_class' => "clr w50", 'rgxp' => 'digit'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['column']['fields']['responsiveAlignSelf'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveAlignSelf'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'options_callback' => [$GLOBALS['responsive']['config'], 'getAlignSelf'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexItems'],
    'eval' => ['tl_class' => "w50"],
    'sql' => "blob NULL"
];

//SPACE
$GLOBALS['TL_DCA']['space']['fields']['responsiveSpacingTop'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveSpacingTop'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'eval' => ['tl_class' => "w50 clr"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['spacings'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['space']['fields']['responsiveSpacingBottom'] = [
    'label' => &$GLOBALS['TL_LANG']['responsive']['responsiveSpacingBottom'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'iconedSelect',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive']['config'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['responsive']['spacings'],
    'sql' => "blob NULL"
];