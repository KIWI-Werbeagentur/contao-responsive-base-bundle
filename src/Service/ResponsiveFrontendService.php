<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\Controller;
use Contao\StringUtil;
use Contao\System;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\Configuration\ResponsiveConfiguration;

class ResponsiveFrontendService
{
    public function __construct(private readonly PaletteManipulatorExtended $paletteManipulator) {}

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

    public function getResponsiveClasses(string|null $strData, string $strMapping, array $arrOptions = []): array
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

            $excludeValues = $arrOptions['excludeValues'] ?? [];

            foreach ($arrValues as $strBreakpoint => $varValue) {
                if (\in_array($varValue, $excludeValues, true)) {
                    continue;
                }

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
        return $this->getResponsiveClasses($strData, 'varSpacingClasses', [
            'direction'     => $strDirection,
            'excludeValues' => [ResponsiveConfiguration::SPACING_NO_OP],
        ]);
    }

    /**
     * Convenience wrapper around {@see self::getSpacingClasses()} for the top direction.
     * Allows {@see self::getAllContainerClasses()} to use the same single-arg spec shape as
     * the other aggregator methods.
     */
    public function getSpacingTopClasses($strData): array
    {
        return $this->getSpacingClasses($strData, 't');
    }

    /**
     * Convenience wrapper around {@see self::getSpacingClasses()} for the bottom direction.
     */
    public function getSpacingBottomClasses($strData): array
    {
        return $this->getSpacingClasses($strData, 'b');
    }

    public function getRowClass(): string
    {
        return (new $GLOBALS['responsive']['config']())->strRow ?? '';
    }

    public function getAllResponsiveClasses($varData, array $arrFields = [], string $table = 'tl_content'): array
    {
        $arrSpecs = [
            ['cols',       'responsiveCols',       'getColClasses'],
            ['offsets',    'responsiveOffsets',    'getOffsetClasses'],
            ['order',      'responsiveOrder',      'getOrderClasses'],
            ['align-self', 'responsiveAlignSelf',  'getAlignSelfClasses'],
        ];

        $type = self::getProp($varData, 'type') ?: null;

        $arrClasses = [];
        foreach ($arrSpecs as [$strKey, $strDefaultField, $strMethod]) {
            $strField = $arrFields[$strKey] ?? $strDefaultField;
            if (!$this->isFieldInPalette($strField, $type, $table)) {
                continue;
            }
            $arrClasses = array_merge($arrClasses, $this->$strMethod(self::getProp($varData, $strField), $varData));
        }
        return $arrClasses;
    }

    /**
     * Resolve a container-size key to its CSS classes, optionally gated by palette membership.
     *
     * The same value lives under two differently named DCA fields, encoding different
     * propositions:
     *   - `responsiveContainer`     (tl_content, tl_form_field): a *mode selector* answering
     *                               "is this element a container, and if so, what size?" -
     *                               has a `default` option (= treat as column) and drives
     *                               column- vs container-mode subpalettes.
     *   - `responsiveContainerSize` (tl_article, tl_layout):     a *pure size attribute* -
     *                               those records are always containers; only the size varies.
     *
     * Both resolve through the same `arrContainerSizes` map, so the conversion is uniform.
     * Palette membership however is per-field-name: direct callers pass the field name they
     * sourced the value from so the gate consults the right DCA entry for the resolved palette.
     *
     * Note on the internal {@see self::getAllContainerClasses()} call path: it already gates
     * each spec entry against the correct field name in its own outer loop, then calls this
     * method with no `$type` so the inner gate short-circuits to a no-op. The `$field`
     * parameter here therefore matters for *direct* external callers (templates / hooks /
     * tests) that source the value from a field other than `responsiveContainer`.
     *
     * @param string|null $strData  Container-size key looked up in $arrContainerSizes.
     * @param string|null $type     Optional record type for palette gating. Null disables gating.
     * @param string      $table    DCA table whose palette is consulted when $type is set.
     * @param string      $field    Name of the DCA field $strData was sourced from.
     */
    public function getContainerClasses($strData, ?string $type = null, string $table = 'tl_content', string $field = 'responsiveContainer'): array
    {
        if (!$strData) return [];
        if (!$this->isFieldInPalette($field, $type, $table)) {
            return [];
        }

        $objConfig = new $GLOBALS['responsive']['config']();
        return is_array($objConfig->arrContainerSizes[$strData]) ? $objConfig->arrContainerSizes[$strData] : [$objConfig->arrContainerSizes[$strData]];
    }

