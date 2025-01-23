<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Widget\Backend;

use Contao\StringUtil;

class OptionalResponsiveSubpaletteWidget extends OptionalResponsiveWidget
{
    public function __construct($arrAttributes = null)
    {
        parent::__construct($arrAttributes);

        $this->generateSubpaletteWidgets();
    }

    protected function generateSubpaletteWidgets(): void
    {
        $arrValues = StringUtil::deserialize($this->value);

        foreach ($this->arrDca['subpalettes'] as $k => $v) {
            // do not add options twice that have already been added via options
            if (array_find($this->arrOptions, fn ($item) => $item['value'] === $k)) continue;
            $this->arrOptions[] = [
                'value' => $k,
                'label' => $this->arrDca['reference'][$k] ?? $k,
            ];
        }

        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            foreach ($this->arrDca['subpalettes'][$arrValues[$strBreakpoint] ?? $this->arrOptions[0]['value'] ?? ''] ?? [] as $baseFieldName => $value) {
                if (is_string($value)) {
                    $arrSubDca = $GLOBALS['TL_DCA'][$this->strTable]['fields'][$value];
                } elseif (is_array($value)) {
                    $arrSubDca = $value;
                } else {
                    continue;
                }
                unset($arrSubDca['sql']);
                $inputName = $this->strField . '-' . $baseFieldName . $arrBreakpoint['modifier'];

                $this->arrSubWidgets[$strBreakpoint][$inputName] = new $GLOBALS['BE_FFL'][$arrSubDca['inputType']]($this::getAttributesFromDca(
                    $arrSubDca,
                    $inputName,
                    $arrValues[$inputName] ?? null,
                    $inputName,
                    $this->strTable,
                ));
            }
        }
    }

    protected function validator($varInput, $arrValues = [])
    {
        $arrValues = StringUtil::deserialize(parent::validator($varInput, $arrValues));

        foreach ($this->arrBreakpoints as $strBreakpoint => $arrBreakpoint) {
            $strSubpalette = $arrValues[$strBreakpoint] ?? $this->selectorWidget->arrOptions[0]['value'] ?? '';
            foreach ($this->arrDca['subpalettes'][$strSubpalette] ?? [] as $baseFieldName => $value) {
                $fieldName = $this->strField . '-' . $baseFieldName . $arrBreakpoint['modifier'];
                $subWidget = $this->arrSubWidgets[$strBreakpoint][$fieldName] ?? null;
                if ($subWidget) {
                    $subWidget->validate();
                    if ($subWidget->arrErrors) {
                        $this->addError('');
                    }
                    $arrValues[$fieldName] = $subWidget->varValue;
                }
            }
        }

        return serialize($arrValues);
    }

    protected function parseChildWidget(string $strBreakpoint, array $arrBreakpoint): string
    {
        $strWidgets = parent::parseChildWidget($strBreakpoint, $arrBreakpoint);

        foreach ($this->arrSubWidgets[$strBreakpoint] ?? [] as $objSubWidget) {
            $strWidgets .= $objSubWidget->parse();
        }

        return $strWidgets;
    }
}
