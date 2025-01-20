<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Widget\Backend;

use Contao\StringUtil;

class OptionalResponsiveSubpaletteWidget extends OptionalResponsiveWidget
{
    protected $blnSubmitInput = true;

    protected $arrDca;

    protected array $arrSubWidgets = [];

    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        $this->arrDca = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField];
        $this->generateSubpaletteWidgets();
    }

    protected function generateSubpaletteWidgets()
    {
        $arrValues = StringUtil::deserialize($this->value);
if ($this->objDca->id == 237) dump($this->arrConfiguration, $this->arrDca, $arrValues);

//        $this->selectorWidget = $objSelectorWidget = new SelectMenu($this::getAttributesFromDca(
//            $this->arrDca,
//            $this->strField,
//            $arrValues[$this->strField] ?? null,
//            $this->strField,
//            $this->strTable,
//            $this,
//        ));
//        dump($objSelectorWidget);
//        $objSelectorWidget->strId = $this->strId;
//        $objSelectorWidget->storeValues = true;
//        $objSelectorWidget->currentRecord = $this->currentRecord;

        foreach ($this->arrDca['subpalettes'] as $k => $v) {
            // do not add options twice that have already been added via options
            if (array_find($this->arrOptions, fn ($item) => $item['value'] === $k)) continue;
            $this->arrOptions[] = [
                'value' => $k,
                'label' => $this->arrDca['reference'][$k] ?? $k,
            ];
        }

        $arrValues = StringUtil::deserialize($this->value, true);
dump($arrValues);

        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
//dump($this->arrDca['subpalettes'][$arrValues[$strBreakpoint]] ?? 'nix drin');
            foreach ($this->arrDca['subpalettes'][$arrValues[$strBreakpoint] ?? $this->arrOptions[0]['value'] ?? ''] ?? [] as $inputName => $value) {
if ($this->objDca->id == 237) dump($inputName, $value);
                if (is_string($value)) {
                    $arrSubDca = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$value];
                } elseif (is_array($value)) {
                    $arrSubDca = $value;
                } else {
                    continue;
                }
                unset($arrSubDca['sql']);
                $inputName = $this->strField . '-' . $inputName . $arrBreakpoint['modifier'];

if ($this->objDca->id == 237) dump($inputName, $arrSubDca);
                $this->arrSubWidgets[$strBreakpoint][$inputName] = $objSubWidget = new $GLOBALS['BE_FFL'][$arrSubDca['inputType']]($this::getAttributesFromDca(
//            $this->arrSubWidgets[$inputName] = $objSubWidget = new ResponsiveWidget($this::getAttributesFromDca(
                    $arrSubDca,
                    $inputName,
                    $arrValues[$inputName] ?? null,
                    $inputName,
                    $this->strTable,
                ));
            }
        }

    }

//    public function generate(): string
//    {
//        $strField = $this->selectorWidget->parse();
//
//        foreach ($this->arrSubWidgets as $objSubWidget) {
//            $strField .= $objSubWidget->parse();
//        }
//
//        return $strField;
//    }

    protected function validator($varInput, $arrValues = [])
    {
if ($this->objDca->id == 237) dump($varInput, $arrValues);
        $arrValues = StringUtil::deserialize(parent::validator($varInput, $arrValues));
if ($this->objDca->id == 237) dump($arrValues);

//        $arrValues = [$this->strField => parent::validator($varInput)];

//        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
//            $this->arrWidgets[$arrBreakpoint['modifier']]->validate(Input::post("{$this->strName}{$arrBreakpoint['modifier']}"));
//            if($this->arrWidgets[$arrBreakpoint['modifier']]->arrErrors) {
//                $this->addError('');
//            }
//
//            if (($strValue = Input::post("{$this->strName}{$arrBreakpoint['modifier']}")) !== "") {
//                $arrValues[$strBreakpoint] = $strValue;
//            }
//        }
//
//        if($this->arrErrors){
//            return '';
//        }
//
//        return serialize($arrValues);

        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
dump($strBreakpoint, $arrBreakpoint);
            foreach ($GLOBALS['TL_DCA'][$this->strTable]['fields'][$this->strField]['subpalettes'][$arrValues[$strBreakpoint] ?? $this->selectorWidget->arrOptions[0]['value'] ?? ''] ?? [] as $fieldName => $value) {
dump($fieldName, $value);
                $fieldName = $this->strField . '-' . $fieldName . $arrBreakpoint['modifier'];
//if ($this->objDca->id == 237) dump($arrValues, $fieldName, !empty($this->arrSubWidgets[$strBreakpoint][$fieldName]) ? $this->arrSubWidgets[$strBreakpoint][$fieldName]->validate() : 'not set', $this);
//dump($this->arrSubWidgets[$strBreakpoint]);
                if (!empty($this->arrSubWidgets[$strBreakpoint][$fieldName])) {
                    $this->arrSubWidgets[$strBreakpoint][$fieldName]->validate();
                    if($this->arrSubWidgets[$strBreakpoint][$fieldName]->arrErrors) {
                        $this->addError('');
                    }
//                    $arrValues[$fieldName] = parent::validator($this->arrSubWidgets[$strBreakpoint][$fieldName]->varValue);
                    $arrValues[$fieldName] = $this->arrSubWidgets[$strBreakpoint][$fieldName]->varValue;
                }
dump($this->arrSubWidgets);
            }
        }
if ($this->objDca->id == 237) dump($arrValues);

        return serialize($arrValues);
    }

    protected function parseChildWidget(string $strBreakpoint, array $arrBreakpoint)
    {
        $strWidgets = parent::parseChildWidget($strBreakpoint, $arrBreakpoint);
dump($strWidgets);

        foreach ($this->arrSubWidgets[$strBreakpoint] ?? [] as $objSubWidget) {
            $strWidgets .= $objSubWidget->parse();
        }

        return $strWidgets;
    }
}
