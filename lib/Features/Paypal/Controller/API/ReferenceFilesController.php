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

    protected function afterConstruct() {
        $this->appendValidator( new TranslatorsWhitelistAccessValidator( $this ) );
        $this->appendValidator( new LoginValidator( $this ) );
    }

    public function flushStream() {

        $projectValidator = new ProjectPasswordValidator( $this );
        $this->appendValidator( $projectValidator )->validateRequest();

        $this->project = $projectValidator->getProject();

        list( $fileName, $filePointer, $mimeType ) = array_values(
                ( new ZipArchiveReference() )->getFileStreamPointerInfo( $this->project, $this->params[ 'file_name_in_zip' ] )
        );

        ( new KleinResponseFileStream( $this->response ) )->streamFileInlineFromPointer( $filePointer, $fileName, $mimeType );

    }

    public function getReferenceFolder(){

        $jobValidator = new JobPasswordValidator( $this );
        $this->appendValidator( $jobValidator )->validateRequest();

        $zipArchiveRef = new ZipArchiveReference();
        list( $fileName, $filePointer, $mimeType ) = array_values(
                $zipArchiveRef->getDirectoryStreamFilePointer( $jobValidator->getJob()->getProject(), '__reference' )
        );

        ( new KleinResponseFileStream( $this->response ) )->streamFileInlineFromPointer( $filePointer, $fileName, $mimeType );

    }

    public function getReferenceFolderList(){

        $jobValidator = new JobPasswordValidator( $this );
        $this->appendValidator( $jobValidator )->validateRequest();

        $zipArchiveRef = new ZipArchiveReference();
        $referenceFileList = $zipArchiveRef->getListTree( $jobValidator->getJob()->getProject(), '__reference' );
        $this->response->json( [ 'reference' => $referenceFileList ] );

    }

}