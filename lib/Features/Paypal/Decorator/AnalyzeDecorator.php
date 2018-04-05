<?php

namespace Features\Paypal\Decorator;

use AbstractDecorator;
use Features\Paypal\Utils\Routes;

class AnalyzeDecorator extends AbstractDecorator {
    /**
     * @var \PHPTALWithAppend
     */
    protected $template;

    public function decorate() {
        $this->template->append( 'footer_js', Routes::staticSrc( 'build/paypal-analyze-build.js' ) );
        $this->template->append( 'css_resources', Routes::staticSrc( 'build/paypal-manage-build.css' ) );

        $this->template->split_enabled    = false;
        $this->template->enable_outsource = false;
    }


}