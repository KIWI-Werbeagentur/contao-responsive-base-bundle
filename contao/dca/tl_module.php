<?php

use Contao\Controller;

Controller::loadDataContainer('responsive');

//Set default values dynamically
$GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

$GLOBALS['TL_DCA']['tl_module']['fields']['addResponsive'] = $GLOBALS['TL_DCA']['columnActivate']['fields']['addResponsive'];
$GLOBALS['TL_DCA']['tl_module']['fields'] += $GLOBALS['TL_DCA']['column']['fields'];
$GLOBALS['TL_DCA']['tl_module']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];
$GLOBALS['TL_DCA']['tl_module']['fields']['responsiveColsItems'] = $GLOBALS['TL_DCA']['column']['fields']['responsiveCols'];

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'addResponsive';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['addResponsive'] = implode(',',array_keys($GLOBALS['TL_DCA']['column']['fields']));
