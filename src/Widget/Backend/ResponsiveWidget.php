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

    protected $arrWidgets = [];

    protected $arrDca;

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        $this->arrBreakpoints = (new $GLOBALS['responsive']['config'])->arrBreakpoints;
        $this->arrDca = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField];
        $this->strLabelIcon = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['label']['icon'] ?? null;

        $i = 0;
        $strInputType = $this->arrDca['responsiveInputType'] ?? '';
        $strClass = $GLOBALS['BE_FFL'][$strInputType];
        $arrValues = StringUtil::deserialize($this->value);
        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            $this->arrWidgets[$arrBreakpoint['modifier']] = $this->generateFormField($strClass, $strBreakpoint, $arrBreakpoint['modifier'], $arrValues, ['mandatory' => $i == 0]);
            $i++;
        }
    }

    public function generateLabel(): string
    {
        $this->strLabel = $this->strLabelIcon ? \Safe\file_get_contents($this->strLabelIcon) . $this->strLabel : $this->strLabel;
        return parent::generateLabel();
    }

    public function generateFormField($strClass, $strBreakpoint, $strModifier, $arrValues = [], $arrOptions = [])
    {
        $objWidget = (
        new $strClass(self::getAttributesFromDca(
            $this->arrDca,
            "{$this->strField}{$strModifier}",
            $arrValues[$strBreakpoint] ?? null,
            "{$this->strField}{$strModifier}",
            $this->strTable,
            $this
        )));
        $objWidget->strField = "{$this->strField}";
        $objWidget->strId = "{$this->strField}{$strModifier}";
        $objWidget->storeValues = true;
        $objWidget->mandatory = $arrOptions['mandatory'] ?? 0;
        $objWidget->options = (($arrOptions['mandatory'] ?? 0) ? ($this->arrConfiguration['options'] ?? []) : array_merge([['value' => '', 'label' => ($GLOBALS['TL_LANG']['responsive']['inherit'] ?? 'inherit')]], ($this->arrConfiguration['options'] ?? [])));
        $objWidget->label = $GLOBALS['TL_LANG']['responsive']['breakpoint'][$strBreakpoint][0] ?? $strBreakpoint;
        $objWidget->currentRecord = $this->currentRecord;

        return $objWidget;
    }

    public function generate(): string
    {
        System::loadLanguageFile('default', 'de');
        $arrInputs = [];
        $arrConfigurations = [];

        $hasEmptyModifier = false;

        //Create field for every Breakpoint
        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            if (!$arrBreakpoint['modifier']) {
                $hasEmptyModifier = true;
            }

            $arrInputs[$strBreakpoint] = sprintf("<div class='%s__item'>%s</div>", $this->strCssClass, $this->parseChildWidget($strBreakpoint, $arrBreakpoint));
        }

        if (!$hasEmptyModifier) {
            $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['eval']['alwaysSave'] = true;
            array_unshift($arrInputs, "<input type='hidden' name='$this->strField'/>");
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
            $this->arrWidgets[$arrBreakpoint['modifier']]->validate(Input::post("{$this->strName}{$arrBreakpoint['modifier']}"));
            if($this->arrWidgets[$arrBreakpoint['modifier']]->arrErrors) {
                $this->addError('');
            }

            if (($strValue = Input::post("{$this->strName}{$arrBreakpoint['modifier']}")) !== "") {
                $arrValues[$strBreakpoint] = $strValue;
            }
        }

        if($this->arrErrors){
            return '';
        }

        return serialize($arrValues);
    }

    protected function parseChildWidget(string $strBreakpoint, array $arrBreakpoint)
    {
        return $this->arrWidgets[$arrBreakpoint['modifier']]->parse();
    }
}