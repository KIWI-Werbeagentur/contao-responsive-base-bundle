<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\StringUtil;

class ResponsiveFrontendService
{
    public function getResponsiveClasses(string $strData, string $strMapping): array
    {
        $arrClasses = [];

        if($strData ?? false) {
            $arrValues = StringUtil::deserialize($strData, true);
            $objConfig = new $GLOBALS['responsive']['config']();
            foreach ($arrValues as $strBreakpoint => $varValue){
                $strClass = $objConfig->{$strMapping}[$varValue] ?? '';
                $arrClasses[] = str_replace('{{modifier}}', $objConfig->arrBreakpoints[$strBreakpoint]['modifier'], $strClass);
            }
        }
        return $arrClasses;
    }
}