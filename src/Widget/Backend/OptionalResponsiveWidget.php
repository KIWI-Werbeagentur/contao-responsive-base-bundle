<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Widget\Backend;

use Contao\Input;
use Contao\StringUtil;

class OptionalResponsiveWidget extends ResponsiveWidget
{
    protected $strCssClass = "responsive-widget";

    public function generate(): string
    {
        $arrValues = StringUtil::deserialize($this->value);
dump($arrValues);
        $strWidget = parent::generate();
        $strChecked = !$arrValues || (isset($arrValues[array_key_first($this->arrBreakpoints)]) && count($arrValues) == 1) || !count($arrValues) ? '' : 'checked';

        return "<input type='checkbox' id='{$this->strName}-responsive' name='{$this->strName}-responsive' {$strChecked}/><label for='{$this->strName}-responsive'>{$GLOBALS['TL_LANG']['responsive']['responsive']}</label>{$strWidget}";
    }

    protected function validator($varInput, $arrValues = [])
    {
        if (Input::post("{$this->strName}-responsive")) {
            return parent::validator($varInput, $arrValues);
        }
        parent::validator($varInput, $arrValues);
        return serialize([array_key_first($this->arrBreakpoints) => Input::post($this->strName.$this->arrBreakpoints[array_key_first($this->arrBreakpoints)]['modifier'])]);
    }
}
