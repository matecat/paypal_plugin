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
use API\V2\Json\SegmentTranslationIssue;

class Paypal extends BaseFeature {

    const FEATURE_CODE = 'paypal';


    /**
     * @var CDataHandler
     */
    protected $jsonHandler;

    const PROJECT_TYPE_LR = 'LR' ;

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
    public function filterNewProjectInputFilters( $filter_args ) {
        unset( $filter_args[ 'tag_projection' ] );  //disable Guess Tag Position Feature
        $filter_args[ 'project_type' ] = [ 'filter' => FILTER_CALLBACK, 'options' => [ __CLASS__, 'sanitizeProjectTypeValue' ] ];
        $filter_args[ 'comments' ]  = [ 'filter' => FILTER_SANITIZE_STRING  ];

        return $filter_args;
    }

    public function addNewProjectStructureAttributes( $projectStructure, $post_input ) {
        $projectStructure['comments'] = $post_input['comments'];

        return $projectStructure;
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
     * This method is used for add export csv in __meta of preview's zip
     *
     * @param $controller
     * @param $output_content
     *
     */

    public function processZIPDownloadPreview($controller, $output_content){
        $project = $controller->getProject();

        if ( $this->isPaypalProject( $project ) ) {
            $file_parts = \FilesStorage::pathinfo_fix( $output_content[ 0 ]->output_filename );
            if ( $file_parts[ 'extension' ] == "zip" ) {
                $zip = new \ZipArchive();
                $job = $controller->getJob();
                $zip->open( $output_content[ 0 ]->input_filename );

                /**
                 * appendEditLogToDownload
                 */

                $editlog_model = new \EditLog_EditLogModel( $controller->id_job, $controller->password );
                $filePath    = $editlog_model->genCSVTmpFile();
                $zip->addFile( $filePath, "__meta/Edit-log-export-" . $controller->id_job . ".csv" );

                /**
                 * appendSegmentsCommentsToDownload
                 */

                $chunk = \Chunks_ChunkDao::getByIdAndPassword(
                        $job->id,
                        $job->password
                );

                $comments  = \Comments_CommentDao::getCommentsForChunk( $chunk );
                $formatter = new SegmentComment( $comments );
                $filePath  = $formatter->genCSVTmpFile();
                $zip->addFile( $filePath, "__meta/Segments-comments-export_" . $controller->id_job . ".csv" );

                /**
                 * appendSegmentsIssuesCommentsToDownload
                 */

                $entries   = \LQA\EntryDao::findAllByChunk( $chunk );
                $formatter = new SegmentTranslationIssue;
                $filePath  = $formatter->genCSVTmpFile( $entries );
                $zip->addFile( $filePath, "__meta/Segments-issues-export_" . $controller->id_job . ".csv" );

                /**
                 * appendJobsInfoToDownload
                 */

                $metadata     = new Projects_MetadataDao;
                $project_type = $metadata->get( $project->id, "project_type" );
                $csv_array    = [];
                $csv_array[] = [ 'project_id', $project->id ];
                $csv_array[] = [ 'project_type', ( !empty( $project_type ) )?$project_type->value:"General" ];
                $csv_array[] = [ 'job_id', $job->id ];
                $csv_array[] = [ 'translate_password', $job->password ];

                $revise_chunk   = \LQA\ChunkReviewDao::findOneChunkReviewByIdJobAndPassword( $chunk->id, $chunk->password );

                $csv_array[] = [ 'revise_password', $revise_chunk->review_password ];
                $csv_array[] = [ 'create_date', \Utils::api_timestamp( $job->create_date ) ];

                $translation = \Chunks_ChunkCompletionEventDao::lastCompletionRecord( $chunk, [ 'is_review' => false ] );
                $revise      = \Chunks_ChunkCompletionEventDao::lastCompletionRecord( $chunk, [ 'is_review' => true ] );

                $csv_array[] = [ 'end_translation_date', ($translation)?\Utils::api_timestamp( $translation[ 'create_date' ] ):"N/A" ];
                $csv_array[] = [ 'end_revision_date', ($revise)?\Utils::api_timestamp( $revise[ 'create_date' ] ):"N/A" ];
                $csv_array[] = [ 'project_password', $project->password ];

                $filePath = $this->genCSVKeyValueFile( $csv_array );
                $zip->addFile( $filePath, "__meta/Job-info-export_" . $controller->id_job . ".csv" );


                $zip->close();
            }
        }
    }

    /**
     * This method can be used to check if project has been made by paypal
     *
     * @param \Projects_ProjectStruct $project
     *
     * @return bool
     */

    private function isPaypalProject( \Projects_ProjectStruct $project ) {
        $metadata     = new Projects_MetadataDao;
        $project_type = $metadata->get( $project->id, "features" );

        if ( !empty( $project_type ) && in_array( self::FEATURE_CODE, explode(",", $project_type->value) ) ) {
            return true;
        }

        return false;
    }

    private function genCSVKeyValueFile($array){
        $filePath   = tempnam( "/tmp", "KeyValueCSV_" );
        $csvHandler = new \SplFileObject( $filePath, "w" );
        $csvHandler->setCsvControl( ';' );

        foreach($array as $row)
        {
            $csvHandler->fputcsv( $row );
        }

        return $filePath;
    }

    public function project_completion_event_saved( \Jobs_JobStruct $chunk, $eventStruct, $chunkCompletionEventId ) {
        $translations_segments_dao = new \Translations_SegmentTranslationDao;
        if ( $eventStruct->is_review ) {
            $translations_segments_dao->setApprovedByChunk( $chunk );
        } else {
            $translations_segments_dao->setTranslatedByChunk( $chunk );
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

    /**
     * Ebay customisation requires that identical source and target are considered identical
     *
     * @param $originalValue
     * @param $projectStructure
     * @param $xliff_trans_unit
     *
     * @return bool
     */
    public function filterIdenticalSourceAndTargetIsTranslated( $originalValue, $projectStructure, $xliff_trans_unit ) {

        if( isset( $xliff_trans_unit[ 'attr' ][ 'approved'] ) && $xliff_trans_unit[ 'attr' ][ 'approved'] ){
            return $xliff_trans_unit[ 'attr' ][ 'approved'];
        }

        return $originalValue;

    }

    public function addCommentsToZipProject( $projectStructure, $zipDir) {
        $datePath = date_create( $this->projectStructure[ 'create_date' ] )->format( 'Ymd' );

        $newZipDir  = $zipDir . DIRECTORY_SEPARATOR . $datePath . DIRECTORY_SEPARATOR . $projectStructure['id_project'] ;

        file_put_contents( $newZipDir . "/comments.txt", $projectStructure[ 'comments' ] );
    }

    /**
     * @param $projectStructure
     */
    public function postProjectCommit( $projectStructure ) {
        // find all chunks
        // for each chunk create a Translation
        $project = \Projects_ProjectDao::findById( $projectStructure[ 'id_project' ] ) ;
        if ( $project->getMetadataValue('project_type') == self::PROJECT_TYPE_LR  ) {
            foreach( $project->getChunks() as $chunk ) {
                $model = new Features\Paypal\Model\ChunkCompletionEventModel($chunk) ;
                $model->setTranslationCompleted([ 'ip_address' => $projectStructure['user_ip'] ] ) ;
            }
        }
    }

    public function postJobSplitted( $projectStructure ) {
        $chunk = \Chunks_ChunkDao::getByIdAndPassword( $projectStructure['job_to_split'], $projectStructure['job_to_split_pass'] ) ;
        $project = $chunk->getProject();
        if ( $project->getMetadataValue('project_type') == self::PROJECT_TYPE_LR ) {
            foreach( $project->getChunks() as $chunk ) {
                $model = new Features\Paypal\Model\ChunkCompletionEventModel($chunk) ;
                $model->setTranslationCompleted() ;
            }
        }
    }

}