<?php

use Kiwi\Contao\ResponsiveBaseBundle\Configuration\ResponsiveConfiguration;

$GLOBALS['TL_LANG']['responsive']['spacings'][ResponsiveConfiguration::SPACING_NO_OP][0] = "No spacing settings [noop]";

$GLOBALS['TL_LANG']['responsive']['responsive'] = "Responsive Settings";
$GLOBALS['TL_LANG']['responsive']['inherit'] = "- Inherit -";

$GLOBALS['TL_LANG']['responsive']['flexDirection']['default'] = "Default - Horizontal [default]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['row'] = "Horizontal [row]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['column'] = "Vertical [column]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['row-reverse'] = "Horizontal Reverse [row-reverse]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['column-reverse'] = "Vertical Reverse [column-reverse]";

$GLOBALS['TL_LANG']['responsive']['flexWrap']['default'] = "Default - Wrap [default]";
$GLOBALS['TL_LANG']['responsive']['flexWrap']['wrap'] = "Wrap [wrap]";
$GLOBALS['TL_LANG']['responsive']['flexWrap']['nowrap'] = "Don't wrap [nowrap]";
$GLOBALS['TL_LANG']['responsive']['flexWrap']['wrap-reverse'] = "Wrap at beginning [wrap-reverse]";

$GLOBALS['TL_LANG']['responsive']['flexItems']['default'] = "Default - Stretch [default]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['stretch'] = "Stretch [stretch]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['baseline'] = "Baseline [baseline]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['start'] = "Start [start]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['center'] = "Center [center]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['end'] = "End [end]";

$GLOBALS['TL_LANG']['responsive']['flexContent']['default'] = "Default - Start [default]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['start'] = "Start [start]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['center'] = "Center [center]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['end'] = "End [end]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['space-between'] = "Distributed [space-between]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['space-around'] = "Distributed with half space outside [space-around]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['space-evenly'] = "Distributed with space outside [space-evenly]";

$GLOBALS['TL_LANG']['responsive']['addResponsive'] = [
    0 => "Define responsive width",
];

$GLOBALS['TL_LANG']['responsive']['addResponsiveChildren'] = [
    0 => "Define responsive width for child elements",
];

$GLOBALS['TL_LANG']['responsive']['overwriteResponsiveChildren'] = [
    0 => "Overwrite responsive width for child elements",
    1 => "Overwrite the module settings <i>%s</i>"
];

$GLOBALS['TL_LANG']['responsive']['responsiveFlexDirection'] = [
    0 => "Main axis <span style=\"color: #7f7f7f\">[flex-direction]</span>",
    1 => "Define the direction in which the elements are distributed.",
];

$GLOBALS['TL_LANG']['responsive']['responsiveAlignItems'] = [
    0 => "Element alignment on peripheral axis (per row) <span style=\"color: #7f7f7f\">[align-items]</span>",
    1 => "Define how the elements are aligned vertically (main axis = 'row') or horizontally (main axis = 'column').",
];

$GLOBALS['TL_LANG']['responsive']['responsiveJustifyContent'] = [
    0 => "Alignment on main axis (all elements) <span style=\"color: #7f7f7f\">[justify-content]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveAlignContent'] = [
    0 => "Alignment on peripheral axis (all elements) <span style=\"color: #7f7f7f\">[align-content]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveFlexWrap'] = [
    0 => "Line break <span style=\"color: #7f7f7f\">[flex-wrap]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveCols'] = [
    0 => "Width <span style=\"color: #7f7f7f\">[col]</span>",
    1 => "Define the width in columns.",
    'options' => [
        12 => "12 Columns",
        11 => "11 Columns",
        10 => "10 Columns",
        9 => "9 Columns",
        8 => "8 Columns",
        7 => "7 Columns",
        6 => "6 Columns",
        5 => "5 Columns",
        4 => "4 Columns",
        3 => "3 Columns",
        2 => "2 Columns",
        1 => "1 Column",
        "auto" => "Content dependent [auto]",
        "fill" => "Fill row []",
        "hidden" => "Hidden [none]"
    ]
];

$GLOBALS['TL_LANG']['responsive']['responsiveOffsets'] = [
    0 => "Offset from left <span style=\"color: #7f7f7f\">[offset]</span>",
    1 => "Define the offset from the left in columns.",
    'options' => [
        12 => "12 Columns",
        11 => "11 Columns",
        10 => "10 Columns",
        9 => "9 Columns",
        8 => "8 Columns",
        7 => "7 Columns",
        6 => "6 Columns",
        5 => "5 Columns",
        4 => "4 Columns",
        3 => "3 Columns",
        2 => "2 Columns",
        1 => "1 Column",
        "auto" => "Right aligned [auto]",
        "none" => "No offset [none]"
    ]
];

$GLOBALS['TL_LANG']['responsive']['responsiveOrder'] = [
    0 => "Order as digit <span style=\"color: #7f7f7f\">[order]</span>",
    1 => ""
];

$GLOBALS['TL_LANG']['responsive']['responsiveAlignSelf'] = [
    0 => "Alignment <span style=\"color: #7f7f7f\">[align-self]</span>",
    1 => ""
];

$GLOBALS['TL_LANG']['responsive']['responsiveSpacingTop'] = [
    0 => "Top spacing <span style=\"color: #7f7f7f\">[pt]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveSpacingBottom'] = [
    0 => "Bottom spacing <span style=\"color: #7f7f7f\">[pb]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveContainerSize'] = [
    0 => "Container Size <span style=\"color: #7f7f7f\">[container]</span>",
    1 => "",
];
