<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveFrontendService;

/**
 * Adds a form field's responsive column classes to its row, which form_row prints as rowClasses.
 *
 * loadFormField fires once per form generator field, after the widget is built from its
 * tl_form_field row and before it is validated and rendered - so the classes are in place for
 * the one regular render. This used to be a parseWidget listener, which only runs after rendering
 * and therefore had to render every widget a second time.
 */
#[AsHook('loadFormField')]
class LoadFormFieldListener
{
    public function __construct(private readonly ResponsiveFrontendService $responsiveFrontendService)
    {
    }

    public function __invoke(Widget $objWidget, string $strForm, array $arrForm, Form $objForm): Widget
    {
        $arrClasses = $this->responsiveFrontendService->getAllResponsiveClasses($objWidget, table: 'tl_form_field');

        if ($arrClasses) {
            $objWidget->rowClasses = trim(($objWidget->rowClasses ?? '') . ' ' . implode(' ', $arrClasses));
        }

        return $objWidget;
    }
}
