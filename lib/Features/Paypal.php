<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 1/29/16
 * Time: 1:08 PM
 */

namespace Features;

use API\V2\Exceptions\AuthenticationError;
use Features\Paypal\Utils\Routes;
use Features\Paypal\View\API\JSON\ProjectUrlsDecorator;
use API\V2\Json\ProjectUrls;
use BasicFeatureStruct;
use Constants_TranslationStatus;
use Features;
use CustomPage;
use Features\Paypal\Controller\API\Validators\TranslatorsWhitelistAccessValidator;
use Features\Paypal\Controller\PreviewController;
use Features\Paypal\Controller\LqaController;
use Features\Paypal\Utils\CDataHandler;
use FilesStorage;
use Klein\Klein;
use LQA\ChunkReviewDao;
use viewController;
use Projects_MetadataDao;
use API\V2\Json\SegmentComment;

class Paypal extends BaseFeature {

    const FEATURE_CODE = 'paypal';


    /**
     * @var CDataHandler
     */
    protected $jsonHandler;

    protected $project_types = [ 'TR', 'LR', 'LQA' ];

    public static $dependencies = [
            Features::PROJECT_COMPLETION,
            Features::TRANSLATION_VERSIONS,
            Features::REVIEW_EXTENDED
    ] ;

    public function __construct( BasicFeatureStruct $feature ) {
        parent::__construct( $feature );
        $this->jsonHandler = new CDataHandler();
    }

    /**
     * Called from API page to overload swagger api definitions
     *
     * @param array $jsIncludes
     *
     * @return array
     */
    public static function overloadAPIDocs( array $jsIncludes ){
        $jsIncludes[] = '<script src="' . Routes::staticSrc( 'src/js/swagger.js' ) . '" type="text/javascript" ></script>';
        return $jsIncludes;
    }

    /**
     * @param Klein $klein
     */
    public static function loadRoutes( Klein $klein ) {

        //TODO Refactor
        //$klein->respond( 'GET', '/lqa/[:id_job]/[:password]', [__CLASS__, 'lqaRoute'] );
        $klein->respond( 'GET', '/preview/template/[:id_job]/[:password]',              [__CLASS__, 'previewRoute'] );

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
        $controller->respond( 'composeView' );
    }

    public static function projectUrls( ProjectUrls $formatted ) {
        $projectUrlsDecorator = new ProjectUrlsDecorator( $formatted );

        return $projectUrlsDecorator;
    }

    //TODO Refactory
//    public static function lqaRoute($request, $response, $service, $app) {
//        $controller    = new LqaController( $request, $response, $service, $app);
//        $template_path = dirname( __FILE__ ) . '/Paypal/View/Html/lqa.html';
//        $controller->setView( $template_path );
//        $controller->performValidations();
//        $controller->respond();
//    }
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
     *
     * @throws \Exception
     */
    public function beginDoAction( viewController $controller, $params ) {

        if( method_exists( $controller, 'setLoginRequired' ) ){
            /**
             * @var $controller viewController
             */
            $controller->setLoginRequired( true ) ;
            $controller->checkLoginRequiredAndRedirect();
        }

        try {
            //to use a validator whitelist here
            ( new TranslatorsWhitelistAccessValidator( $controller ) )->validate();
        } catch( AuthenticationError $e ){

            $controllerInstance = new CustomPage();
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
     * Callback for manage project type redirect
     *
     * @param viewController $controller
     *
     */
    public function handleProjectType( viewController $controller ) {

        if ( $controller instanceof \catController ) {
            $project      = $controller->project;
            $metadata     = new Projects_MetadataDao;
            $project_type = $metadata->get( $project->id, "project_type" );
            if ( !empty( $project_type ) ) {
                if ( $controller->isRevision() ) {
                    $page = "revision";
                } else {
                    $page = "translation";
                }


                // Is it really usefull? Maybe when project type is revision, the user will not must to see the revision button
                if ( $project_type->value == "TR" && $page == "revision" ) {
                    $chunk = $controller->getChunk();
                    header( 'Location: ' . \Routes::translate( $project->name, $chunk->id, $chunk->password, $chunk->source, $chunk->target ) );
                    die;
                }

                /**
                 * If user click on translate button (or open button) and project type is 'LR' (it means revision), he'll be redirected to revision page
                 */
                if ( $project_type->value == "LR" && $page == "translation" ) {
                    $chunk = $controller->getChunk();
                    $job   = \LQA\ChunkReviewDao::findOneChunkReviewByIdJobAndPassword( $chunk->id, $chunk->password );
                    header( 'Location: ' . \Routes::revise( $project->name, $chunk->id, $job->review_password, $chunk->source, $chunk->target ) );
                    die;
                }
            }
        }

    }

    /**
     * This method is used for add editLog csv in __meta of preview's zip
     * @param $controller
     * @param $output_content
     */

    public function appendEditLogToDownload( $controller, $output_content ) {
        $project      = $controller->getProject();
        $metadata     = new Projects_MetadataDao;
        $project_type = $metadata->get( $project->id, "project_type" );

        if ( !empty( $project_type ) && in_array($project_type->value, $this->project_types) ) {

            $file_parts = pathinfo( $output_content[ 0 ]->output_filename );
            if ( $file_parts[ 'extension' ] == "zip" ) {
                $zip = new \ZipArchive();
                $zip->open( $output_content[ 0 ]->input_filename );

                $this->model = new \EditLog_EditLogModel( $controller->id_job, $controller->password );
                $output      = $this->model->generateCSVOutput();
                $zip->addFromString( "__meta/Edit-log-export-" . $controller->id_job . ".csv", $output );
                $zip->close();
            }
        }

    }

    /**
     * This method is used for add segments comments csv in __meta of preview's zip
     * @param $controller
     * @param $output_content
     */

    public function appendSegmentsCommentsToDownload( $controller, $output_content ) {
        $project      = $controller->getProject();
        $metadata     = new Projects_MetadataDao;
        $project_type = $metadata->get( $project->id, "project_type" );

        if ( !empty( $project_type ) && in_array($project_type->value, $this->project_types) )
        {

            $file_parts = FilesStorage::pathinfo_fix( $output_content[ 0 ]->output_filename );
            if ( $file_parts[ 'extension' ] == "zip" ) {
                $zip = new \ZipArchive();
                $job = $controller->getJob();
                $zip->open( $output_content[ 0 ]->input_filename );

                $chunk = \Chunks_ChunkDao::getByIdAndPassword(
                        $job->id,
                        $job->password
                );

                $comments = \Comments_CommentDao::getCommentsForChunk( $chunk );
                $formatter = new SegmentComment( $comments ) ;
                $output = $formatter->genCSV();
                $zip->addFromString( "__meta/SegmentsComments_" . $controller->id_job . ".csv", $output );
                $zip->close();
            }
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