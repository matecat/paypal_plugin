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
use API\V2\Validators\ProjectPasswordValidator;
use ZipArchiveReference;

class ReferenceFilesController extends KleinController {

    /**
     * @var \Projects_ProjectStruct
     */
    protected $project;

    /**
     * @var ProjectPasswordValidator
     */
    protected $projectValidator;

    protected function afterConstruct() {
        $this->projectValidator = new ProjectPasswordValidator( $this );
        $this->appendValidator( $this->projectValidator );
    }

    public function flushStream() {

        $this->project = $this->projectValidator->getProject();

        list( $fileName, $filePointer, $mimeType ) = array_values(
                ( new ZipArchiveReference() )->getFileStreamPointerInfo( $this->project, $this->params[ 'file_name_in_zip' ] )
        );

        ( new KleinResponseFileStream( $this->response ) )->streamFileInlineFromPointer( $filePointer, $fileName, $mimeType );

    }

}