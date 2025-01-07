<?php

namespace Kiwi\Contao\ResponsiveBaseBundle\Controller\Form;

use Contao\Form;
use Contao\System;

class FormResponsive extends Form
{
    protected $cte = null;

    public function __construct($objElement, $strColumn='main')
    {
        parent::__construct($objElement, $strColumn);

        $this->cte = $objElement;
    }

    protected function compile()
    {
        parent::compile();
        $this->Template->cte = $this->cte;
        $this->Template->innerClass = System::getContainer()->get('kiwi.contao.responsive.frontend')->getAllInnerContainerClasses($this->cte->row());
    }
}