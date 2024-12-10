<?php

use Contao\Controller;
use \Contao\CoreBundle\DataContainer\PaletteManipulator;

Controller::loadDataContainer('responsive');

$GLOBALS['TL_DCA']['tl_article']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

$GLOBALS['TL_DCA']['tl_article']['fields']['responsiveContainerSize'] = $GLOBALS['TL_DCA']['containerSize']['fields']['responsiveContainerSize'];

$GLOBALS['TL_DCA']['tl_article']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];
$GLOBALS['TL_DCA']['tl_article']['fields'] += $GLOBALS['TL_DCA']['space']['fields'];

PaletteManipulator::create()
    ->addField(array_merge(['responsiveContainerSize'], array_keys($GLOBALS['TL_DCA']['container']['fields']), array_keys($GLOBALS['TL_DCA']['space']['fields'])), 'layout_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_article');
