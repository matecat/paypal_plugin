<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 3/18/16
 * Time: 4:56 PM
 */

namespace Features\Paypal\Decorator;

use AbstractModelViewDecorator;
use Bootstrap;
use Features\Paypal\Utils\Routes;
use INIT;

//use Features\Paypal\Utils\Routes ;


class PreviewDecorator extends AbstractModelViewDecorator {

    public function decorate( $template ) {

        $template->basepath     = INIT::$BASEURL;
        $template->build_number = INIT::$BUILD_NUMBER;

        $template->id_job   = $this->model->id;
        $template->password = $this->model->password;

        $template->append('footer_js', Routes::staticSrc('build/paypal-components-build.js') );
        $template->append('footer_js', Routes::staticSrc('build/paypal-preview-build.js') );
        $template->append('css_resources', Routes::staticSrc('build/paypal-build.css') );

        $template->googleDriveEnabled = Bootstrap::isGDriveConfigured() ;

    }

}