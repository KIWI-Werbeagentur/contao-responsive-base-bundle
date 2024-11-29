<?php

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

$GLOBALS['TL_DCA']['container']['fields']['responsiveSpacingTop'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveSpacingTop'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50 clr"],
    'options_callback' => [$GLOBALS['responsive'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['spacings'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['container']['fields']['responsiveSpacingBottom'] = [
    'label' => &$GLOBALS['TL_LANG']['MSC']['responsiveSpacingBottom'],
    'inputType' => 'optionalResponsive',
    'responsiveInputType' => 'select',
    'eval' => ['tl_class' => "w50"],
    'options_callback' => [$GLOBALS['responsive'], 'getSpacings'],
    'reference' => &$GLOBALS['TL_LANG']['MSC']['spacings'],
    'sql' => "blob NULL"
];