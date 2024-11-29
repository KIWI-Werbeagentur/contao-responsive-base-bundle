<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

class Content{
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function addContainerSubpalette(DataContainer $objDca)
    {
        if(!$objDca->getActiveRecord()) return;

        $arrContainerSizes = (new $GLOBALS['responsive'])->getContainerSizes();
        if(!$arrContainerSizes) return;

        foreach ($arrContainerSizes as $strContainerSize){
            $GLOBALS['TL_DCA']['tl_content']['subpalettes']["responsiveContainer_$strContainerSize"] = $GLOBALS['TL_DCA']['tl_content']['subpalettes']['responsiveContainer_responsiveContainerSizes'];
        }
    }
}