<?php

namespace Features\Paypal\Decorator;


use AbstractDecorator;
use Features\Paypal\Utils\Routes;

class CatDecorator extends AbstractDecorator
{
    /**
     * @var \PHPTALWithAppend
     */
    protected $template ;

    public function decorate() {
        $this->template->append('footer_js', Routes::staticSrc('build/paypal-components-build.js') );
        $this->template->append('footer_js', Routes::staticSrc('build/paypal-core-build.js') );
        $this->template->append('css_resources', Routes::staticSrc('build/paypal-core-build.css') );
        $this->template->splitSegmentEnabled = var_export(false, true);
        $this->template->allow_link_to_analysis = false ;
    }
}