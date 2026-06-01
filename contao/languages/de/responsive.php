<?php

use Kiwi\Contao\ResponsiveBaseBundle\Configuration\ResponsiveConfiguration;

$GLOBALS['TL_LANG']['responsive']['spacings'][ResponsiveConfiguration::SPACING_NO_OP][0] = "Keine Abstandseinstellungen [noop]";

$GLOBALS['TL_LANG']['responsive']['responsive'] = "Responsiv einstellen";
$GLOBALS['TL_LANG']['responsive']['inherit'] = "- Erben -";

$GLOBALS['TL_LANG']['responsive']['flexDirection']['default'] = "Standard [row]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['row'] = "Horizontal [row]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['column'] = "Vertikal [column]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['row-reverse'] = "Horizontal  [row-reverse]";
$GLOBALS['TL_LANG']['responsive']['flexDirection']['column-reverse'] = "Vertikal Reverse [column-reverse]";

$GLOBALS['TL_LANG']['responsive']['flexWrap']['default'] = "Standard [wrap]";
$GLOBALS['TL_LANG']['responsive']['flexWrap']['wrap'] = "Umbrechen [wrap]";
$GLOBALS['TL_LANG']['responsive']['flexWrap']['nowrap'] = "Nicht umbrechen [nowrap]";
$GLOBALS['TL_LANG']['responsive']['flexWrap']['wrap-reverse'] = "Vorne umbrechen [wrap-reverse]";

$GLOBALS['TL_LANG']['responsive']['flexItems']['default'] = "Standard [stretch]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['stretch'] = "Wachsend [stretch]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['baseline'] = "Grundlinie [baseline]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['start'] = "Anfang [start]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['center'] = "Mitte [center]";
$GLOBALS['TL_LANG']['responsive']['flexItems']['end'] = "Ende [end]";

$GLOBALS['TL_LANG']['responsive']['flexContent']['default'] = "Standard [start]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['start'] = "Anfang [start]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['end'] = "Ende [end]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['center'] = "Mitte [center]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['space-between'] = "Verteilt [space-between]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['space-around'] = "Verteilt mit halben Platz nach außen [space-around]";
$GLOBALS['TL_LANG']['responsive']['flexContent']['space-evenly'] = "Verteilt mit Platz nach außen [space-evenly]";

$GLOBALS['TL_LANG']['responsive']['addResponsive'] = [
    0 => "Responsive Breite festlegen",
];

$GLOBALS['TL_LANG']['responsive']['addResponsiveChildren'] = [
    0 => "Responsive Breite für Kindelemente festlegen",
];

$GLOBALS['TL_LANG']['responsive']['overwriteResponsiveChildren'] = [
    0 => "Responsive Breite für Kindelemente überschreiben",
    1 => "Überschreiben Sie die Moduleinstellungen <i>%s</i>"
];

$GLOBALS['TL_LANG']['responsive']['responsiveFlexDirection'] = [
    0 => "Hauptachse <span style=\"color: #7f7f7f\">[flex-direction]</span>",
    1 => "Geben Sie an, in welche Richtung die Elemente verteilt werden sollen.",
];

$GLOBALS['TL_LANG']['responsive']['responsiveAlignItems'] = [
    0 => "Elementanordnung an Gegenachse (pro Reihe) <span style=\"color: #7f7f7f\">[align-items]</span>",
    1 => "Geben Sie an, wie die Elemente vertikal ('row') oder horizontal (\"Vertikal [column]\") ausgerichtet werden sollen.",
];

$GLOBALS['TL_LANG']['responsive']['responsiveJustifyContent'] = [
    0 => "Ausrichtung an Hauptachse (alle Elemente) <span style=\"color: #7f7f7f\">[justify-content]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveAlignContent'] = [
    0 => "Ausrichtung an Gegenachse (alle Elemente) <span style=\"color: #7f7f7f\">[align-content]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveFlexWrap'] = [
    0 => "Umbruch <span style=\"color: #7f7f7f\">[flex-wrap]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveCols'] = [
    0 => "Breite <span style=\"color: #7f7f7f\">[col-*]</span>",
    1 => "Geben Sie die Breite (in Spalten) an.",
    'options' => [
        12 => "12 Spalten",
        11 => "11 Spalten",
        10 => "10 Spalten",
        9 => "9 Spalten",
        8 => "8 Spalten",
        7 => "7 Spalten",
        6 => "6 Spalten",
        5 => "5 Spalten",
        4 => "4 Spalten",
        3 => "3 Spalten",
        2 => "2 Spalten",
        1 => "1 Spalte",
        "auto" => "Inhaltsabhängig [auto]",
        "fill" => "Zeile füllen []",
        "hidden" => "Unsichtbar [none]"
    ]
];

$GLOBALS['TL_LANG']['responsive']['responsiveOffsets'] = [
    0 => "Versatz von links <span style=\"color: #7f7f7f\">[offset-*]</span>",
    1 => "Geben Sie den Versatz von links (in Spalten) an.",
    'options' => [
        12 => "12 Spalten",
        11 => "11 Spalten",
        10 => "10 Spalten",
        9 => "9 Spalten",
        8 => "8 Spalten",
        7 => "7 Spalten",
        6 => "6 Spalten",
        5 => "5 Spalten",
        4 => "4 Spalten",
        3 => "3 Spalten",
        2 => "2 Spalten",
        1 => "1 Spalte",
        "auto" => "rechts [auto]",
        "none" => "Kein Versatz [none]"
    ]
];

$GLOBALS['TL_LANG']['responsive']['responsiveOrder'] = [
    0 => "Reihenfolge als Zahl <span style=\"color: #7f7f7f\">[order]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveAlignSelf'] = [
    0 => "Ausrichtung <span style=\"color: #7f7f7f\">[align-self]</span>",
    1 => ""
];

$GLOBALS['TL_LANG']['responsive']['responsiveSpacingTop'] = [
    0 => "Abstand nach oben <span style=\"color: #7f7f7f\">[pt-*]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveSpacingBottom'] = [
    0 => "Abstand nach unten <span style=\"color: #7f7f7f\">[pb-*]</span>",
    1 => "",
];

$GLOBALS['TL_LANG']['responsive']['responsiveContainerSize'] = [
    0 => "Containergröße <span style=\"color: #7f7f7f\">[container-*]</span>",
    1 => "",
];