<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

class WrapperListener{
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    #[AsCallback(table: 'tl_form_field', target: 'config.onload')]
    public function addContainerSubpalette(DataContainer $objDca):void
    {
        if(!$objDca->getActiveRecord()) return;

        $arrContainerSizes = (new $GLOBALS['responsive']['config'])->getContainerSizes();
        if(!$arrContainerSizes) return;

        foreach ($arrContainerSizes as $strContainerSize){
            $GLOBALS['TL_DCA'][$objDca->table]['subpalettes']["responsiveContainer_$strContainerSize"] = $GLOBALS['TL_DCA'][$objDca->table]['subpalettes']['responsiveContainer_responsiveContainerSizes'];
        }
    }
}