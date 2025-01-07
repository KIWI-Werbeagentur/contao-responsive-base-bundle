<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Interface;

interface ResponsiveConfigurationInterface
{
    public array $arrBreakpoints {
        get;
        set;
    }

    public array $arrContainerSizes {
        get;
        set;
    }

    public array $arrCols {
        get;
        set;
    }

    public array $arrColsDefaults {
        get;
    }

    public array $arrOffsets {
        get;
        set;
    }

    public array $arrOffsetsDefaults {
        get;
        set;
    }

    public array $arrSpacings {
        get;
        set;
    }

    public array $arrSpacingsDefaults {
        get;
        set;
    }

    public array|string $varOrderClasses {
        get;
    }

    public array|string $varAlignSelfClasses {
        get;
    }

    public array|string $varAlignItemsClasses {
        get;
    }

    public array|string $varAlignContentClasses {
        get;
    }

    public array|string $varJustifyContentClasses {
        get;
    }
}