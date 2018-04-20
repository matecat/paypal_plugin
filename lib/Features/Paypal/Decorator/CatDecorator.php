<?php

namespace Features\Paypal\Decorator;


use AbstractDecorator;
use catController;
use Chunks_ChunkCompletionEventDao;
use Constants_TranslationStatus;
use Features\Paypal\Utils\Routes;

class CatDecorator extends AbstractDecorator {
    /**
     * @var \PHPTALWithAppend
     */
    protected $template;

    public function decorate() {
        $this->template->append( 'footer_js', Routes::staticSrc( 'build/paypal-components-build.js' ) );
        $this->template->append( 'footer_js', Routes::staticSrc( 'build/paypal-core-build.js' ) );
        $this->template->append( 'css_resources', Routes::staticSrc( 'build/paypal-core-build.css' ) );
        $this->template->splitSegmentEnabled    = var_export( false, true );
        $this->template->allow_link_to_analysis = false;
        $this->template->lxq_enabled            = 0;

        //do not show Tag Projection
        $this->template->show_tag_projection = false;

        $this->stats = $this->controller->getJobStats();

        $dao                 = new \Chunks_ChunkCompletionEventDao();
        $this->current_phase = $dao->currentPhase( $this->controller->getChunk() );

        $job = $this->controller->getChunk();

        $completed = $job->isMarkedComplete( array( 'is_review' => $this->controller->isRevision() ) );

        if ( $completed ) {
            $this->varsForComplete();
        } else {
            $this->varsForUncomplete();
        }

        /*$metadata     = new \Projects_MetadataDao;
        $project      = $this->controller->getProject();
        $project_type = $metadata->get( $project->id, "project_type" );
        if ( $project_type->value == "LR" ) {*/
        if ( $this->controller->isRevision() ) {
            $this->template->footer_show_translate_link = false;
        }

        $this->template->searchable_statuses = $this->searchableStatuses();

    }

    private function varsForUncomplete() {
        $this->template->job_marked_complete      = false;
        $this->template->header_main_button_label = 'Mark as complete';
        $this->template->header_main_button_class = 'notMarkedComplete';

        if ( $this->completable() ) {
            $this->template->header_main_button_enabled = true;
            $this->template->header_main_button_class   = " isMarkableAsComplete";
        } else {
            $this->template->header_main_button_enabled = false;
        }
    }

    private function varsForComplete() {
        $this->template->job_marked_complete        = true;
        $this->template->header_main_button_label   = 'Marked as complete';
        $this->template->header_main_button_class   = 'isMarkedComplete';
        $this->template->header_main_button_enabled = false;
    }

    private function completable() {

        if ($this->controller->isRevision()) {
            $completable = $this->current_phase == Chunks_ChunkCompletionEventDao::REVISE &&
                    $this->stats['DRAFT'] == 0 &&  $this->stats['TRANSLATED'] == 0 ;
        }
        else {
            $completable = $this->current_phase == Chunks_ChunkCompletionEventDao::TRANSLATE &&
                    $this->stats['DRAFT'] == 0 ;
        }

        $completable = $this->controller->getChunk()->getProject()->getFeatures()->filter('filterJobCompletable', $completable,
                $this->controller->getChunk(),
                $this->controller->getUser(),
                catController::isRevision()
        );

        return $completable ;
    }

    /**
     * @return array
     */
    private function searchableStatuses() {
        $statuses = array_merge(
                Constants_TranslationStatus::$INITIAL_STATUSES,
                Constants_TranslationStatus::$TRANSLATION_STATUSES,
                array(Constants_TranslationStatus::STATUS_APPROVED)
        );

        return array_map( function ( $item ) {
            return (object)array( 'value' => $item, 'label' => $item );
        }, $statuses );
    }

}