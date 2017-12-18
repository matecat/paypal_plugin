<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 15/04/16
 * Time: 11:40
 */

namespace Features\Paypal\Controller\API;

use API\V2\KleinController;
use API\V2\KleinResponseFileStream;
use API\V2\Validators\JobPasswordValidator;
use API\V2\Validators\LoginValidator;
use API\V2\Validators\ProjectPasswordValidator;
use Features\Paypal\Controller\API\Validators\TranslatorsWhitelistAccessValidator;
use ZipArchiveReference;

class ReferenceFilesController extends KleinController {

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

    protected function afterConstruct() {
        $this->appendValidator( new LoginValidator( $this ) );
    }

    public function flushStream() {

        $projectValidator = new ProjectPasswordValidator( $this );

        $Controller = $this;
        $projectValidator->onSuccess( function() use( $projectValidator, $Controller ) {
            /**
             * @var $jobValidator JobPasswordValidator
             */
            $Controller->setProject( $projectValidator->getProject() );
        } );

        $this->appendValidator( new TranslatorsWhitelistAccessValidator( $this ) );
        $this->appendValidator( $projectValidator )->validateRequest();

        list( $fileName, $filePointer, $mimeType ) = array_values(
                ( new ZipArchiveReference() )->getFileStreamPointerInfo( $this->project, $this->params[ 'file_name_in_zip' ] )
        );

        ( new KleinResponseFileStream( $this->response ) )->streamFileInlineFromPointer( $filePointer, $fileName, $mimeType );

    }

    public function getReferenceFolder() {

        $jobValidator = new JobPasswordValidator( $this );

        $Controller = $this;
        $jobValidator->onSuccess( function() use( $jobValidator, $Controller ) {
            /**
             * @var $jobValidator JobPasswordValidator
             */
            $Controller->setProject( $jobValidator->getJob()->getProject( 60 * 60 ) );
        } );

        $this->appendValidator( $jobValidator );
        $this->appendValidator( new TranslatorsWhitelistAccessValidator( $this ) )->validateRequest();

        $zipArchiveRef = new ZipArchiveReference();
        list( $fileName, $filePointer, $mimeType ) = array_values(
                $zipArchiveRef->getDirectoryStreamFilePointer( $this->project, '__reference' )
        );

        ( new KleinResponseFileStream( $this->response ) )->streamFileInlineFromPointer( $filePointer, $fileName, $mimeType );

    }

    public function getReferenceFolderList() {

        $jobValidator = new JobPasswordValidator( $this );

        $Controller = $this;
        $jobValidator->onSuccess( function() use( $jobValidator, $Controller ) {
            /**
             * @var $jobValidator JobPasswordValidator
             */
            $Controller->setProject( $jobValidator->getJob()->getProject( 60 * 60 ) );
        } );

        $this->appendValidator( $jobValidator );
        $this->appendValidator( new TranslatorsWhitelistAccessValidator( $this ) )->validateRequest();

        $zipArchiveRef     = new ZipArchiveReference();
        $referenceFileList = $zipArchiveRef->getListTree( $this->project, '__reference' );
        $this->response->json( [ 'reference' => $referenceFileList ] );

    }

}