<?php

use Contao\Controller;
use \Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_form']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

Controller::loadDataContainer('responsive');
$GLOBALS['TL_DCA']['tl_form']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];

PaletteManipulator::create()
    ->addField(array_keys($GLOBALS['TL_DCA']['container']['fields']), 'template_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_form');