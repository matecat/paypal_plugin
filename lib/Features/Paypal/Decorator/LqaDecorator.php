<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 15/02/2018
 * Time: 13:10
 */

namespace Features\Paypal\Decorator;

use AbstractDecorator;
use CatUtils;
use ChunkOptionsModel;
use Chunks_ChunkStruct;
use Constants_ProjectStatus;
use Constants_Teams;
use Constants_TranslationStatus;
use Database;
use EnginesModel_EngineDAO;
use EnginesModel_EngineStruct;
use Features\Dqf\Utils\Functions;
use Features\Paypal\Controller\LqaController;
use INIT;
use Jobs\JobStatsStruct;
use Langs_Languages;
use LexiQA\LexiQADecorator;
use ProjectOptionsSanitizer;
use Projects_ProjectDao;
use RemoteFiles_RemoteFileDao;
use Revise_JobQA;
use TeamModel;
use TmKeyManagement\UserKeysModel;
use TmKeyManagement_Filter;
use Users_UserDao;
use Utils;
use ZipArchiveExtended;

class LqaDecorator extends AbstractDecorator {

    /**
     * @var LqaController
     */
    protected $controller ;
    /**
     * @var \PHPTALWithAppend
     */
    protected $template ;

    /**
     * @var \Projects_ProjectStruct
     */
    protected $project ;

    /**
     * @var Chunks_ChunkStruct
     */
    protected $chunk ;

    protected $wStruct ;

    protected $isGDriveProject ;

    protected $review_password ;

    protected $jobStats ;

    /**
     * @var JobStatsStruct
     */
    protected $jobStatsStruct ;

    public function setReviewPassword( $password ) {
        $this->review_password = $password ;
    }

    public function decorate() {
        $start_time = microtime( 1 ) * 1000 ;

        // constants and INIT based
        $this->template->isReview               = true ;
        $this->template->maxFileSize            = INIT::$MAX_UPLOAD_FILE_SIZE ;
        $this->template->maxTMXFileSize         = INIT::$MAX_UPLOAD_TMX_FILE_SIZE;
        $this->template->warningPollingInterval = 1000 * ( INIT::$WARNING_POLLING_INTERVAL );
        $this->template->segmentQACheckInterval = 1000 * ( INIT::$SEGMENT_QA_CHECK_INTERVAL );
        $this->template->maxNumSegments         = INIT::$MAX_NUM_SEGMENTS;
        $this->template->copySourceInterval     = INIT::$COPY_SOURCE_INTERVAL;
        $this->template->time_to_edit_enabled   = INIT::$TIME_TO_EDIT_ENABLED;
        $this->template->tagLockCustomizable    = ( INIT::$UNLOCKABLE_TAGS == true ) ? true : false;
        $this->template->basepath               = INIT::$BASEURL ;
        $this->template->build_number           = INIT::$BUILD_NUMBER;
        $this->template->review_type            = "extended-footer";

        $this->template->splitSegmentEnabled    = var_export(true, true);
        $this->template->chunk_completion_undoable = true ;
        $this->template->translation_matches_enabled = true ;
        $this->template->allow_link_to_analysis = true ;
        $this->template->lxq_enabled            = 0;

        $this->template->support_mail        = INIT::$SUPPORT_MAIL;
        $this->template->use_compiled_assets  = INIT::$USE_COMPILED_ASSETS;

        if ( !$this->controller->getChunkValidator()->getChunk() ) {
            $this->__populateFroNotFound() ;
        } elseif (
                $this->controller->getChunkValidator()->getChunk()->isArchived() ||
                $this->controller->getChunkValidator()->getChunk()->isCanceled() ) {
            $this->__populateVarsForArchivedOrCanceled();
        }
        else {
            $this->__popluateForJobOk() ;
        }


        $end_time                    = microtime( true ) * 1000;
        $load_time                   = $end_time - $start_time;
        $this->template->load_time   = $load_time;
    }

    private function __populateFroNotFound() {

    }

