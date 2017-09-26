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

class Paypal extends BaseFeature {

    public static function loadRoutes( Klein $klein ) {
        $klein->respond( 'GET', '/preview',              [__CLASS__, 'previewRoute'] );
        $klein->respond( 'GET', '/reference-files/[:id_project]/[:password]/[:file_name_in_zip]', [ __CLASS__, 'referenceImagesGet' ] );
    }

    public static function previewRoute($request, $response, $service, $app) {
        $controller    = new PreviewController( $request, $response, $service, $app);
        $template_path = dirname( __FILE__ ) . '/Paypal/View/Html/preview.html';
        $controller->setView( $template_path );
        $controller->respond();
    }

    public static function referenceImagesGet( $request, $response, $service, $app ) {
        $controller    = new Paypal\Controller\API\ReferenceFilesController( $request, $response, $service, $app );
        $controller->flushStream();
    }

    /**
     * Ignore all glossaries. Temporary hack to avoid something unknown on MyMemory side.
     * We simply change the array_files key to avoid any glossary to be sent to MyMemory.
     *
     * TODO: glossary detection based on extension is brittle.
     *
     */
    public function filter_project_manager_array_files( $files, $projectStructure ) {
        $new_files = array() ;
        foreach ( $files as $file ) {
            if ( \FilesStorage::pathinfo_fix( $file, PATHINFO_EXTENSION ) != 'g' ) {
                $new_files[] = $file ;
            }
        }

        return $new_files   ;
    }

}