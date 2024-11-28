<?php

use \Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'][] = [$GLOBALS['responsive'], 'getDefaults'];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveContainerSize'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr"],
    'options_callback' => [$GLOBALS['responsive'], 'getContainerSizes'],
    'value' => 'container',
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexContainer'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveFlexDirection'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "clr w50"],
    'options' => ['row', 'column', 'row-reverse', 'column-reverse'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexDirection'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveAlignItems'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50 clr"],
    'options' => ['normal', 'stretch', 'baseline', 'flex-start', 'center', 'flex-end'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexItems'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveJustifyContent'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options' => ['normal', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveAlignContent'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options' => ['normal', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['flexContent'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveSpacingTop'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['spacings'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveSpacingBottom'] = [
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['spacings'],
    'sql' => "blob NULL"
];

PaletteManipulator::create()
    ->addField('responsiveContainerSize,responsiveFlexDirection,responsiveJustifyContent,responsiveAlignItems,responsiveAlignContent,responsiveSpacingTop,responsiveSpacingBottom', 'template_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_article');