    private function __popluateForJobOk() {
        $this->chunk   = new Chunks_ChunkStruct( $this->controller->getChunkValidator()->getChunk()->toArray() );
        $this->project = $this->chunk->getProject() ;

        $this->isGDriveProject = Projects_ProjectDao::isGDriveProject($this->chunk->id_project) ;
        $this->template->isGDriveProject = $this->isGDriveProject ;

        $this->__populateFromGetSegmentsInfo();
        $this->__populateFromFileInfo() ;

        $this->template->status_labels          = $this->getStatusLabels() ;
        $this->template->searchable_statuses    = $this->getSearchableStatuses() ;

        $this->template->job_not_found = false ;
        $this->template->job_cancelled = false ;
        $this->template->job_archived = false ;

        $this->template->tag_projection_languages = json_encode(
                ProjectOptionsSanitizer::$tag_projection_allowed_languages
        );

        $this->__populatePlaceholderVars();
        $this->__populateForComments() ;

        $this->template->pname               = $this->project->name ;
        $this->template->pid                 = $this->project->id ;
        $this->template->review_password     = $this->review_password ;
        $this->template->quality_report_href = '#' ;

        $langHandler = Langs_Languages::getInstance();
        $this->template->languages_array = json_encode( $langHandler->getEnabledLanguages( 'en' )  );

        $this->template->project_type = null ;

        $this->setBrowserSupport() ;

        // Chunk based
        $this->template->create_date         = $this->chunk->create_date ;
        $this->template->jid                 = $this->chunk->id ;
        $this->template->first_job_segment   = $this->chunk->job_first_segment ;
        $this->template->last_job_segment    = $this->chunk->job_last_segment ;
        $this->template->mt_id               = $this->chunk->id_mt_engine ;
        $this->template->password            = $this->chunk->password ;
        $this->template->source_code        = $this->chunk->source ;
        $this->template->target_code        = $this->chunk->target ;
        $this->template->tms_enabled            = var_export( (bool) $this->chunk->id_tms , true );
        $this->template->mt_enabled             = var_export( (bool) $this->chunk->id_mt_engine , true );

        $this->template->remoteFilesInJob = $this->getRemoteFilesInJob();

        $this->__populateFromChunkOptionsModel() ;

        // User Based
        $this->template->owner_email = $this->__getOwnerEmail() ;
        $this->template->jobOwnerIsMe = ( $this->controller->getUser()->email == $this->__getOwnerEmail() );

        $this->template->footer_show_revise_link    = false;
        $this->template->footer_show_translate_link = true;
        $this->template->review_class               = 'review';

        $lang_handler = Langs_Languages::getInstance();
        $this->template->source_rtl = ( $lang_handler->isRTL( $this->chunk->source ) ) ? ' rtl-source' : '';
        $this->template->target_rtl = ( $lang_handler->isRTL( $this->chunk->target ) ) ? ' rtl-target' : '';

        $this->template->header_quality_report_item_class = '';
        $this->template->header_main_button_enabled = true;
        $this->template->header_main_button_label   = $this->getHeaderMainButtonLabel();
        $this->template->header_main_button_id      = 'downloadProject';

        $this->template->uses_matecat_filters = Utils::isJobBasedOnMateCatFilters($this->chunk->id );

        $this->template->segmentFilterEnabled = false;
        $this->template->editLogClass         = "";
        $this->template->page        = 'cattool';
        $this->template->get_public_matches = ( !$this->chunk->only_private_tm );


        $this->__populateMTEngines();


    }

    private function __populateVarsForArchivedOrCanceled() {
        $this->template->pid                 = null;
        $this->template->source_code         = null;
        $this->template->target_code         = null;

        $this->template->firstSegmentOfFiles = 0;
        $this->template->fileCounter         = 0;

        $this->template->jobOwnerIsMe        = false;
        $this->template->support_mail        = INIT::$SUPPORT_MAIL;
        $this->template->owner_email         = INIT::$SUPPORT_MAIL;

        $this->template->job_not_found = false ;
        $this->template->job_cancelled = true ;
        $this->template->job_archived = true ;

        $team = $this->project->getTeam();

        if( !empty( $team ) ){
            $teamModel = new TeamModel( $team );
            $teamModel->updateMembersProjectsCount();
            $membersIdList = [];
            $ownerMail = null;

            /**
             * if team is personal get the emai address of the user
             * otherwise check if the team isa
             */
            if( $team->type == Constants_Teams::PERSONAL ){
                $ownerMail = $team->getMembers()[0]->getUser()->getEmail();
            } else {

                $ownerMail = ( new Users_UserDao() )->setCacheTTL( 60 * 60 * 24 )->getByUid( $this->project->id_assignee )->getEmail();
                $membersIdList = array_map( function( $memberStruct ){
                    /**
                     * @var $memberStruct \Teams\MembershipStruct
                     */
                    return $memberStruct->uid;
                }, $team->getMembers() );

            }
            $this->template->owner_email = $ownerMail;

            if ( $this->controller->getUser()->email == $ownerMail || in_array( $this->controller->getUser()->uid, $membersIdList ) ) {
                $this->template->jobOwnerIsMe        = true;
            } else {
                $this->template->jobOwnerIsMe        = false;
            }
        }
    }

