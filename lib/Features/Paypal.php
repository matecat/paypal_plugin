<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 1/29/16
 * Time: 1:08 PM
 */

namespace Features;

use API\V2\Exceptions\AuthenticationError;
use API\V2\Json\ProjectUrls;
use API\V2\Json\SegmentComment;
use API\V2\Json\SegmentTranslationIssue;
use BasicFeatureStruct;
use CatUtils;
use Constants_TranslationStatus;
use Contribution\ContributionStruct;
use CustomPage;
use Exception;
use Features;
use Features\Paypal\Controller\API\Validators\TranslatorsWhitelistAccessValidator;
use Features\Paypal\Controller\PreviewController;
use Features\Paypal\Model\Analysis\CustomPayableRates;
use Features\Paypal\Utils\CDataHandler;
use Features\Paypal\Utils\Routes;
use Features\Paypal\View\API\JSON\ProjectUrlsDecorator;
use Klein\Klein;
use LQA\ChunkReviewDao;
use Monolog\Logger;
use Projects_MetadataDao;
use Projects_ProjectStruct;
use Users_UserStruct;
use viewController;

class Paypal extends BaseFeature {

    const FEATURE_CODE = 'paypal';

    /**
     * @var bool
     */
    protected $autoActivateOnProject = false;

    /**
     * @var CDataHandler
     */
    protected $jsonHandler;

    const PROJECT_TYPE_METADATA_KEY = "project_type";

    const PROJECT_TYPE_LR = 'LR';

    protected static $project_types = [ 'TR', 'LR', 'LQA' ];

    /**
     * @var Logger
     */
    protected static $logger ;

    public static $dependencies = [
            Features::PROJECT_COMPLETION,
            Features::TRANSLATION_VERSIONS,
            Features::REVIEW_EXTENDED
    ];

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
    public static function overloadAPIDocs( array $jsIncludes ) {
        $jsIncludes[] = '<script src="' . Routes::staticSrc( 'src/js/swagger.js' ) . '" type="text/javascript" ></script>';

        return $jsIncludes;
    }

    /**
     * @param Klein $klein
     */
    public static function loadRoutes( Klein $klein ) {

        //TODO Refactor
        //$klein->respond( 'GET', '/lqa/[:id_job]/[:password]', [__CLASS__, 'lqaRoute'] );
        $klein->respond( 'GET', '/preview/template/[:id_job]/[:password]', [ __CLASS__, 'previewRoute' ] );

        route( '/preview/[:id_project]/[:password]/[:file_name_in_zip]', 'GET', 'Features\Paypal\Controller\API\ReferenceFilesController', 'flushStream' );
        route( '/preview/[:id_job]/[:password]', 'GET', 'Features\Paypal\Controller\API\PreviewsStruct', 'getPreviewsStruct' );
        route( '/job/[:id_job]/[:password]/instructions', 'GET', 'Features\Paypal\Controller\API\JobController', 'getInstructions' );
        route( '/job/[:id_job]/[:password]/segments/[:segments_ids]', 'GET', 'Features\Paypal\Controller\API\JobController', 'getSegments' );
        route( '/reference-files/[:id_job]/[:password]', 'GET', 'Features\Paypal\Controller\API\ReferenceFilesController', 'getReferenceFolder' );
        route( '/reference-files/[:id_job]/[:password]/list', 'GET', 'Features\Paypal\Controller\API\ReferenceFilesController', 'getReferenceFolderList' );
        route( '/projects/[:id_project]/[:password]/whitelist', 'POST', 'Features\Paypal\Controller\API\WhitelistController', 'create' );
        route( '/projects/[:id_project]/[:password]/whitelist', 'DELETE', 'Features\Paypal\Controller\API\WhitelistController', 'delete' );
        route( '/lqa/[:id_job]/[:password]', 'GET', 'Features\Paypal\Controller\LqaController', 'show' );

        route( '/oauth/github/response', 'GET', 'Features\Paypal\Controller\OAuth\GithubOAuthController', 'response' );

        route( '/saml/login', [ 'GET', 'POST' ] , 'Features\Paypal\Controller\SAML\PayPalController', 'login' );
        route( '/saml/logout', [ 'GET', 'POST' ] , 'Features\Paypal\Controller\SAML\PayPalController', 'logout' );
        route( '/saml/forward', [ 'GET', 'POST' ] , 'Features\Paypal\Controller\SAML\PayPalController', 'forward' );
    }

