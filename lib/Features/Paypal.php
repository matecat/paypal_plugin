<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 1/29/16
 * Time: 1:08 PM
 */

namespace Features;

use API\V2\Exceptions\AuthenticationError;
use BasicFeatureStruct;
use Constants_TranslationStatus;
use Features;
use CustomErrorPage;
use Features\Paypal\Controller\API\Validators\TranslatorsWhitelistAccessValidator;
use Features\Paypal\Controller\PreviewController;
//use Features\Paypal\Controller\API\WhitelistController;
use Features\Paypal\Utils\CDataHandler;
use Klein\Klein;
use viewController;

class Paypal extends BaseFeature {

    const FEATURE_CODE = 'paypal';

    /**
     * @var CDataHandler
     */
    protected $jsonHandler;

    public static $dependencies = [
            Features::PROJECT_COMPLETION,
            Features::TRANSLATION_VERSIONS,
            Features::REVIEW_EXTENDED
    ] ;

    public function __construct( BasicFeatureStruct $feature ) {
        parent::__construct( $feature );
        $this->jsonHandler = new CDataHandler();
    }

    public static function loadRoutes( Klein $klein ) {
        $klein->respond( 'GET', '/preview',              [__CLASS__, 'previewRoute'] );
        route( '/preview/[:id_project]/[:password]/[:file_name_in_zip]', 'GET', 'Features\Paypal\Controller\API\ReferenceFilesController', 'flushStream' );
        route( '/preview/[:id_job]/[:password]', 'GET', 'Features\Paypal\Controller\API\PreviewsStruct', 'getPreviewsStruct'  );
        route( '/reference-files/[:id_job]/[:password]', 'GET', 'Features\Paypal\Controller\API\ReferenceFilesController', 'getReferenceFolder' );
        route( '/reference-files/[:id_job]/[:password]/list', 'GET', 'Features\Paypal\Controller\API\ReferenceFilesController', 'getReferenceFolderList' );
        route( '/projects/[:id_project]/[:password]/whitelist', 'POST', 'Features\Paypal\Controller\API\WhitelistController', 'create' );
        route( '/projects/[:id_project]/[:password]/whitelist', 'DELETE', 'Features\Paypal\Controller\API\WhitelistController', 'delete' );
    }

    public static function previewRoute($request, $response, $service, $app) {
        $controller    = new PreviewController( $request, $response, $service, $app);
        $template_path = dirname( __FILE__ ) . '/Paypal/View/Html/preview.html';
        $controller->setView( $template_path );
        $controller->respond();
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

    public function handleJsonNotes( $projectStructure ){
        $this->jsonHandler->formatJson( $projectStructure );
    }

    /**
     * Tell to the controller to extract also json content of segment_notes table
     * @return bool
     */
    public function prepareAllNotes(){
        return true;
    }

    /**
     * @param $jsonStringNotes string
     *
     * @return mixed
     */
    public function processExtractedJsonNotes( $jsonStringNotes ){
        return $this->jsonHandler->parseJsonNotes( $jsonStringNotes );
    }

    public function processJobsCreated( $projectStructure ){
        $this->jsonHandler->storePreviewsMetadata( $projectStructure );
    }

    /**
     * Override the instance decision to convert or not the normal xlf/xliff files
     *
     * @param $forceXliff
     *
     * @return bool
     */
    public function forceXLIFFConversion( $forceXliff ){
        return false;
    }

    public function skipTagLessFeature( $boolean ){
        return true;
    }

    /**
     * Remove unwanted options from the UI and add additional filters
     *
     * @param $filter_args
     *
     * @return mixed
     */
    public function filterNewProjectInputFilters( $filter_args ){
        unset( $filter_args[ 'tag_projection' ] );  //disable Guess Tag Position Feature
        $filter_args[ 'project_type' ] = [ 'filter' => FILTER_CALLBACK, 'options' => [ __CLASS__, 'sanitizeProjectTypeValue' ] ];
        return $filter_args;
    }

    /**
     * Callback for filters
     *
     * @param $fieldVal string
     *
     * @return string The sanitized field
     */
    public static function sanitizeProjectTypeValue( $fieldVal ) {

        $fieldVal = strtoupper( trim( filter_var( $fieldVal, FILTER_SANITIZE_STRING, [ 'flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH ] ) ) );

        $accepted_values = [ 'TR', 'LR', 'LQA' ];

        //if $fieldVal is not one of the accepted values, force it to "none"
        if ( !in_array( $fieldVal, $accepted_values ) ) {
            throw new \InvalidArgumentException( "Invalid project type $fieldVal", 400 );
        }

        return $fieldVal;

    }

    /**
     * Add options to project metadata
     *
     * @param $metadata
     * @param $__postInput
     *
     * @return mixed
     */
    public function filterProjectMetadata( $metadata, $__postInput ){
        $metadata[ 'project_type' ] = $__postInput[ 'project_type' ];
        return $metadata;
    }

    /**
     * Force a required authentication
     *
     * viewControllers validation access
     *
     * @param viewController $controller
     * @param                $params
     */
    public function beginDoAction( viewController $controller, $params ) {

        if( method_exists( $controller, 'setLoginRequired' ) ){
            /**
             * @var $controller viewController
             */
            $controller->setLoginRequired( true ) ;
            //$controller->checkLoginRequiredAndRedirect();
        }

        try {
            //to use a validator whitelist here
            ( new TranslatorsWhitelistAccessValidator( $controller ) )->validate();
        } catch( AuthenticationError $e ){

            $controllerInstance = new CustomErrorPage();
            $template = new \PHPTALWithAppend( dirname( __FILE__ ) . '/Paypal/View/Html/NotAllowed.html' );
            $controllerInstance->setTemplate( $template );
            $controllerInstance->setCode( 401 );
            $controllerInstance->doAction();
            \Log::doLog( "TODO Implements Authentication exception behaviour" );
            trigger_error( "TODO Implements Authentication exception behaviour", E_USER_WARNING );
            die();

        }

    }

    /**
     * Disable the TM ICES
     *
     * @param $tm_data
     * @param $queueElementParams
     *
     * @return mixed
     */
    public function checkIceLocked( $tm_data, $queueElementParams ){
        $tm_data[ 'status' ] = Constants_TranslationStatus::STATUS_NEW;
        $tm_data[ 'locked' ] = false;
        return $tm_data;
    }

    /**
     * Rewrite 101% to 100% from matches retrieved by getContributionController
     *
     * @param $match
     *
     * @return mixed
     */
    public function iceMatchRewriteForContribution( $match ){
        if( $match[ 'match' ] == '101%' ){
            $match[ 'match' ] = '100%';
        }
        return $match;
    }

    /**
     * @param $iceLockArray array
     *
     * <code>
     *  [
     *      'approved'      => $translation_row [ 4 ],
     *      'locked'        => 0,
     *      'match_type'    => 'ICE',
     *      'eq_word_count' => 0,
     *      'status'        => $status
     * ]
     * </code>
     *
     * @return array $iceLockArray
     */
    public function setICESLockFromXliffValues( $iceLockArray ){

        if( $iceLockArray[ 'approved' ] ){
            $iceLockArray[ 'locked' ] = 1;
            $iceLockArray[ 'status' ] = Constants_TranslationStatus::STATUS_APPROVED;
        }
        return $iceLockArray;

    }

}