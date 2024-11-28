<?php

namespace Kiwi\Contao\ResponsiveBase;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class KiwiResponsiveBaseBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
