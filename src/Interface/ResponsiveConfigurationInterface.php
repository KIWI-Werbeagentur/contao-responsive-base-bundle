<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Configuration;

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
        set;
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
}