    public static function previewRoute( $request, $response, $service, $app ) {
        $controller    = new PreviewController( $request, $response, $service, $app );
        $template_path = dirname( __FILE__ ) . '/Paypal/View/Html/preview.html';
        $controller->setView( $template_path );
        $controller->respond( 'composeView' );
    }

    public static function projectUrls( ProjectUrls $formatted ) {
        $projectUrlsDecorator = new ProjectUrlsDecorator( $formatted->getData() );

        return $projectUrlsDecorator;
    }

    /**
     * As Autoload Plugin PayPal set itself as project feature when an user is logged
     *
     * @param $projectFeatures
     * @param $__postInput
     *
     * @return array
     */
    public function filterCreateProjectFeatures( $projectFeatures, $__postInput, $userIsLogged ) {

        if( $userIsLogged ){
            $projectFeatures[ self::FEATURE_CODE ] = new BasicFeatureStruct( [ 'feature_code' => self::FEATURE_CODE ] );
        }

        return $projectFeatures;

    }

    public function filterContributionStructOnSetTranslation( ContributionStruct $contributionStruct, Projects_ProjectStruct $project ) {

        try {

            $metadataValue = $project->getMetadataValue( Paypal::PROJECT_TYPE_METADATA_KEY );
            if ( !empty( $metadataValue ) ) {
                $contributionStruct->props[ Paypal::PROJECT_TYPE_METADATA_KEY ] = $metadataValue;
            }

            //get Notes
            $segmentNotes                        = $contributionStruct->getSegmentNotes();
            $jsonNote                            = json_decode( $segmentNotes[ 0 ]->json );
            $contributionStruct->props[ 'note' ] = $jsonNote->note;

            $userInfoList                        = $contributionStruct->getUserInfo();
            $userInfo                            = array_pop( $userInfoList );
            $contributionStruct->props[ 'user' ] = $userInfo->email;

        } catch ( Exception $e ) {

        }

        return $contributionStruct;
    }

    /**
     *
     * @param                  $propArray
     * @param Users_UserStruct $userStruct
     *
     * @return mixed
     */
    public function filterGlossaryOnSetTranslation( $propArray, Users_UserStruct $userStruct ) {

        if ( empty( $userStruct->email ) ) {
            return $propArray;
        }

        try {
            $propArray[ 'user' ] = $userStruct->email;
//            $contributionStruct->props[ 'SID' ]  = 'SID';
        } catch ( Exception $e ) {

        }

        return $propArray;
    }

    /**
     * Ignore all glossaries. Temporary hack to avoid something unknown on MyMemory side.
     * We simply change the array_files key to avoid any glossary to be sent to MyMemory.
     *
     * TODO: glossary detection based on extension is brittle.
     *
     */
    public function filter_project_manager_array_files( $files, $projectStructure ) {
        $new_files = [];
        foreach ( $files as $file ) {
            if ( \FilesStorage::pathinfo_fix( $file, PATHINFO_EXTENSION ) != 'g' ) {
                $new_files[] = $file;
            }
        }

        return $new_files;
    }

    public function handleJsonNotes( $projectStructure ) {
        $this->jsonHandler->formatJson( $projectStructure );
    }

    /**
     * Tell to the controller to extract also json content of segment_notes table
     * @return bool
     */
    public function prepareAllNotes() {
        return true;
    }

    /**
     * @param $jsonStringNotes string
     *
     * @return mixed
     */
    public function processExtractedJsonNotes( $jsonStringNotes ) {
        return $this->jsonHandler->parseJsonNotes( $jsonStringNotes );
    }

    public function processJobsCreated( $projectStructure ) {
        $this->jsonHandler->storePreviewsMetadata( $projectStructure );
    }

    /**
     * Override the instance decision to convert or not the normal xlf/xliff files
     *
     * @param $forceXliff
     *
     * @return bool
     */
    public function forceXLIFFConversion( $forceXliff, $_userIsLogged ) {
        if( !$_userIsLogged ) {
            return $forceXliff;
        }
        return false;
    }

