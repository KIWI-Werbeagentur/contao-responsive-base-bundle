<?php

use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

Controller::loadDataContainer('responsive');


//Set default values dynamically
$GLOBALS['TL_DCA']['tl_layout']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];



//Container size for rows
$GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveContainerSizeHeader'] = $GLOBALS['TL_DCA']['containerSize']['fields']['responsiveContainerSize'];

$GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveContainerSizeFooter'] = $GLOBALS['TL_DCA']['containerSize']['fields']['responsiveContainerSize'];

$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['rows_2rwh'] .= ',responsiveContainerSizeHeader';
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['rows_2rwf'] .= ',responsiveContainerSizeFooter';

PaletteManipulator::create()
    ->addField('responsiveContainerSizeHeader', 'headerHeight')
    ->addField('responsiveContainerSizeFooter', 'footerHeight')
    ->applyToSubpalette('rows_3rw', 'tl_layout');

$GLOBALS['TL_DCA']['tl_layout']['fields']['rows']['default'] = '3rw';



//Container size for surrounding el of cols & col sizes
$GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveContainerSize'] = $GLOBALS['TL_DCA']['containerSize']['fields']['responsiveContainerSize'];
unset($GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveContainerSize']['label']);

$GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveColsLeft'] = $GLOBALS['TL_DCA']['column']['fields']['responsiveCols'];
unset($GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveColsLeft']['label']);

$GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveColsRight'] = $GLOBALS['TL_DCA']['column']['fields']['responsiveCols'];
unset($GLOBALS['TL_DCA']['tl_layout']['fields']['responsiveColsRight']['label']);

$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['cols_2cll'] = 'responsiveColsLeft';
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['cols_2clr'] = 'responsiveColsRight';
$GLOBALS['TL_DCA']['tl_layout']['subpalettes']['cols_3cl'] = $GLOBALS['TL_DCA']['tl_layout']['subpalettes']['cols_2cll'] . ',' . $GLOBALS['TL_DCA']['tl_layout']['subpalettes']['cols_2clr'];

PaletteManipulator::create()
    ->addField('responsiveContainerSize', null, PaletteManipulator::POSITION_PREPEND)
    ->applyToSubpalette('cols_2cll', 'tl_layout')
    ->applyToSubpalette('cols_2clr', 'tl_layout')
    ->applyToSubpalette('cols_3cl', 'tl_layout');

$GLOBALS['TL_DCA']['tl_layout']['fields']['cols']['default'] = '1cl';