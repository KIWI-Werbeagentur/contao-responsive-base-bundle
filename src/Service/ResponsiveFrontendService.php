<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\StringUtil;
use Contao\System;

class ResponsiveFrontendService
{
    public static function propExists($varTarget, $strProp)
    {
        return (is_array($varTarget) && array_key_exists($strProp, $varTarget)) || (is_object($varTarget) && property_exists($varTarget, $strProp));
    }

    public static function getProp($varTarget, $strProp)
    {
        if (is_array($varTarget)) {
            return $varTarget[$strProp] ?? "";
        }
        if (is_object($varTarget)) {
            return $varTarget->{$strProp} ?? "";
        }
        return "";
    }

    public function getResponsiveClasses(string $strData, string $strMapping, array $arrOptions = []): array
    {
        $arrClasses = [];

        if ($strData ?? false) {
            $arrValues = StringUtil::deserialize($strData, true);
            $objConfig = new $GLOBALS['responsive']['config']();

            // HOOK: add custom logic
            if (isset($GLOBALS['TL_HOOKS']['alterResponsiveValues']) && \is_array($GLOBALS['TL_HOOKS']['alterResponsiveValues'])) {
                foreach ($GLOBALS['TL_HOOKS']['alterResponsiveValues'] as $callback) {
                    System::importStatic($callback[0])->{$callback[1]}($arrValues, $strMapping, $objConfig, $arrOptions);
                }
            }

            foreach ($arrValues as $strBreakpoint => $varValue) {
                if ($objConfig->{$strMapping}) {
                    $strClass = is_array($objConfig->{$strMapping}) ? ($objConfig->{$strMapping}[$varValue] ?? '') : $objConfig->{$strMapping};
                } else {
                    $strClass = $strMapping;
                }

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

    public function getAllResponsiveClasses($varData, array $arrFields = []): array
    {
        return
            array_merge(
                $this->getColClasses(self::getProp($varData, $arrFields['cols'] ?? 'responsiveCols'), $varData),
                $this->getOffsetClasses(self::getProp($varData, $arrFields['offsets'] ?? 'responsiveOffsets'), $varData),
                $this->getOrderClasses(self::getProp($varData, $arrFields['order'] ?? 'responsiveOrder'), $varData),
                $this->getAlignSelfClasses(self::getProp($varData, $arrFields['align-self'] ?? 'responsiveAlignSelf'), $varData)
            );
    }

    public function getContainerClasses($strData): array
    {
        if (!$strData) return [];

        $objConfig = new $GLOBALS['responsive']['config']();
        return is_array($objConfig->arrContainerSizes[$strData]) ? $objConfig->arrContainerSizes[$strData] : [$objConfig->arrContainerSizes[$strData]];
    }

    public function getAllContainerClasses($varData, array $arrFields = []): array
    {
        return
            array_merge(
                $this->getContainerClasses(self::getProp($varData, $arrFields['containerSize'] ?? 'responsiveContainerSize'), $varData),
                $this->getSpacingClasses(self::getProp($varData, $arrFields['spacingTop'] ?? 'responsiveSpacingTop'), 't', $varData),
                $this->getSpacingClasses(self::getProp($varData, $arrFields['spacingBottom'] ?? 'responsiveSpacingBottom'), 'b', $varData),
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

    public function getAllInnerContainerClasses($varData, array $arrFields = []): array
    {
        return
            array_merge(
                $this->getFlexDirectionClasses(self::getProp($varData, $arrFields['flexDirection'] ?? 'responsiveFlexDirection'), $varData),
                $this->getFlexWrapClasses(self::getProp($varData, $arrFields['flexWrap'] ?? 'responsiveFlexWrap'), $varData),
                $this->getAlignItemsClasses(self::getProp($varData, $arrFields['alignItems'] ?? 'responsiveAlignItems'), $varData),
                $this->getAlignContentClasses(self::getProp($varData, $arrFields['alignContent'] ?? 'responsiveAlignContent'), $varData),
                $this->getJustifyContentClasses(self::getProp($varData, $arrFields['justifyContent'] ?? 'responsiveJustifyContent'), $varData),
            );
    }
}