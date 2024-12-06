<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Configuration;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Kiwi\Contao\ResponsiveBaseBundle\Interface\ResponsiveConfigurationInterface;

abstract class ResponsiveConfiguration implements ResponsiveConfigurationInterface
{
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

    #[AsCallback(table: 'tl_article', target: 'config.onload')]
    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    #[AsCallback(table: 'tl_form', target: 'config.onload')]
    #[AsCallback(table: 'tl_form_field', target: 'config.onload')]
    public function getDefaults(DataContainer $objDca): void
    {
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveCols']['default'] = (new $GLOBALS['responsive']['config'])->arrColsDefaults;
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveOffsets']['default'] = (new $GLOBALS['responsive']['config'])->arrOffsetsDefaults;
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveSpacingTop']['default'] = ((new $GLOBALS['responsive']['config'])->arrSpacingTopDefaults) ?? null;
        $GLOBALS['TL_DCA'][$objDca->table]['fields']['responsiveSpacingBottom']['default'] = ((new $GLOBALS['responsive']['config'])->arrSpacingBottomDefaults) ?? null;
    }

    #[AsCallback(table: 'tl_article', target: 'fields.responsiveContainerSize.options')]
    #[AsCallback(table: 'tl_content', target: 'fields.responsiveContainer.options')]
    public function getContainerSizes(): array
    {
        return $this->arrContainerSizes;
    }

    #[AsCallback(table: 'tl_content', target: 'config.onload')]
    public function addToPalettes(DataContainer $objDca):void
    {
        foreach ($GLOBALS['TL_DCA'][$objDca->table]['palettes'] as $strPalette => $varFields) {
            if(!is_string($varFields) || in_array($strPalette, ($GLOBALS['responsive'][$objDca->table]['excludePalettes'] ?? []))) continue;

            PaletteManipulator::create()
                ->addLegend('layout_legend', ['protected_legend','expert_legend'],PaletteManipulator::POSITION_BEFORE)
                ->addField('responsiveCols,responsiveOffsets,responsiveOrder,responsiveAlignSelf', 'layout_legend', PaletteManipulator::POSITION_APPEND)
                ->applyToPalette($strPalette, $objDca->table);
        }
    }
}