<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\System;

#[AsHook('alterSchemesScss')]
class AlterSchemesScss
{
    public function __invoke($objTemplate)
    {
        return $objTemplate .  System::getContainer()->get('twig')->render('@KiwiResponsiveBase/schemes.scss.twig', [
                'breakpoints' => (new $GLOBALS['responsive']['config']())->arrBreakpoints
            ]);
    }
}
