<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Configuration;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Kiwi\Contao\ResponsiveBaseBundle\Interface\ResponsiveConfigurationInterface;

abstract class ResponsiveConfiguration implements ResponsiveConfigurationInterface
{
    public array|string $varColClasses {
        get => $this->arrCols;
    }

    public array|string $varOffsetClasses {
        get => $this->arrOffsets;
    }

    public array|string $varAlignItemsClasses {
        get => $this->arrAlignmentItems;
    }

    public array|string $varAlignSelfClasses {
        get => $this->arrAlignmentItems;
    }

    public array|string $varAlignContentClasses {
        get => $this->arrAlignmentContent;
    }

    public array|string $varJustifyContentClasses {
        get => $this->arrAlignmentContent;
    }

    public array|string $varSpacingClasses {
        get => $this->arrSpacings;
    }

    protected $arrAlignmentItems;

    protected $arrAlignmentContent;

    protected $arrJustifyContent;

    protected $arrFlexWrap;

    protected $arrFlexDirections;

    protected $arrOrderDefaults;

    protected $arrIcons;

    public function __construct(){
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
    }

    public function getBreakpoints(): array
    {
        return array_keys($this->arrBreakpoints);
    }

    #[AsCallback('tl_content', 'fields.responsiveCols.options')]
    public function getCols(): array
    {
        return array_keys($this->arrCols);
    }

    #[AsCallback('tl_content', 'fields.responsiveOffsets.options')]
    public function getOffsets(): array
    {
        return array_keys($this->arrOffsets);
    }

    #[AsCallback('tl_article', 'fields.responsiveSpacingTop.options')]
    #[AsCallback('tl_article', 'fields.responsiveSpacingBottom.options')]
    public function getSpacings(): array
    {
        return array_keys($this->arrSpacings);
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

    #[AsCallback(table: 'tl_module', target: 'config.onload')]
    #[AsCallback(table: 'tl_layout', target: 'config.onload')]
    #[AsCallback(table: 'tl_article', target: 'config.onload')]
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    #[AsCallback(table: 'tl_form', target: 'config.onload')]
    #[AsCallback(table: 'tl_form_field', target: 'config.onload')]
    public function getDefaults(DataContainer $objDca): void
    {
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveContainerSize']['default'] = (new $GLOBALS['responsive']['config'])->strContainerDefault ?? '';
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveHeaderContainerSize']['default'] = (new $GLOBALS['responsive']['config'])->strContainerDefault ?? '';
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveFooterContainerSize']['default'] = (new $GLOBALS['responsive']['config'])->strContainerDefault ?? '';

        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveCols']['default'] = (new $GLOBALS['responsive']['config'])->arrColsDefaults;
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveOffsets']['default'] = (new $GLOBALS['responsive']['config'])->arrOffsetsDefaults;
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveSpacingTop']['default'] = ((new $GLOBALS['responsive']['config'])->arrSpacingTopDefaults) ?? null;
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveSpacingBottom']['default'] = ((new $GLOBALS['responsive']['config'])->arrSpacingBottomDefaults) ?? null;
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveOrder']['default'] = ((new $GLOBALS['responsive']['config'])->arrOrderDefaults) ?? null;
    }

    #[AsCallback(table: 'tl_layout', target: 'fields.responsiveHeaderContainerSize.options')]
    #[AsCallback(table: 'tl_layout', target: 'fields.responsiveFooterContainerSize.options')]
    #[AsCallback(table: 'tl_article', target: 'fields.responsiveContainerSize.options')]
    #[AsCallback(table: 'tl_content', target: 'fields.responsiveContainer.options')]
    public function getContainerSizes(): array
    {
        return $this->arrContainerSizes;
    }
}