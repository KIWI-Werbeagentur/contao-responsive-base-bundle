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

    /**
     * The responsive frontend service supplies the configuration, built once per request, as
     * resolveValue() runs for every placeholder of every design class.
     */
    public function __construct(private readonly ResponsiveFrontendService $responsiveFrontendService)
    {
    }

    public function resolveValue($strName, &$strValue)
    {
        parent::resolveValue($strName, $strValue);

        switch ($strName) {
            case 'modifiersAll':
                $arrReturn = [];
                foreach ($this->responsiveFrontendService->getConfig()->arrBreakpoints as $strKey => $arrBreakpoint) {
                    $arrReturn[] = str_replace('{{modifier}}', $strKey, $GLOBALS['design']['modifiers']['pattern']);
                }
                $strValue = implode($GLOBALS['design']['modifiers']['delimiter'], $arrReturn);
                break;
            case 'modifiers':
                $arrReturn = [];
                foreach (array_merge($this->arrBreakpoints, [$this->strBreakpoint]) as $strBreakpoint) {
                    $arrReturn[] = str_replace('{{modifier}}', $strBreakpoint, $GLOBALS['design']['modifiers']['pattern']);
                }
                $strValue = implode($GLOBALS['design']['modifiers']['delimiter'], $arrReturn);
                break;
            default:
                if ($GLOBALS['responsive'] ?? false) {
                    $arrBreakpoints = $this->responsiveFrontendService->getConfig()->arrBreakpoints;
                    if (isset($arrBreakpoints[$this->strBreakpoint][$strName])) $strValue = $arrBreakpoints[$this->strBreakpoint][$strName];
                }
                break;
        }
    }

    public function getGlobalStrings($arrData, $strMapping, $strField = "")
    {
        if (!$strField) $strField = $strMapping;
        $this->arrData = $arrData;

        $arrStyles = ($arrData[$strField] ?? false) ? StringUtil::deserialize($arrData[$strField], true) : [];
        $arrReturn = [];

        if (array_key_exists(0, $arrStyles)) {
            $arrReturn[] = $this->getClasses($arrStyles[0], $strMapping, $strField);
        } else {
            foreach (array_reverse($this->responsiveFrontendService->getConfig()->arrBreakpoints) as $strBreakpoint => $arrBreakpoint) {
                if ($arrStyles[$strBreakpoint] ?? false) {
                    $this->strBreakpoint = $strBreakpoint;
                    $arrReturn[] = $this->getClasses($arrStyles[$strBreakpoint], $strMapping, $strField);
                    $this->arrBreakpoints = [];
                } else {
                    $this->arrBreakpoints[] = $strBreakpoint;
                }
            }
        }

        return implode(" ", $arrReturn);
    }
}