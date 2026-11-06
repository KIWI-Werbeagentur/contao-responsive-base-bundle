<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ResponseContext\Csp\CspHandler;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\LayoutModel;
use Contao\PageModel;
use Kiwi\Contao\ResponsiveBaseBundle\Service\ResponsiveBreakpoints;
use Twig\Environment;

/**
 * Adds the frontend assets of the responsive base: the --responsive-* custom properties, the
 * KiwiResponsive breakpoint helper and the deprecated responsive.js.
 */
#[AsHook('generatePage')]
class ResponsiveAssetsListener
{
    public function __construct(
        private readonly ResponsiveBreakpoints $responsiveBreakpoints,
        private readonly ResponseContextAccessor $responseContextAccessor,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(PageModel $objPage, LayoutModel $objLayout): void
    {
        // The --responsive-* custom properties via CSS, so they are available on first paint
        // (responsive.js only set them after the page had loaded, which made layouts jump).
        $GLOBALS['TL_HEAD']['kiwi_responsive_properties'] = \sprintf(
            '<style%s>%s</style>',
            $this->getNonceAttribute('style-src'),
            $this->responsiveBreakpoints->getCustomPropertiesCss(),
        );

        // The KiwiResponsive breakpoint helper (and the deprecated window.arrBreakpoints). Inline in
        // the <head>, so it is available synchronously to every later script, and without the
        // uncacheable PHP request the deprecated /responsive/breakpoints.js route caused.
        $GLOBALS['TL_HEAD']['kiwi_responsive_helper'] = \sprintf(
            '<script%s>%s</script>',
            $this->getNonceAttribute('script-src'),
            $this->twig->render('@KiwiResponsiveBase/kiwi_responsive.js.twig', [
                'breakpoints' => $this->responsiveBreakpoints->getBreakpointsJson(),
            ]),
        );

        // Deprecated, to be removed in 2.0 (use KiwiResponsive instead)
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/kiwiresponsivebase/responsive.js|static|defer';
    }

    private function getNonceAttribute(string $strDirective): string
    {
        $responseContext = $this->responseContextAccessor->getResponseContext();

        if (!$responseContext?->has(CspHandler::class)) {
            return '';
        }

        $strNonce = $responseContext->get(CspHandler::class)->getNonce($strDirective);

        return $strNonce ? ' nonce="'.$strNonce.'"' : '';
    }
}
