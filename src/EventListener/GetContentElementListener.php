<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;

/**
 * Applies a content element's responsive column classes to LEGACY content elements that render
 * their own template (e.g. forms, which are a Hybrid) and therefore bypass the
 * content_element/_base.html.twig override that styles modern fragment elements.
 *
 * This restores the column classes for a form included via a content element: the columns come
 * from the content element record, the gating (bootstrap's responsiveOverwriteRowCols) is enforced
 * inside the frontend service. The form-via-module case is handled by GetFrontendModuleListener.
 *
 * Fragment content elements render through a proxy with no Template and are skipped here - the twig
 * base override handles those, so classes are never applied twice.
 */
#[AsHook('getContentElement')]
class GetContentElementListener
{
    public function __construct(protected ResponsiveFrontendService $responsiveFrontendService)
    {
    }

    public function __invoke(ContentModel $objContentModel, string $strBuffer, object $objElement): string
    {
        if (!$objElement->Template) {
            return $strBuffer;
        }

        $arrClasses = $this->responsiveFrontendService->getAllResponsiveClasses($objContentModel->row(), [], 'tl_content');

        if ($arrClasses) {
            $objElement->Template->isResponsive = true;
            $objElement->Template->class = trim(($objElement->Template->class ?? '') . ' ' . implode(' ', $arrClasses));
            $strBuffer = $objElement->Template->parse();
        }

        return $strBuffer;
    }
}
