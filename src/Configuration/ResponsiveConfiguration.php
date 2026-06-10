<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Configuration;

use Contao\DataContainer;
use Kiwi\Contao\ResponsiveBaseBundle\Interface\ResponsiveConfigurationInterface;

abstract class ResponsiveConfiguration
{
    public const SPACING_NO_OP = 'noop';

    protected array|string $varColClasses = [];

    protected array|string $varOffsetClasses = [];

    protected array|string $varAlignItemsClasses = [];

    protected array|string $varAlignSelfClasses = [];

    protected array|string $varAlignContentClasses = [];

    protected array|string $varJustifyContentClasses = [];

    protected array|string $varSpacingClasses = [];

    /** @var array<string, int|string> */
    protected array $arrElementGroupSpacingTopDefaults = [];

    /** @var array<string, int|string> */
    protected array $arrElementGroupSpacingBottomDefaults = [];

    protected $arrAlignmentItems;

    protected $arrAlignmentContent;

    protected $arrJustifyContent;

    protected $arrFlexWrap;

    protected $arrFlexDirections;

    protected $arrOrderDefaults;

    protected $arrIcons;

    public function __construct(private $objDca = null){
        $this->arrAlignmentContent = [
            'normal' => 'normal',
            'start' => 'start',
            'center' => 'center',
            'end' => 'end',
            'space-between' => 'space-between',
            'space-around' => 'space-around',
            'space-evenly' => 'space-evenly'
        ];

        $this->arrAlignmentItems = [
            'auto' => 'auto',
            'stretch' => 'stretch',
            'baseline' => 'baseline',
            'start' => 'start',
            'center' => 'center',
            'end' => 'end'
        ];

        $this->arrFlexDirections = ['row' => 'row', 'column' => 'column', 'row-reverse' => 'row-reverse', 'column-reverse' => 'column-reverse'];

        $this->arrFlexWrap = ['wrap' => 'wrap', 'nowrap' => 'nowrap', 'wrap-reverse' => 'wrap-reverse'];

        $this->arrOrderDefaults = ['xs' => 0];

        $this->arrIcons = [
            'flexDirection' =>
                [
                    'row' => "/bundles/kiwiresponsivebase/icons/flex-direction/flex-direction-row.svg",
                    'column' => "/bundles/kiwiresponsivebase/icons/flex-direction/flex-direction-column.svg",
                    'row-reverse' => "/bundles/kiwiresponsivebase/icons/flex-direction/flex-direction-row-reverse.svg",
                    'column-reverse' => "/bundles/kiwiresponsivebase/icons/flex-direction/flex-direction-column-reverse.svg"
                ],

            'flexItems' =>
                [
                    'auto' => "/bundles/kiwiresponsivebase/icons/flex-items/flex-items-stretch.svg",
                    'stretch' => "/bundles/kiwiresponsivebase/icons/flex-items/flex-items-stretch.svg",
                    'start' => "/bundles/kiwiresponsivebase/icons/flex-items/flex-items-start.svg",
                    'center' => "/bundles/kiwiresponsivebase/icons/flex-items/flex-items-center.svg",
                    'end' => "/bundles/kiwiresponsivebase/icons/flex-items/flex-items-end.svg",
                    'baseline' => "/bundles/kiwiresponsivebase/icons/flex-items/flex-items-baseline.svg",
                ],

            'alignContent' =>
                [
                    'normal' => "/bundles/kiwiresponsivebase/icons/align-content/flex-content-start.svg",
                    'start' => "/bundles/kiwiresponsivebase/icons/align-content/flex-content-start.svg",
                    'center' => "/bundles/kiwiresponsivebase/icons/align-content/flex-content-center.svg",
                    'end' => "/bundles/kiwiresponsivebase/icons/align-content/flex-content-end.svg",
                    'space-around' => "/bundles/kiwiresponsivebase/icons/align-content/flex-content-space-around.svg",
                    'space-evenly' => "/bundles/kiwiresponsivebase/icons/align-content/flex-content-space-evenly.svg",
                    'space-between' => "/bundles/kiwiresponsivebase/icons/align-content/flex-content-space-between.svg",
                ],

            'justifyContent' =>
                [
                    'normal' => "/bundles/kiwiresponsivebase/icons/justify-content/flex-content-start.svg",
                    'start' => "/bundles/kiwiresponsivebase/icons/justify-content/flex-content-start.svg",
                    'center' => "/bundles/kiwiresponsivebase/icons/justify-content/flex-content-center.svg",
                    'end' => "/bundles/kiwiresponsivebase/icons/justify-content/flex-content-end.svg",
                    'space-around' => "/bundles/kiwiresponsivebase/icons/justify-content/flex-content-space-around.svg",
                    'space-evenly' => "/bundles/kiwiresponsivebase/icons/justify-content/flex-content-space-evenly.svg",
                    'space-between' => "/bundles/kiwiresponsivebase/icons/justify-content/flex-content-space-between.svg",
                ],

            'flexWrap' =>
                [
                    'wrap' => "/bundles/kiwiresponsivebase/icons/flex-wrap/flex-wrap-wrap.svg",
                    'nowrap' => "/bundles/kiwiresponsivebase/icons/flex-wrap/flex-wrap-nowrap.svg",
                    'wrap-reverse' => "/bundles/kiwiresponsivebase/icons/flex-wrap/flex-wrap-wrap-reverse.svg",
                ]
        ];

        return $this;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'varColClasses' => $this->arrCols,
            'varOffsetClasses' => $this->arrOffsets,
            'varAlignItemsClasses', 'varAlignSelfClasses' => $this->arrAlignmentItems,
            'varAlignContentClasses', 'varJustifyContentClasses' => $this->arrAlignmentContent,
            'varSpacingClasses' => $this->arrSpacings,
            default => $this->{$name},
        };
    }

    public function getBreakpoints(): array
    {
        return array_keys($this->arrBreakpoints);
    }

    public function getCols(): array
    {
        return array_keys($this->arrCols);
    }

    public function getOffsets(): array
    {
        return array_keys($this->arrOffsets);
    }

    public function getSpacings(): array
    {
        return array_keys($this->arrSpacings);
    }

    /**
     * Returns the spacing option keys with {@see self::SPACING_NO_OP} removed.
     * Useful for consumers that need to enumerate the visible/effective tokens
     * (e.g. SCSS regeneration), without knowing the concrete noop literal.
     *
     * @return list<string>
     */
    public function getSpacingsExcludingNoOp(): array
    {
        return array_values(array_filter(
            $this->getSpacings(),
            static fn ($key): bool => (string) $key !== self::SPACING_NO_OP
        ));
    }

    public function getFlexDirections(): array
    {
        return array_keys($this->arrFlexDirections);
    }

    public function getJustifyContent(): array
    {
        return array_keys($this->arrAlignmentContent);
    }

    public function getAlignContent(): array
    {
        return array_keys($this->arrAlignmentContent);
    }

    public function getAlignItems(): array
    {
        return array_keys($this->arrAlignmentItems);
    }

    public function getAlignSelf(): array
    {
        return array_keys($this->arrAlignmentItems);
    }

    public function getFlexWrap(): array
    {
        return array_keys($this->arrFlexWrap);
    }

    public function getIcons($strField): array
    {
        return $this->arrIcons[$strField] ?? [];
    }

    /**
     * DCA `onload_callback` entry point. Writes initial default values into
     * every responsive field this bundle knows about for the table currently
     * being loaded. Each family of fields is handled by a dedicated
     * {@see self::apply*Defaults()} helper so subclasses can override individual
     * groups surgically.
     */
    public function getDefaults(DataContainer $objDca): void
    {
        $this->applyContainerSizeDefaults($objDca);
        $this->applyColumnDefaults($objDca);
        $this->applySpacingDefaults($objDca);
        $this->applyElementGroupSpacingDefaults($objDca);
        $this->applyOrderDefaults($objDca);
    }

    protected function applyContainerSizeDefaults(DataContainer $dc): void
    {
        $fields = &$GLOBALS['TL_DCA'][$dc->table]['fields'];
        if (isset($fields['responsiveContainerSize'])) {
            $fields['responsiveContainerSize']['default'] = $dc->table === 'tl_layout'
                ? ($this->strContainerDefaultLayout ?? '')
                : ($this->strContainerDefault ?? '');
        }
        if (isset($fields['responsiveContainerSizeHeader'])) {
            $fields['responsiveContainerSizeHeader']['default'] = $this->strContainerDefaultLayout ?? '';
        }
        if (isset($fields['responsiveContainerSizeFooter'])) {
            $fields['responsiveContainerSizeFooter']['default'] = $this->strContainerDefaultLayout ?? '';
        }
    }

    protected function applyColumnDefaults(DataContainer $dc): void
    {
        $fields = &$GLOBALS['TL_DCA'][$dc->table]['fields'];
        if (isset($fields['responsiveCols'])) {
            $fields['responsiveCols']['default'] = $this->arrColsDefaults;
        }
        if (isset($fields['responsiveOffsets'])) {
            $fields['responsiveOffsets']['default'] = $this->arrOffsetsDefaults;
        }
    }

    protected function applySpacingDefaults(DataContainer $dc): void
    {
        $fields = &$GLOBALS['TL_DCA'][$dc->table]['fields'];
        if (isset($fields['responsiveSpacingTop'])) {
            $fields['responsiveSpacingTop']['default'] = $this->arrSpacingTopDefaults ?? null;
        }
        if (isset($fields['responsiveSpacingBottom'])) {
            $fields['responsiveSpacingBottom']['default'] = $this->arrSpacingBottomDefaults ?? null;
        }
    }

    protected function applyElementGroupSpacingDefaults(DataContainer $dc): void
    {
        $fields = &$GLOBALS['TL_DCA'][$dc->table]['fields'];
        if (isset($fields['responsiveGroupSpacingTop'])) {
            $fields['responsiveGroupSpacingTop']['default'] = $this->arrElementGroupSpacingTopDefaults ?? null;
        }
        if (isset($fields['responsiveGroupSpacingBottom'])) {
            $fields['responsiveGroupSpacingBottom']['default'] = $this->arrElementGroupSpacingBottomDefaults ?? null;
        }
    }

    protected function applyOrderDefaults(DataContainer $dc): void
    {
        $fields = &$GLOBALS['TL_DCA'][$dc->table]['fields'];
        if (isset($fields['responsiveOrder'])) {
            $fields['responsiveOrder']['default'] = $this->arrOrderDefaults ?? null;
        }
    }

    public function getContainerSizes(): array
    {
        return $this->arrContainerSizes;
    }
}
