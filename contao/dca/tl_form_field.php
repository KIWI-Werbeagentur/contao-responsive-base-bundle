<?php

use Contao\Controller;
use Kiwi\Contao\ResponsiveBaseBundle\DataContainer\WrapperListener;

//Set default values dynamically
$GLOBALS['TL_DCA']['tl_form_field']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

Controller::loadDataContainer('responsive');
$GLOBALS['TL_DCA']['tl_form_field']['fields'] += $GLOBALS['TL_DCA']['column']['fields'];



// Apply Container-Option to Elementgroup
$GLOBALS['TL_DCA']['tl_form_field']['config']['onload_callback'][] = [WrapperListener::class, 'addContainerSubpalette'];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['responsiveContainer'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr",'submitOnChange' => true, 'chosen'=>true],
    'options_callback' => function () {
        return ['default'] + (new $GLOBALS['responsive']['config'])->getContainerSizes();
    },
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContainer'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['__selector__'][] = 'responsiveContainer';
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['responsiveContainer_0'] = 'responsiveCols,responsiveOffsets';

// Used for all container sizes (Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Content->addContainerSubpalette())
$GLOBALS['TL_DCA']['tl_form_field']['subpalettes']['responsiveContainer_responsiveContainerSizes'] = implode(',',array_keys($GLOBALS['TL_DCA']['container']['fields']));