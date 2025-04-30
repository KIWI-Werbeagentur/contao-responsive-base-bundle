<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Kiwi\Contao\DesignerBundle\Models\ColorModel;
use Kiwi\Contao\DesignerBundle\Service\DesignerFrontendService;

class ResponsiveDesignerFrontendService extends DesignerFrontendService
{
    protected $strBreakpoint = "";
    protected $arrBreakpoints = [];

    public function resolveValue($strName, &$strValue)
    {
        parent::resolveValue($strName,$strValue);

        switch($strName){
            case 'modifiers':
                $arrReturn = [];
                foreach(array_merge($this->arrBreakpoints, [$this->strBreakpoint]) as $strBreakpoint){
                    $arrReturn[] = str_replace('{{modifier}}', $strBreakpoint, $GLOBALS['design']['modifiers']['pattern']);
                }
                $strValue = implode($GLOBALS['design']['modifiers']['delimiter'],$arrReturn);
                break;
            default:
                if ($GLOBALS['responsive'] ?? false) {
                    if($replacement = (new $GLOBALS['responsive']['config']())->arrBreakpoints[$this->strBreakpoint][$strName] ?? false) $strValue = $replacement;
                }
                break;
        }
    }

    public function getGlobalStrings($arrData, $strMapping, $strField = "")
    {
        if (!$strField) $strField = $strMapping;

        $arrStyles = StringUtil::deserialize($arrData[$strField], true);
        $arrReturn = [];

        foreach (array_reverse((new $GLOBALS['responsive']['config']())->arrBreakpoints) ?? [] as $strBreakpoint => $arrBreakpoint) {
            if ($arrStyles[$strBreakpoint] ?? false) {
                $this->strBreakpoint = $strBreakpoint;
                $arrReturn[] = $this->getClasses($arrStyles[$strBreakpoint], $strMapping, $strField);
                $this->arrBreakpoints = [];
            } else {
                $this->arrBreakpoints[] = $strBreakpoint;
            }
        }

        return implode("",$arrReturn);
    }
}