    private function __populateFromChunkOptionsModel() {
        $chunk_options_model = new ChunkOptionsModel( $this->chunk ) ;

        $this->template->tag_projection_enabled = $chunk_options_model->isEnabled('tag_projection')   ;
        $this->template->speech2text_enabled = $chunk_options_model->isEnabled( 'speech2text' ) ;

        LexiQADecorator::getInstance( $this->template )
                ->checkJobHasLexiQAEnabled( $chunk_options_model )
                ->decorateViewLexiQA();

        $this->template->segmentation_rule = @$chunk_options_model->project_metadata[ 'segmentation_rule' ];
    }

    protected function setBrowserSupport() {
        $browser_info = Utils::getBrowser() ;
        $this->template->supportedBrowser = Utils::isSupportedWebBrowser($browser_info);
        $this->template->platform = strtolower( $browser_info[ 'platform' ] );
    }

    private function getRemoteFilesInJob() {
        $this->template->remoteFilesInJob = [];

        if ( $this->isGDriveProject ) {
            $files = array_map(function( $item ) {
                /** @var $item \RemoteFiles_RemoteFileStruct */
                return $item->attributes(array('id'));
            }, RemoteFiles_RemoteFileDao::getByJobId( $this->chunk->id ) );

            $this->template->remoteFilesInJob = $files ;
        }

    }

    private function getStatusLabels() {
        return json_encode( [
                Constants_TranslationStatus::STATUS_NEW        => 'New',
                Constants_TranslationStatus::STATUS_DRAFT      => 'Draft',
                Constants_TranslationStatus::STATUS_TRANSLATED => 'Translated',
                Constants_TranslationStatus::STATUS_APPROVED   => 'Approved',
                Constants_TranslationStatus::STATUS_REJECTED   => 'Rejected',
                Constants_TranslationStatus::STATUS_FIXED      => 'Fixed',
                Constants_TranslationStatus::STATUS_REBUTTED   => 'Rebutted'
        ] );
    }


    private function getSearchableStatuses() {
        $statuses = array_merge(
                Constants_TranslationStatus::$INITIAL_STATUSES,
                Constants_TranslationStatus::$TRANSLATION_STATUSES,
                Constants_TranslationStatus::$REVISION_STATUSES
        );

        return array_map( function ( $item ) {
            return (object)array( 'value' => $item, 'label' => $item );
        }, $statuses );
    }

    private function __populatePlaceholderVars() {
        $this->template->brPlaceholdEnabled   = true;

        $this->template->lfPlaceholder        = CatUtils::lfPlaceholder;
        $this->template->crPlaceholder        = CatUtils::crPlaceholder;
        $this->template->crlfPlaceholder      = CatUtils::crlfPlaceholder;
        $this->template->lfPlaceholderClass   = CatUtils::lfPlaceholderClass;
        $this->template->crPlaceholderClass   = CatUtils::crPlaceholderClass;
        $this->template->crlfPlaceholderClass = CatUtils::crlfPlaceholderClass;
        $this->template->lfPlaceholderRegex   = CatUtils::lfPlaceholderRegex;
        $this->template->crPlaceholderRegex   = CatUtils::crPlaceholderRegex;
        $this->template->crlfPlaceholderRegex = CatUtils::crlfPlaceholderRegex;

        $this->template->tabPlaceholder      = CatUtils::tabPlaceholder;
        $this->template->tabPlaceholderClass = CatUtils::tabPlaceholderClass;
        $this->template->tabPlaceholderRegex = CatUtils::tabPlaceholderRegex;

        $this->template->nbspPlaceholder      = CatUtils::nbspPlaceholder;
        $this->template->nbspPlaceholderClass = CatUtils::nbspPlaceholderClass;
        $this->template->nbspPlaceholderRegex = CatUtils::nbspPlaceholderRegex;
    }

    private function __populateForComments() {
        $this->template->comments_enabled = true;
        $this->template->sse_base_url     = INIT::$SSE_BASE_URL;
    }

    private function __populateFromFileInfo() {
        $fileInfo     = getFirstSegmentOfFilesInJob( $this->chunk->id );
        $TotalPayable = array();
        foreach ( $fileInfo as &$file ) {
            $file[ 'file_name' ] = ZipArchiveExtended::getFileName( $file[ 'file_name' ] );

            $TotalPayable[ $file[ 'id_file' ] ][ 'TOTAL_FORMATTED' ] = $file[ 'TOTAL_FORMATTED' ];
        }
        $this->template->firstSegmentOfFiles = json_encode( $fileInfo );
        $this->template->fileCounter         = json_encode( $TotalPayable );
    }

