<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 27/09/17
 * Time: 12.23
 *
 */

namespace Features\Paypal\Controller\API;

use AbstractControllers\IController;
use API\V2\KleinController;
use API\V2\Validators\Base;
use API\V2\Validators\JobPasswordValidator;
use API\V2\Validators\LoginValidator;
use Features\Paypal\Controller\API\Validators\TranslatorsWhitelistAccessValidator;
use Features\Paypal\Utils\CDataHandler;
use Features\Paypal\View\API\JSON\Preview;
use Jobs\MetadataDao;

class PreviewsStruct extends KleinController {

    /**
     * @var \Projects_ProjectStruct
     */
    protected $project;

    /**
     * @return \Projects_ProjectStruct
     */
    public function getProject() {
        return $this->project;
    }

    /**
     * @param \Projects_ProjectStruct $project
     *
     * @return $this
     */
    public function setProject( $project ) {
        $this->project = $project;

        return $this;
    }

    public function afterConstruct() {
        $this->appendValidator( new LoginValidator( $this ) );
        $jobValidator = new JobPasswordValidator( $this );
        $Controller = $this;
        $jobValidator->onSuccess( function() use( $jobValidator, $Controller ) {
            /**
             * @var $jobValidator JobPasswordValidator
             */
            $Controller->setProject( $jobValidator->getChunk()->getProject( 60 * 60 ) );
        } );

        $this->appendValidator( $jobValidator );
        $this->appendValidator( new TranslatorsWhitelistAccessValidator( $this ) );

    }

    public function getPreviewsStruct() {

        $jobStructs = \Jobs_JobDao::getById( $this->params[ 'id_job' ], 60 * 60 );

        $jobMeta       = new MetadataDao();
        $jobMetaStruct = @$jobMeta->setCacheTTL( 60 * 60 * 24 )->getByIdJob( $this->params[ 'id_job' ], CDataHandler::PREVIEWS_LOOKUP )[ 0 ];

        $notes = \Segments_SegmentNoteDao::getJsonNotesByRange( $jobStructs[ 0 ]->job_first_segment, end( $jobStructs )->job_last_segment, 60 * 60 * 24 );

        $previewObj = ( new Preview() )->renderItem( $jobMetaStruct, $notes );
        $this->response->json( $previewObj );

    }

}