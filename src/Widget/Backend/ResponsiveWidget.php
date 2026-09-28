<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Widget\Backend;

use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;

class ResponsiveWidget extends Widget
{
    /** POST value for the non-mandatory inherit option; omitted from serialized save. */
    public const INHERIT_OPTION_VALUE = '__kiwi_responsive_inherit__';

    protected $strCssClass = "responsive-widget";
    protected $blnSubmitInput = true;
    protected $strTemplate = 'be_widget';

    protected $arrBreakpoints;
    protected $strLabelIcon;

    protected $arrWidgets = [];
    protected array $arrSubWidgets = [];

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
        $arrValues = StringUtil::deserialize($this->value, true);
        // A field offering core's blank option can express "not set" at the base breakpoint,
        // so the base must not be mandatory there - otherwise the empty choice is rejected on
        // save. Fields without it keep the mandatory base exactly as before. `base` is passed
        // separately because it no longer coincides with `mandatory`.
        $blnHasBlankOption = (bool) ($this->arrDca['eval']['includeBlankOption'] ?? false);

        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            $this->arrWidgets[$arrBreakpoint['modifier']] = $this->generateFormField($strClass, $strBreakpoint, $arrBreakpoint['modifier'], $arrValues, [
                'base' => $i === 0,
                'mandatory' => $i === 0 && !$blnHasBlankOption,
            ]);
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

        $blnBase = (bool) ($arrOptions['base'] ?? false);
        $arrOpts = $this->arrConfiguration['options'] ?? [];

        if (!$blnBase) {
            // Per-breakpoint selects offer "- Inherit -" and never the base's blank entry:
            // taking over the next lower breakpoint's value is a different statement from
            // having no value at all. getAttributesFromDca() prepends the blank entry to the
            // options of every sub-widget of a field with includeBlankOption, so drop it here.
            $arrOpts = array_values(array_filter($arrOpts, static fn ($arrOption) => ($arrOption['value'] ?? null) !== ''));
            $arrOpts = array_merge([['value' => self::INHERIT_OPTION_VALUE, 'label' => ($GLOBALS['TL_LANG']['responsive']['inherit'] ?? 'inherit')]], $arrOpts);
        }

        $objWidget->options = $arrOpts;

        if (\in_array($arrValues[$strBreakpoint] ?? null, [null, ''], true)) {
            if (!$blnBase) {
                $objWidget->value = self::INHERIT_OPTION_VALUE;
            } elseif (
                !($this->arrDca['eval']['includeBlankOption'] ?? false)
                && ($varDefault = $this->arrDca['default'][$strBreakpoint] ?? null) !== null
                && $varDefault !== ''
            ) {
                // No blank option, so the base cannot say "not set". Left empty it would render
                // with nothing selected, the browser would preselect whatever is first in the
                // option list, and the next save would silently store that — which is how an
                // unconfigured field ends up written as the first concrete option. Preselect the
                // field's declared DCA default instead, so it round-trips as the value it would
                // have been created with.
                $objWidget->value = $varDefault;
            }
            // Base *with* a blank option: leave the value empty. Widget::optionSelected()
            // compares loosely, so '' == null preselects the blank entry on its own, and the
            // field round-trips unchanged.
        }
        $objWidget->label = $GLOBALS['TL_LANG']['responsive']['breakpoint'][$strBreakpoint][0] ?? $strBreakpoint;
        $objWidget->currentRecord = $this->currentRecord;

        return $objWidget;
    }

    public function generate(): string
    {
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

            if (($strValue = Input::post("{$this->strName}{$arrBreakpoint['modifier']}")) !== "" && $strValue !== self::INHERIT_OPTION_VALUE) {
                $arrValues[$strBreakpoint] = $strValue;
            }
        }

        if($this->arrErrors){
            return '';
        }

        return serialize($arrValues);
    }

    protected function parseChildWidget(string $strBreakpoint, array $arrBreakpoint): string
    {
        return $this->arrWidgets[$arrBreakpoint['modifier']]->parse();
    }
}
