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

// Apply Container-Option to Elementgroup
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = [WrapperListener::class, 'addContainerSubpalette'];


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

//Set palettes
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'responsiveContainer';
// BUG: only working with both 'values':
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_'] = 'responsiveCols,responsiveOffsets';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_0'] = 'responsiveCols,responsiveOffsets';

// Used for all container sizes (Kiwi\Contao\ResponsiveBaseBundle\DataContainer\Content->addContainerSubpalette())
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_responsiveContainerSizes'] = implode(',',array_keys($GLOBALS['TL_DCA']['container']['fields']));


/*
    * INCLUDES
*/
$GLOBALS['TL_DCA']['tl_content']['fields']['addResponsiveChildren'] = $GLOBALS['TL_DCA']['columnActivate']['fields']['addResponsiveChildren'];
$GLOBALS['TL_DCA']['tl_content']['fields']['responsiveColsItems'] = $GLOBALS['TL_DCA']['column']['fields']['responsiveCols'];
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'addResponsiveChildren';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['addResponsiveChildren'] = implode(',',array_merge(['responsiveColsItems'], array_keys($GLOBALS['TL_DCA']['container']['fields'] ?? [])));