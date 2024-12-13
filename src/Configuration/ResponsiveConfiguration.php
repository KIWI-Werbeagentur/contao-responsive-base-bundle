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
                    'row' => "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' height='24' width='24' id='Align-Stretch--Streamline-Material-Pro'%3E%3Cg id='Align-Stretch--Streamline-Material-Pro'%3E%3Cpath id='align-stretch_2' fill='%23000' d='M7 10V4H2V2H22V4H17V10H7ZM2 22V20H7V14H17V20H22V22H2Z' stroke-width='1'%3E%3C/path%3E%3C/g%3E%3C/svg%3E",
                    'column' => "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' height='24' width='24' id='Align-Stretch--Streamline-Material-Pro'%3E%3Cg id='Align-Stretch--Streamline-Material-Pro'%3E%3Cpath id='align-stretch_2' fill='%23000' d='M7 10V4H2V2H22V4H17V10H7ZM2 22V20H7V14H17V20H22V22H2Z' stroke-width='1'%3E%3C/path%3E%3C/g%3E%3C/svg%3E",
                    'row-reverse' => "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' height='24' width='24' id='Align-Stretch--Streamline-Material-Pro'%3E%3Cg id='Align-Stretch--Streamline-Material-Pro'%3E%3Cpath id='align-stretch_2' fill='%23000' d='M7 10V4H2V2H22V4H17V10H7ZM2 22V20H7V14H17V20H22V22H2Z' stroke-width='1'%3E%3C/path%3E%3C/g%3E%3C/svg%3E",
                    'column-reverse' => "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' height='24' width='24' id='Align-Stretch--Streamline-Material-Pro'%3E%3Cg id='Align-Stretch--Streamline-Material-Pro'%3E%3Cpath id='align-stretch_2' fill='%23000' d='M7 10V4H2V2H22V4H17V10H7ZM2 22V20H7V14H17V20H22V22H2Z' stroke-width='1'%3E%3C/path%3E%3C/g%3E%3C/svg%3E"
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