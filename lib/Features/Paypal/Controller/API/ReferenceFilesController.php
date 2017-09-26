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
use ZipArchiveReference;

class ReferenceFilesController extends KleinController {

    /**
     * @var \Projects_ProjectStruct
     */
    protected $project;

    protected function afterConstruct() {
        $this->findProject();
    }

    public function flushStream() {

        list( $fileName, $filePointer, $mimeType ) = array_values(
                ( new ZipArchiveReference() )->getFileStreamPointerInfo( $this->project, $this->params[ 'file_name_in_zip' ] )
        );

        ( new KleinResponseFileStream( $this->response ) )->streamFileInlineFromPointer( $filePointer, $fileName, $mimeType );

    }

    private function findProject() {
        $this->project = \Projects_ProjectDao::findByIdAndPassword(
                $this->params[ 'id_project' ],
                $this->params[ 'password' ]
        );
    }

}