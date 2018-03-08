<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 16/02/2018
 * Time: 16:19
 */

namespace Features\Paypal\Controller\API;

use API\V2\KleinController;
use API\V2\Validators\JobPasswordValidator;
use API\V2\Exceptions\NotFoundException;
use Features\Paypal\View\API\JSON\SegmentTranslationIssue;
use API\V2\Exceptions\ValidationError;


class JobController extends KleinController {

    /**
     * @var \Projects_ProjectStruct
     */
    protected $project;

    protected function afterConstruct() {
        $jobValidator = ( new JobPasswordValidator( $this ) );

        $jobValidator->onSuccess( function () use ( $jobValidator ) {
            $this->job     = $jobValidator->getJob();
            $this->project = $this->job->getProject();
        } );

        $this->appendValidator( $jobValidator );
    }

    public function getInstructions() {

        $fileStorage = new \FilesStorage();
        $zipDir      = $fileStorage->getOriginalZipDir( $this->project->create_date, $this->project->id );
        $filePath    = $zipDir . "/instructions.txt";
        if ( file_exists( $filePath ) ) {
            $this->response->json( [ 'data' => file_get_contents( $filePath ) ] );
        } else {
            throw new NotFoundException( "No instructions found for this project", -1 );
        }
    }

    public function getSegments() {

        $segments_id = filter_var( $this->request->segments_id, FILTER_VALIDATE_INT,FILTER_FORCE_ARRAY );

        $segments = \Translations_SegmentTranslationDao::getSegmentsWithIssues($this->job->id, $segments_id);

        $json     = new SegmentTranslationIssue();
        $rendered = $json->render( $segments );

        $this->response->json( array( 'data' => $rendered ) );
    }

    public function changeSegmentsStatus() {

        $segments_id = filter_var( $this->request->segments_id, FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY );
        $status      = strtoupper( $this->request->status );
        if ( in_array( $status, [ \Constants_TranslationStatus::STATUS_TRANSLATED, \Constants_TranslationStatus::STATUS_APPROVED ] ) ) {
            $unchangeble_segments = \Translations_SegmentTranslationDao::getUnchangebleStatus( $segments_id, $status );

            $segments_id = array_diff( $segments_id, $unchangeble_segments );

            if ( !empty( $segments_id ) ) {
                \Translations_SegmentTranslationDao::changeStatusBySegmentsIds( $this->job, $segments_id, $status );
            }

            $this->response->json( [ 'data' => true, 'unchangeble_segments' => $unchangeble_segments ] );
        }
    }

}
