<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\StringUtil;
use Contao\System;

class ResponsiveFrontendService
{
    public function getResponsiveClasses(string $strData, string $strMapping, array $arrOptions = []): array
    {
        $arrClasses = [];

        if ($strData ?? false) {
            $arrValues = StringUtil::deserialize($strData, true);
            $objConfig = new $GLOBALS['responsive']['config']();

            // HOOK: add custom logic
            if (isset($GLOBALS['TL_HOOKS']['alterResponsiveValues']) && \is_array($GLOBALS['TL_HOOKS']['alterResponsiveValues']))
            {
                foreach ($GLOBALS['TL_HOOKS']['alterResponsiveValues'] as $callback)
                {
                    System::importStatic($callback[0])->{$callback[1]}($arrValues, $strMapping, $objConfig, $arrOptions);
                }
            }

            foreach ($arrValues as $strBreakpoint => $varValue) {
                $strClass = is_array($objConfig->{$strMapping}) ? ($objConfig->{$strMapping}[$varValue] ?? '') : $objConfig->{$strMapping};

                $strClass = str_replace(
                    ['{{modifier}}', '{{value}}'],
                    [$objConfig->arrBreakpoints[$strBreakpoint]['modifier'], $varValue],
                    $strClass);

                $strClass = preg_replace_callback('/\{{(\w+)}}/', function ($match) use ($arrOptions) {
                    $matched = $match[0];
                    $name = $match[1];
                    return isset($arrOptions[$name]) ? $arrOptions[$name] : $matched;
                }, $strClass);

                $arrClasses[] = $strClass;
            }
        }
        return $arrClasses;
    }

    public function getColClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varColClasses');
    }

    public function getOffsetClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varOffsetClasses');
    }

    public function getOrderClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varOrderClasses');
    }

    public function getAlignSelfClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varAlignSelfClasses');
    }

    public function getSpacingClasses($strData, $strDirection = ""): array
    {
        return $this->getResponsiveClasses($strData, 'varSpacingClasses', ['direction' => $strDirection]);
    }

    public function getAllResponsiveClasses(array $arrData, array $arrFields = []): array
    {
        return
            array_merge(
                $this->getColClasses($arrData[$arrFields['cols'] ?? 'responsiveCols'] ?? "", $arrData),
                $this->getOffsetClasses($arrData[$arrFields['offsets'] ?? 'responsiveOffsets'] ?? "", $arrData),
                $this->getOrderClasses($arrData[$arrFields['order'] ?? 'responsiveOrder'] ?? "", $arrData),
                $this->getAlignSelfClasses($arrData[$arrFields['align-self'] ?? 'responsiveAlignSelf'] ?? "", $arrData)
            );
    }

    public function getContainerClasses($strData): array
    {
        if (!$strData) return [];

        $objConfig = new $GLOBALS['responsive']['config']();
        return is_array($objConfig->arrContainerSizes[$strData]) ? $objConfig->arrContainerSizes[$strData] : [$objConfig->arrContainerSizes[$strData]];
    }

    public function getAllContainerClasses(array $arrData, array $arrFields = []): array
    {
        return
            array_merge(
                $this->getContainerClasses($arrData[$arrFields['containerSize'] ?? 'responsiveContainerSize'] ?? "", $arrData),
                $this->getSpacingClasses($arrData[$arrFields['spacingTop'] ?? 'responsiveSpacingTop'] ?? "", 't', $arrData),
                $this->getSpacingClasses($arrData[$arrFields['spacingBottom'] ?? 'responsiveSpacingBottom'] ?? "", 'b', $arrData),
            );
    }

    public function getFlexDirectionClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varFlexDirectionClasses');
    }

    public function getFlexWrapClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varFlexWrapClasses');
    }

    public function getAlignItemsClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varAlignItemsClasses');
    }

    public function getAlignContentClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varAlignContentClasses');
    }

    public function getJustifyContentClasses($strData): array
    {
        return $this->getResponsiveClasses($strData, 'varJustifyContentClasses');
    }

    public function getAllInnerContainerClasses(array $arrData, array $arrFields = []): array
    {
        return
            array_merge(
                $this->getFlexDirectionClasses($arrData[$arrFields['flexDirection'] ?? 'responsiveFlexDirection'] ?? "", $arrData),
                $this->getFlexWrapClasses($arrData[$arrFields['flexWrap'] ?? 'responsiveFlexWrap'] ?? "", $arrData),
                $this->getAlignItemsClasses($arrData[$arrFields['alignItems'] ?? 'responsiveAlignItems'] ?? "", $arrData),
                $this->getAlignContentClasses($arrData[$arrFields['alignContent'] ?? 'responsiveAlignContent'] ?? "", $arrData),
                $this->getJustifyContentClasses($arrData[$arrFields['justifyContent'] ?? 'responsiveJustifyContent'] ?? "", $arrData),
            );
    }
}