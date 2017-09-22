<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 1/29/16
 * Time: 1:08 PM
 */

namespace Features;

use Features\Paypal\Controller\PreviewController;
use Klein\Klein;

//use Features\Paypal\Utils\Metadata;
//use Features\Paypal\Utils\Routes as Routes ;

//use Features\Paypal\Utils\SkippedSegments;

class Paypal extends BaseFeature {

    public static function loadRoutes( Klein $klein ) {
        $klein->respond( 'GET', '/preview',              [__CLASS__, 'previewRoute'] );
    }

    public static function previewRoute($request, $response, $service, $app) {
        $controller    = new PreviewController( $request, $response, $service, $app);
        $template_path = dirname( __FILE__ ) . '/Paypal/View/Html/preview.html';
        $controller->setView( $template_path );
        $controller->respond();
    }

}