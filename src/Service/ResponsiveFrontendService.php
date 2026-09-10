<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Service;

use Contao\Controller;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Kiwi\Contao\CmxBundle\DataContainer\PaletteManipulatorExtended;
use Kiwi\Contao\ResponsiveBaseBundle\Configuration\ResponsiveConfiguration;

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

    /**
     * Resolve the DCA record type $varData's palette is looked up by.
     *
     * For rows and models that is simply the "type" property, but a Contao form widget is not
     * a plain record: FormText::__get('type') answers with the HTML5 *input* type derived from
     * the field's rgxp - "email", "tel", "number", "url", "date" - not with the tl_form_field
     * record type, which stays "text". Reading "type" straight off such a widget therefore
     * gates against a palette that cannot exist, and since the gate fails closed the field
     * silently loses every responsive class (with a deprecation from isFieldInPalette() that
     * blames the caller's $table rather than the widget).
     *
     * A widget's class is the reliable source: $GLOBALS['TL_FFL'] maps exactly the record types
     * to their widget classes. Only widgets are treated this way; every other caller keeps the
     * plain property lookup.
     */
    protected static function resolveType($varData): ?string
    {
        if ($varData instanceof Widget) {
            $arrFfl = $GLOBALS['TL_FFL'] ?? [];

            if (false !== ($strType = array_search($varData::class, $arrFfl, true))) {
                return $strType;
            }

            // an app may register a subclass of a core widget under its own type
            foreach ($arrFfl as $strType => $strClass) {
                if ($varData instanceof $strClass) {
                    return $strType;
                }
            }
        }

        return self::getProp($varData, 'type') ?: null;
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

    public function getSpacingClasses(string|null $strData, string $strDirection = ""): array
    {
        return $this->getResponsiveClasses($strData, 'varSpacingClasses', [
            'direction'     => $strDirection,
            'excludeValues' => [ResponsiveConfiguration::SPACING_NO_OP],
        ]);
    }

    /**
     * Element-group vertical spacing. The base bundle has no per-partial spacing
     * system, so this is identical to {@see self::getSpacingClasses()}; a layering
     * bundle (e.g. contao-bootstrap) overrides it to resolve the group partials.
     */
    public function getGroupSpacingClasses(string|null $strData, string $strDirection = ""): array
    {
        return $this->getSpacingClasses($strData, $strDirection);
    }

    /**
     * Convenience wrapper around {@see self::getSpacingClasses()} for the top direction.
     * Allows {@see self::getAllContainerClasses()} to use the same single-arg spec shape as
     * the other aggregator methods.
     */
    public function getSpacingTopClasses(string|null $strData): array
    {
        return $this->getSpacingClasses($strData, 't');
    }

    /**
     * Convenience wrapper around {@see self::getSpacingClasses()} for the bottom direction.
     */
    public function getSpacingBottomClasses(string|null $strData): array
    {
        return $this->getSpacingClasses($strData, 'b');
    }

    public function getRowClass(): string
    {
        return (new $GLOBALS['responsive']['config']())->strRow ?? '';
    }

    public function getAllResponsiveClasses($varData, array $arrFields = [], string $table = 'tl_content', bool $skipPaletteCheck = false): array
    {
        $arrSpecs = [
            ['cols',       'responsiveCols',       'getColClasses'],
            ['offsets',    'responsiveOffsets',    'getOffsetClasses'],
            ['order',      'responsiveOrder',      'getOrderClasses'],
            ['align-self', 'responsiveAlignSelf',  'getAlignSelfClasses'],
        ];

        $type = self::resolveType($varData);
        $blnSuppressColumns = $this->suppressesColumns($varData, $table, $skipPaletteCheck);

        $arrClasses = [];
        foreach ($arrSpecs as [$strKey, $strDefaultField, $strMethod]) {
            $strField = $arrFields[$strKey] ?? $strDefaultField;
            if (!$this->isFieldInPalette($strField, $type, $table, $skipPaletteCheck)) {
                continue;
            }
            if ($blnSuppressColumns && in_array($strKey, ['cols', 'offsets'], true)) {
                continue;
            }
            $arrClasses = array_merge($arrClasses, $this->$strMethod(self::getProp($varData, $strField), $varData));
        }
        return $arrClasses;
    }

    /**
     * Whether this record's column classes give way to its container.
     *
     * An element that sizes itself as a container must not ALSO carry column and offset
     * classes: the two express competing widths and the container wins. Order and align-self
     * are unaffected - they still apply to a container-mode element.
     *
     * This is the single place that decides it. It used to live in two: an inline
     * `'tl_content' == $table` branch here (keyed on includePalettes.container membership) and
     * a `not this.responsiveContainer` conditional in form_fieldsetStart.html.twig. Same rule,
     * two conditions, two layers - so fixing one silently left the other behind, and the form
     * path additionally re-applied order/align-self OUTSIDE the palette gate (fail-open, in a
     * code path that had otherwise moved to fail-closed).
     *
     * Membership is now derived from the palette itself: a type that offers responsiveContainer
     * is by definition container-capable. That is equivalent to the old includePalettes lookup
     * (element_group is the only tl_content type carrying the field, fieldsetStart the only one
     * in tl_form_field) while working identically for every table, so form templates no longer
     * need a rule of their own.
     *
     * Bundles layering further conditions on column output - contao-bootstrap suppresses columns
     * when responsiveOverwriteRowCols is unset, i.e. when the parent's row-cols should win -
     * override this method rather than the class-generating ones.
     *
     * Public because it is the authoritative definition of "this record's columns do not render":
     * ContainerColumnConflictMigration asks it directly instead of re-deriving container-capability
     * from $GLOBALS['responsive'][...]['includePalettes']['container']. Those two answers agree
     * only for as long as LoadDataContainerListener keeps the field and the config list aligned,
     * so the migration selects exactly the records this method strands rather than a parallel
     * approximation of them.
     */
    public function suppressesColumns($varData, string $table = 'tl_content', bool $skipPaletteCheck = false): bool
    {
        if (!self::getProp($varData, 'responsiveContainer')) {
            return false;
        }

        return $this->isFieldInPalette(
            'responsiveContainer',
            self::resolveType($varData),
            $table,
            $skipPaletteCheck,
        );
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
     * method with $skipPaletteCheck = true so the inner gate is not re-applied (it would
     * otherwise check the wrong field name, `responsiveContainer`, against the row's palette).
     * The `$field` parameter here therefore matters for *direct* external callers (templates /
     * hooks / tests) that source the value from a field other than `responsiveContainer`.
     *
     * @param string|null $strData          Container-size key looked up in $arrContainerSizes.
     * @param string|null $type             Record type for palette gating. Null fails the gate (closed).
     * @param string      $table            DCA table whose palette is consulted.
     * @param string      $field            Name of the DCA field $strData was sourced from.
     * @param bool         $skipPaletteCheck Bypass the palette gate (typeless / already-gated callers).
     */
    public function getContainerClasses($strData, ?string $type = null, string $table = 'tl_content', string $field = 'responsiveContainer', bool $skipPaletteCheck = false): array
    {
        if (!$strData) return [];
        if (!$this->isFieldInPalette($field, $type, $table, $skipPaletteCheck)) {
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
    public function getAllContainerClasses($varData, array $arrFields = [], string $table = 'tl_article', bool $skipPaletteCheck = false): array
    {
        $arrSpecs = [
            ['containerSize', 'responsiveContainerSize', 'getContainerClasses'],
            ['spacingTop',    'responsiveSpacingTop',    'getSpacingTopClasses'],
            ['spacingBottom', 'responsiveSpacingBottom', 'getSpacingBottomClasses'],
        ];

        $type = self::resolveType($varData);

        $arrClasses = [];
        foreach ($arrSpecs as [$strKey, $strDefaultField, $strMethod]) {
            $strField = $arrFields[$strKey] ?? $strDefaultField;
            if (!$this->isFieldInPalette($strField, $type, $table, $skipPaletteCheck)) {
                continue;
            }
            $varValue = self::getProp($varData, $strField);
            // getContainerClasses re-gates on its own field name; this spec was already gated
            // above (on responsiveContainerSize), so skip its inner palette check.
            $arrClasses = array_merge(
                $arrClasses,
                'getContainerClasses' === $strMethod
                    ? $this->getContainerClasses($varValue, $type, $table, $strField, true)
                    : $this->$strMethod($varValue)
            );
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

    /**
     * Returns whether the record offers its children settings behind the addResponsiveChildren
     * selector but has it disabled - in which case none of those settings apply.
     *
     * Only palettes registered as "children" (see LoadDataContainerListener::addChildrenSettings)
     * carry that field: for them the values live in a subpalette and are meaningless while the
     * selector is off. Container palettes carry the same settings unconditionally and have no such
     * field, so they are unaffected.
     *
     * Callers operating on typeless data ($skipPaletteCheck) cannot resolve a palette and have
     * already established their source, so the check does not apply to them.
     */
    protected function hasChildrenSettingsDisabled($varData, string $table, bool $skipPaletteCheck): bool
    {
        if ($skipPaletteCheck || self::getProp($varData, 'addResponsiveChildren')) {
            return false;
        }

        return $this->isFieldInPalette('addResponsiveChildren', self::resolveType($varData), $table);
    }

    public function getAllInnerContainerClasses($varData, array $arrFields = [], string $table = 'tl_content', bool $skipPaletteCheck = false): array
    {
        if ($this->hasChildrenSettingsDisabled($varData, $table, $skipPaletteCheck)) {
            return [];
        }

        $arrSpecs = [
            ['flexDirection',  'responsiveFlexDirection',  'getFlexDirectionClasses'],
            ['flexWrap',       'responsiveFlexWrap',       'getFlexWrapClasses'],
            ['alignItems',     'responsiveAlignItems',     'getAlignItemsClasses'],
            ['alignContent',   'responsiveAlignContent',   'getAlignContentClasses'],
            ['justifyContent', 'responsiveJustifyContent', 'getJustifyContentClasses'],
        ];

        $type = self::resolveType($varData);

        $arrClasses = [];
        foreach ($arrSpecs as [$strKey, $strDefaultField, $strMethod]) {
            $strField = $arrFields[$strKey] ?? $strDefaultField;
            if (!$this->isFieldInPalette($strField, $type, $table, $skipPaletteCheck)) {
                continue;
            }
            $arrClasses = array_merge($arrClasses, $this->$strMethod(self::getProp($varData, $strField), $varData));
        }
        return $arrClasses;
    }

    /**
     * Returns whether the field is part of the resolved palette.
     *
     * Fails closed: a field only renders when it is positively confirmed in the type's palette
     * for the given table. A missing type, or a type that is not a palette in $table (the usual
     * symptom of a wrong table / wrong source being passed), returns false - so such mismatches
     * surface immediately as missing classes instead of silently rendering and lingering.
     *
     * Callers that legitimately operate on typeless data (an article or form body that has no
     * per-record type and renders from its table's "default" palette) opt out via $skipPaletteCheck.
     */
    protected function isFieldInPalette(string $strField, ?string $type, string $table = 'tl_content', bool $skipPaletteCheck = false): bool
    {
        if ($skipPaletteCheck) {
            return true;
        }

        // Both rejections below are indistinguishable, in the rendered output, from "this record
        // simply has no responsive settings" - the element just silently loses its classes. That
        // is precisely how a stale template override goes unnoticed: a pre-1.1 copy of
        // form_fieldsetStart.html.twig still calls getAllResponsiveClasses(arrConfiguration) with
        // no table, so it lands here with a null type against tl_content and drops everything.
        // Announce it instead of failing quietly.
        if ($type === null || $type === '') {
            trigger_deprecation(
                'kiwi/contao-responsive-base',
                '1.1',
                'Resolving responsive field "%s" without a record type (table "%s") is deprecated and '
                .'yields no classes. Pass the record itself (e.g. "this" rather than a widget '
                .'configuration array) together with its table, or opt out via $skipPaletteCheck for '
                .'genuinely typeless data. Check for an outdated project-level template override.',
                $strField,
                $table,
            );

            return false;
        }

        Controller::loadDataContainer($table);

        if (!isset($GLOBALS['TL_DCA'][$table]['palettes'][$type])) {
            trigger_deprecation(
                'kiwi/contao-responsive-base',
                '1.1',
                'Resolving responsive field "%s" for type "%s" against table "%s", which has no such '
                .'palette, is deprecated and yields no classes. This usually means the wrong $table '
                .'was passed - check for an outdated project-level template override.',
                $strField,
                $type,
                $table,
            );

            return false;
        }

        return PaletteManipulatorExtended::create()->hasField($type, $table, $strField);
    }
}