    /**
     * Resolve article-/layout-level container classes (size + top/bottom spacing).
     *
     * Unlike the sibling aggregators, this method operates on the article/layout level: the
     * fields it reads (`responsiveContainerSize`, `responsiveSpacingTop`, `responsiveSpacingBottom`)
     * live on `tl_article` and `tl_layout` via the `containerSize` / `space` DCA buckets, not
     * on `tl_content`. The `$table` default therefore differs from the sibling aggregators -
     * `tl_article` is the dominant caller (mod_article.html.twig); layout-level callers
     * override with `tl_layout`.
     */
    public function getAllContainerClasses($varData, array $arrFields = [], string $table = 'tl_article'): array
    {
        $arrSpecs = [
            ['containerSize', 'responsiveContainerSize', 'getContainerClasses'],
            ['spacingTop',    'responsiveSpacingTop',    'getSpacingTopClasses'],
            ['spacingBottom', 'responsiveSpacingBottom', 'getSpacingBottomClasses'],
        ];

        $type = self::getProp($varData, 'type') ?: null;

        $arrClasses = [];
        foreach ($arrSpecs as [$strKey, $strDefaultField, $strMethod]) {
            $strField = $arrFields[$strKey] ?? $strDefaultField;
            if (!$this->isFieldInPalette($strField, $type, $table)) {
                continue;
            }
            $arrClasses = array_merge($arrClasses, $this->$strMethod(self::getProp($varData, $strField)));
        }
        return $arrClasses;
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

    public function getAllInnerContainerClasses($varData, array $arrFields = [], string $table = 'tl_content'): array
    {
        $arrSpecs = [
            ['flexDirection',  'responsiveFlexDirection',  'getFlexDirectionClasses'],
            ['flexWrap',       'responsiveFlexWrap',       'getFlexWrapClasses'],
            ['alignItems',     'responsiveAlignItems',     'getAlignItemsClasses'],
            ['alignContent',   'responsiveAlignContent',   'getAlignContentClasses'],
            ['justifyContent', 'responsiveJustifyContent', 'getJustifyContentClasses'],
        ];

        $type = self::getProp($varData, 'type') ?: null;

        $arrClasses = [];
        foreach ($arrSpecs as [$strKey, $strDefaultField, $strMethod]) {
            $strField = $arrFields[$strKey] ?? $strDefaultField;
            if (!$this->isFieldInPalette($strField, $type, $table)) {
                continue;
            }
            $arrClasses = array_merge($arrClasses, $this->$strMethod(self::getProp($varData, $strField), $varData));
        }
        return $arrClasses;
    }

    /**
     * Returns whether the field is part of the resolved palette.
     *
     * Always returns true if no palette gating applies - either because the caller did not provide
     * a type (`$type === null`), or because the type does not correspond to any palette in
     * the given table (soft gating: legacy callers that pass varData whose `type` was not
     * meant to identify a palette in $table keep their previous "all fields render" behavior).
     */
    protected function isFieldInPalette(string $strField, ?string $type, string $table = 'tl_content'): bool
    {
        if ($type === null || $type === '') {
            return true;
        }

        Controller::loadDataContainer($table);

        if (!isset($GLOBALS['TL_DCA'][$table]['palettes'][$type])) {
            return true;
        }

        return $this->paletteManipulator->hasField($type, $table, $strField);
    }
}
