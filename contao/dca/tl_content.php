<?php

use Contao\Controller;
use Kiwi\Contao\ResponsiveBaseBundle\DataContainer\WrapperListener;

/*
    * COLUMNS
*/
//Set default values dynamically
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [$GLOBALS['responsive']['config'], 'getDefaults'];

//Add multiple reusable fields
Controller::loadDataContainer('responsive');
$GLOBALS['TL_DCA']['tl_content']['fields'] += $GLOBALS['TL_DCA']['column']['fields'];


/*
    * CONTAINER
*/

//Create choice between column and container for element_group
$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveContainer'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => "clr",'submitOnChange' => true, 'chosen'=>true],
    'options_callback' => function () {
        return ['default'] + (new $GLOBALS['responsive']['config'])->getContainerSizes();
    },
    'reference' => &$GLOBALS['TL_LANG']['responsive']['flexContainer'],
    'sql' => "varchar(255) NOT NULL default ''"
];

//Add multiple reusable fields
$GLOBALS['TL_DCA']['tl_content']['fields'] += $GLOBALS['TL_DCA']['container']['fields'];
$GLOBALS['TL_DCA']['tl_content']['fields'] += $GLOBALS['TL_DCA']['elementGroupSpace']['fields'];

//Set palettes
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'responsiveContainer';
// BUG: only working with both 'values':
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_'] = 'responsiveCols,responsiveOffsets'; // kept for BC
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_0'] = 'responsiveCols,responsiveOffsets';

// Used for all container sizes, see Kiwi\Contao\ResponsiveBaseBundle\DataContainer\WrapperListener
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_responsiveContainerSizes'] = '';


/*
    * INCLUDES
*/
$GLOBALS['TL_DCA']['tl_content']['fields']['addResponsiveChildren'] = $GLOBALS['TL_DCA']['columnActivate']['fields']['addResponsiveChildren'];
$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveColsItems'] = $GLOBALS['TL_DCA']['column']['fields']['responsiveCols'];
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'addResponsiveChildren';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['addResponsiveChildren'] = implode(',',array_merge(['responsiveColsItems'], array_keys($GLOBALS['TL_DCA']['container']['fields'] ?? [])));
