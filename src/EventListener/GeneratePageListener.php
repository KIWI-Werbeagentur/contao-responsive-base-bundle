<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;

#[AsHook('generatePage')]
class GeneratePageListener
{
    public function __invoke(PageModel $objPage, LayoutModel $objLayout)
    {
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/kiwiresponsivebase/responsive.js|static';
    }
}
