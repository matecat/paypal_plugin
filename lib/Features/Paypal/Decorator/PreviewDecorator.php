<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 3/18/16
 * Time: 4:56 PM
 */

namespace Features\Paypal\Decorator;

use AbstractModelViewDecorator;
use Features\Paypal\Utils\Routes;

//use Features\Paypal\Utils\Routes ;


class PreviewDecorator extends AbstractModelViewDecorator {

    public function decorate( $template ) {
        $template->append('footer_js', Routes::staticSrc('js/paypal.core.js') );
    }

}