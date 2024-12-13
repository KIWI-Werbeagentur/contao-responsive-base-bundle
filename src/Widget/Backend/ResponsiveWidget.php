<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Widget\Backend;

use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class ResponsiveWidget extends Widget
{
    protected $strCssClass = "responsive-widget";
    protected $blnSubmitInput = true;
    protected $strTemplate = 'be_widget';

    protected $arrBreakpoints;
    protected $strLabelIcon;

    protected $arrDca;

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        $this->arrBreakpoints = (new $GLOBALS['responsive']['config'])->arrBreakpoints;
        $this->arrDca = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField];
        $this->strLabelIcon = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label']['icon'] ?? null;
    }

    public function generateLabel(): string
    {
        $this->strLabel = $this->strLabelIcon ? \Safe\file_get_contents($this->strLabelIcon) . $this->strLabel : $this->strLabel;
        return parent::generateLabel();
    }

    public function generate(): string
    {
        System::loadLanguageFile('default', 'de');
        $arrInputs = [];
        $arrConfigurations = [];
        $strInputType = $this->arrDca['responsiveInputType'] ?? '';
        $strClass = $GLOBALS['BE_FFL'][$strInputType];
        $arrValues = StringUtil::deserialize($this->value);

        $i = 0;

        //Create field for every Breakpoint
        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            $objWidget = (
            new $strClass(self::getAttributesFromDca(
                $this->arrDca,
                "{$this->strField}{$arrBreakpoint['modifier']}",
                $arrValues[$strBreakpoint] ?? null,
                "{$this->strField}{$arrBreakpoint['modifier']}",
                $this->strTable,
                $this
            )));
            $objWidget->strField = "{$this->strField}";
            $objWidget->strId = "{$this->strField}{$arrBreakpoint['modifier']}";
            $objWidget->storeValues = true;
            $objWidget->mandatory = $i == 0;
            $objWidget->options = ($i == 0 ? ($this->arrConfiguration['options'] ?? []) : array_merge([['value' => '', 'label' => ($GLOBALS['TL_LANG']['responsive']['inherit'] ?? 'inherit')]], ($this->arrConfiguration['options'] ?? [])));
            $objWidget->label = $GLOBALS['TL_LANG']['responsive']['breakpoint'][$strBreakpoint][0] ?? $strBreakpoint;
            $objWidget->currentRecord = $this->currentRecord;

            $strField = $objWidget->parse();

            $arrInputs[$strBreakpoint] = sprintf("<div class='%s__item'>%s</div>", $this->strCssClass, $strField);
            $i++;
        }

        if (isset($GLOBALS['TL_HOOKS']['alterResponsiveBackendWidgetOptions']) && is_array($GLOBALS['TL_HOOKS']['alterResponsiveBackendWidgetOptions'])) {
            foreach ($GLOBALS['TL_HOOKS']['alterResponsiveBackendWidgetOptions'] as $callback) {
                $arrInputs = System::importStatic($callback[0])->{$callback[1]}($arrInputs, $this, $arrConfigurations);
            }
        }

        return sprintf("<div class='%s'>%s</div>", $this->strCssClass, implode("", $arrInputs));
    }

    protected function validator($varInput, $arrValues = [])
    {
        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            if (($strValue = Input::post("{$this->strName}{$arrBreakpoint['modifier']}")) !== "") {
                $arrValues[$strBreakpoint] = $strValue;
            }
            parent::validator($varInput);
        }

        return serialize($arrValues);
    }
}