    private function __getOwnerEmail() {
        return empty( $this->chunk->owner ) ? 'support@matecat.com' : $this->chunk->owner ;
    }

    /**
     * @deprecated try to get data from other objects in this class (project/chunk)
     */
    private function __populateFromGetSegmentsInfo() {
        $data = getSegmentsInfo( $this->chunk->id, $this->chunk->password );

        $this->template->cid = $data[ 0 ] [ 'cid' ] ;

        $userKeys = new UserKeysModel($this->controller->getUser(), TmKeyManagement_Filter::ROLE_REVISOR ) ;
        $this->template->user_keys = $userKeys->getKeys( $data[ 0 ] [ 'tm_keys' ] ) ;

        $jobStats = CatUtils::getFastStatsForJob( $this->getWStruct( $data ) );
        $jobStats[ 'STATUS_BAR_NO_DISPLAY' ] = ( $this->project->status_analysis == Constants_ProjectStatus::STATUS_DONE ? '' : 'display:none;' );
        $jobStats[ 'ANALYSIS_COMPLETE' ]     = ( $this->project->status_analysis == Constants_ProjectStatus::STATUS_DONE ? true : false );

        $this->jobStats = $jobStats ;
        $this->template->job_stats = $jobStats ;

        $jobQA = new Revise_JobQA(
                $this->chunk->id,
                $this->chunk->password,
                $this->getWStruct( $data )->getTotal(),
                new \Constants_Revise()
        );

        $jobQA->retrieveJobErrorTotals();

        $this->template->stat_quality = json_encode( $jobQA->getQaData() ) ;

        $jobVote = $jobQA->evalJobVote() ;
        $this->template->overall_quality_class = strtolower(
                str_replace(' ',  '', $jobVote[ 'minText' ] )
        );

        $this->jobStatsStruct = $jobStatsStruct = new JobStatsStruct( $jobStats );

        if( $jobStatsStruct->isCompleted() && $jobStatsStruct->isAllApproved() ){
            $this->template->header_main_button_class = 'downloadtr-button approved';
        } elseif( $jobStatsStruct->isCompleted() ) {
            $this->template->header_main_button_class = 'downloadtr-button translated';
        } else {
            $this->template->header_main_button_class = 'downloadtr-button draft';
        }

    }

    private function getWStruct( $data ) {
        if ( is_null( $this->wStruct ) ) {
            $this->wStruct = CatUtils::getWStructFromJobArray( $data[0] );
        }
        return $this->wStruct ;
    }

    private function getHeaderMainButtonLabel() {
        $label = '';

        if ( $this->jobStatsStruct->isDownloadable() ) {
            if($this->isGDriveProject) {
                $label = 'OPEN IN GOOGLE DRIVE';
            } else {
                $label = 'DOWNLOAD TRANSLATION';
            }
        } else {
            if($this->isGDriveProject) {
                $label = 'PREVIEW IN GOOGLE DRIVE';
            } else {
                $label = 'PREVIEW';
            }
        }

        return $label;
    }

    private function __populateMTEngines() {
        $engine = new EnginesModel_EngineDAO( Database::obtain() );

        //this gets all engines of the user
        if ( $this->controller->getUser()->uid ) {
            $engineQuery         = new EnginesModel_EngineStruct();
            $engineQuery->type   = 'MT';
            $engineQuery->uid    = $this->controller->getUser()->uid;
            $engineQuery->active = 1;
            $mt_engines          = $engine->read( $engineQuery );
        } else {
            $mt_engines = array();
        }

        // this gets MyMemory
        $engineQuery         = new EnginesModel_EngineStruct();
        $engineQuery->type   = 'TM';
        $engineQuery->active = 1;
        $tms_engine          = $engine->setCacheTTL( 3600 * 24 * 30 )->read( $engineQuery );

        //this gets MT engine active for the job
        $engineQuery         = new EnginesModel_EngineStruct();
        $engineQuery->id     = $this->chunk->id_mt_engine;
        $engineQuery->active = 1;
        $active_mt_engine    = $engine->setCacheTTL( 60 * 10 )->read( $engineQuery );

        /*
         * array_unique cast EnginesModel_EngineStruct to string
         *
         * EnginesModel_EngineStruct implements __toString method
         *
         */
        $this->template->mt_engines = array_unique( array_merge( $active_mt_engine, $tms_engine, $mt_engines ) );
        $this->template->mt_id      = $this->chunk->id_mt_engine;
    }

    public function getJobStats() {
        return $this->jobStats ;
    }
}