    /**
     * Decide whether remove initial tags or not
     *
     * @param $boolean
     * @param $segment
     *
     * @return bool
     */
    public function skipTagLessFeature( $boolean, $segment ) {
        /**
         * Ugly tag recognition, it's the easy way to decide whether a tag is a normal tag or a paypal xml <ph> tag
         *
         * Filters do not use <ph> tags, so it comes directly from a not converted xliff.
         * In the PayPal feature ALL the Xliff are NOT converted
         *
         */
        if( preg_match('|^<ph [^>]+>|', $segment ) ){
            return true;
        }
        return false;
    }

    public function filter_manage_single_project( $project ) {
        $metadata                  = new Projects_MetadataDao;
        $project_type              = $metadata->setCacheTTL( 60 * 60 * 24 )->get( $project[ 'id' ], "project_type" );
        $project[ 'project_type' ] = $project_type->value;

        return $project;
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
        $filter_args[ 'instructions' ] = [ 'filter' => FILTER_SANITIZE_STRING ];

        return $filter_args;
    }

    public function filterCreateProjectInputFilters( $filter_args ){
        unset( $filter_args[ 'tag_projection' ] );
        return $filter_args;
    }

    public function addNewProjectStructureAttributes( $projectStructure, $post_input ) {
        $projectStructure[ 'instructions' ] = $post_input[ 'instructions' ];

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
     * @throws Exception
     */
    public function filterProjectMetadata( $metadata, $__postInput ) {

        if( !empty( $__postInput[ Paypal::PROJECT_TYPE_METADATA_KEY ] ) ){
            if( !in_array( $__postInput[ Paypal::PROJECT_TYPE_METADATA_KEY ], self::$project_types ) ){
                throw new Exception( "Project type '{$__postInput[ Paypal::PROJECT_TYPE_METADATA_KEY ]}'' is not allowed. Allowed types: [ 'TR', 'LR', 'LQA', NULL ]." );
            }
            $metadata[ Paypal::PROJECT_TYPE_METADATA_KEY ] = $__postInput[ Paypal::PROJECT_TYPE_METADATA_KEY ];
        }

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
    public function beginDoAction( viewController $controller, $params = [] ) {

        if ( method_exists( $controller, 'setLoginRequired' ) ) {
            /**
             * @var $controller viewController
             */
            $controller->setLoginRequired( true );
            $controller->checkLoginRequiredAndRedirect();
        }

        try {
            //to use a validator whitelist here
            ( new TranslatorsWhitelistAccessValidator( $controller ) )->validate();
        } catch ( AuthenticationError $e ) {

            $controllerInstance = new CustomPage();
            $template           = new \PHPTALWithAppend( dirname( __FILE__ ) . '/Paypal/View/Html/NotAllowed.html' );
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
            $project_type = $metadata->get( $project->id, Paypal::PROJECT_TYPE_METADATA_KEY );
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
                    $job   = ChunkReviewDao::findOneChunkReviewByIdJobAndPassword( $chunk->id, $chunk->password );
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
     * @throws \Exceptions\NotFoundError
     */

    public function processZIPDownloadPreview( $controller, $output_content ) {
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
                $filePath      = $editlog_model->genCSVTmpFile();
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
                $project_type = $metadata->get( $project->id, Paypal::PROJECT_TYPE_METADATA_KEY );
                $csv_array    = [];
                $csv_array[]  = [ 'project_id', $project->id ];
                $csv_array[]  = [ Paypal::PROJECT_TYPE_METADATA_KEY, ( !empty( $project_type ) ) ? $project_type->value : "General" ];
                $csv_array[]  = [ 'job_id', $job->id ];
                $csv_array[]  = [ 'translate_password', $job->password ];

                $revise_chunk = ChunkReviewDao::findOneChunkReviewByIdJobAndPassword( $chunk->id, $chunk->password );

                $csv_array[] = [ 'revise_password', $revise_chunk->review_password ];
                $csv_array[] = [ 'create_date', \Utils::api_timestamp( $job->create_date ) ];

                $translation = \Chunks_ChunkCompletionEventDao::lastCompletionRecord( $chunk, [ 'is_review' => false ] );
                $revise      = \Chunks_ChunkCompletionEventDao::lastCompletionRecord( $chunk, [ 'is_review' => true ] );

                $csv_array[] = [ 'end_translation_date', ( $translation ) ? \Utils::api_timestamp( $translation[ 'create_date' ] ) : "N/A" ];
                $csv_array[] = [ 'end_revision_date', ( $revise ) ? \Utils::api_timestamp( $revise[ 'create_date' ] ) : "N/A" ];
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

        if ( !empty( $project_type ) && in_array( self::FEATURE_CODE, explode( ",", $project_type->value ) ) ) {
            return true;
        }

        return false;
    }

    /**
     * @param $array
     *
     * @return bool|string
     */
    private function genCSVKeyValueFile( $array ) {
        $filePath   = tempnam( "/tmp", "KeyValueCSV_" );
        $csvHandler = new \SplFileObject( $filePath, "w" );
        $csvHandler->setCsvControl( ';' );

        foreach ( $array as $row ) {
            $csvHandler->fputcsv( $row );
        }

        return $filePath;
    }

    /**
     * @param \Jobs_JobStruct $chunk
     * @param                 $eventStruct
     * @param                 $chunkCompletionEventId
     */
    public function project_completion_event_saved( \Jobs_JobStruct $chunk, $eventStruct, $chunkCompletionEventId ) {
        $translations_segments_dao = new \Translations_SegmentTranslationDao;
        if ( $eventStruct->is_review ) {
            $translations_segments_dao->setApprovedByChunk( $chunk );
        } else {
            $translations_segments_dao->setTranslatedByChunk( $chunk );
        }

    }

    /**
     *
     * Payable Rates customization hook
     * 
     * @param $payableRates
     * @param $SourceLang
     * @param $TargetLang
     *
     * @return array
     */
    public function filterPayableRates( $payableRates, $SourceLang, $TargetLang ){
        return CustomPayableRates::getPayableRates( $SourceLang, $TargetLang );
    }

    public function modifyMatches( $matches ){

        $remove = false;

        /**
         * Force to perform re-ordering and match rewrite
         */
        if( count( $matches ) == 1 ){
            $remove = true;
            $matches[ 1 ] = $matches[ 0 ];
        }

        /**
         * Force the MT value as 75 and reorder the matches
         */
        usort ( $matches , function( &$matchA, &$matchB ){

            if ( stripos( $matchA[ 'created_by' ], "MT" ) !== false ){
                $matchA[ 'match' ] = "75%";
            }

            if ( stripos( $matchB[ 'created_by' ], "MT" ) !== false ){
                $matchB[ 'match' ] = "75%";
            }

            if( intval( $matchA[ 'match' ] ) == intval( $matchB[ 'match' ] ) ){
                return 0;
            }

            return ( intval( $matchA[ 'match' ] ) < intval( $matchB[ 'match' ] ) ) ? 1 : -1;

        } );

        if( $remove ){
            array_pop( $matches );
        }

        return $matches;

    }

    /**
     * Customize Band value for the match retrieved during the TM Analysis
     *
     * @param $tm_match_type
     *
     * @return string
     */
    public function customizeTMMatches( $tm_match_type ){

        /**
         * If the first match is a match 50/74 ( the MT was not found for any reason ) force MT discount
         */
        if ( stripos( $tm_match_type, "MT" ) === false ) {

            $ind = intval( $tm_match_type );
            if ( $ind < 75 ) {
                $tm_match_type = "MT";
            }

        }

        return $tm_match_type;

    }

    /**
     * Set the TM ICES in TManalysis
     *
     * @param $tm_data
     * @param $queueElementParams
     *
     * @return mixed
     */
    public function checkIceLocked( $tm_data, $queueElementParams ) {
        $tm_data[ 'status' ] = \Constants_TranslationStatus::STATUS_TRANSLATED;
//        $tm_data[ 'locked' ] = true; //already locked
        return $tm_data;
    }

    /**
     * Lock 100% matches in TManalysis
     *
     * @param $tm_data
     * @param $queueElementParams
     *
     * @return mixed
     */
    public function check100MatchLocked( $tm_data, $queueElementParams ){
        $tm_data[ 'status' ] = \Constants_TranslationStatus::STATUS_TRANSLATED;
        return $tm_data;
    }

    /**
     * Rewrite 101% to 100% from matches retrieved by getContributionController
     *
     * @param $match
     *
     * @return mixed
     */
    public function iceMatchRewriteForContribution( $match ) {
        if ( $match[ 'match' ] == '101%' ) {
            $match[ 'match' ] = '100%';
        }

        return $match;
    }

    /**
     *
     * Add additional parameters to the getMoreSegments query
     *
     * @param $options
     *
     * @return mixed
     */
    public function filter_get_segments_optional_fields( $options ){
        $options[ 'optional_fields' ][] = "IF( ( st.locked AND match_type = 'ICE' ) OR suggestion_match = 100, 1, 0 ) AS ice_locked"; // ALL 100% matches are locked for PayPal
        $options[ 'optional_fields' ][] = "st.translation"; // Return ALL translations in the UI, even if the statuses are NEW
        $options[ 'optional_fields' ][] = "IF( st.status = 'NEW', 'DRAFT', st.status ) as status"; // Show NEW statuses as DRAFT in the UI
        return $options;
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
    public function setICESLockFromXliffValues( $iceLockArray ) {

        if ( $iceLockArray[ 'approved' ] ) {
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

        if ( isset( $xliff_trans_unit[ 'attr' ][ 'approved' ] ) && $xliff_trans_unit[ 'attr' ][ 'approved' ] ) {
            return $xliff_trans_unit[ 'attr' ][ 'approved' ];
        }

        return $originalValue;

    }

    /**
     * @param $projectStructure
     * @param $zipDir
     */
    public function addInstructionsToZipProject( $projectStructure, $zipDir ) {
        if ( !empty( $projectStructure[ 'instructions' ] ) ) {
            $datePath = date_create( $projectStructure[ 'create_date' ] )->format( 'Ymd' );

            $newZipDir = $zipDir . DIRECTORY_SEPARATOR . $datePath . DIRECTORY_SEPARATOR . $projectStructure[ 'id_project' ];

            file_put_contents( $newZipDir . "/instructions.txt", $projectStructure[ 'instructions' ] );
        }
    }

    /**
     * @param $projectStructure
     */
    public function postProjectCommit( $projectStructure ) {
        // find all chunks
        // for each chunk create a Translation
        $project = \Projects_ProjectDao::findById( $projectStructure[ 'id_project' ] );
        if ( $project->getMetadataValue( Paypal::PROJECT_TYPE_METADATA_KEY ) == self::PROJECT_TYPE_LR ) {
            foreach ( $project->getChunks() as $chunk ) {
                $model = new Features\Paypal\Model\ChunkCompletionEventModel( $chunk );
                $model->setTranslationCompleted( [ 'ip_address' => $projectStructure[ 'user_ip' ] ] );
            }
        }
    }

    /**
     * @param $projectStructure
     *
     * @throws \Exceptions\NotFoundError
     */
    public function postJobSplitted( $projectStructure ) {
        $chunk   = \Chunks_ChunkDao::getByIdAndPassword( $projectStructure[ 'job_to_split' ], $projectStructure[ 'job_to_split_pass' ] );
        $project = $chunk->getProject();
        if ( $project->getMetadataValue( Paypal::PROJECT_TYPE_METADATA_KEY ) == self::PROJECT_TYPE_LR ) {
            foreach ( $project->getChunks() as $chunk ) {
                $model = new Features\Paypal\Model\ChunkCompletionEventModel( $chunk );
                $model->setTranslationCompleted();
            }
        }
    }

    /**
     * @param $controller
     * @param $template
     *
     * @throws Exception
     */
    public function appendDecorators( $controller, $template ) {
        if ( method_exists( $template, 'append' ) ) {

            if ( !isset( $_SESSION[ 'paypal_github_oauth_state' ] ) ) {
                $state = $_SESSION[ 'paypal_github_oauth_state' ] = CatUtils::generate_password( 12 );
            } else {
                $state = $_SESSION[ 'paypal_github_oauth_state' ];
            }

            $template->append( 'config_js', [
                    'auth_disable_google' => false,
                    'auth_disable_email' => false,
                    'other_service_auth_url' => Routes::samlOwnLoginURL(),
                    'other_service_button_label' => 'Sign in with PayPal'
            ] );
        }
    }

    public static function getSamlAgentConfigFilePath() {
        return realpath( self::getPluginBasePath() . '/../config/saml-agent-config.txt' ) ;
    }

    public static function staticLogger() {
        if ( is_null( self::$logger ) ) {
            $feature = new BasicFeatureStruct(['feature_code' => self::FEATURE_CODE ]);
            self::$logger = ( new Paypal($feature) )->getLogger();
        }
        return self::$logger ;
    }

}
