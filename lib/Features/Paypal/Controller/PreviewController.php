<?php

/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 3/18/16
 * Time: 2:48 PM
 */

namespace Features\Paypal\Controller;

use API\V2\Validators\ChunkPasswordValidator;
use API\V2\Validators\LoginValidator;
use Features\Paypal\Controller\API\Validators\TranslatorsWhitelistAccessValidator;
use Features\Paypal\Decorator\PreviewDecorator;
use PHPTALWithAppend;
use Projects_ProjectStruct;

class PreviewController extends \BaseKleinViewController {

    /**
     * @var \PHPTAL ;
     */
    protected $view;

    protected $model ;

    protected $project;

    protected $chunk;

    public function afterConstruct() {
        $chunkPasswordValidator = new ChunkPasswordValidator( $this );
        $chunkPasswordValidator->onSuccess( function() use ( $chunkPasswordValidator ) {
            $this->setProject( $chunkPasswordValidator->getChunk()->getProject( 60 * 60 ) );
            $this->setChunk( $chunkPasswordValidator->getChunk() );
        });
        $this->appendValidator( $chunkPasswordValidator );
        $this->appendValidator( new LoginValidator( $this ) );
        $this->appendValidator( new TranslatorsWhitelistAccessValidator( $this ) );
    }

    public function setView( $template_name ) {
        $this->view = new PHPTALWithAppend( $template_name );
    }

    public function getProject(){
        return $this->project;
    }

    public function setProject( Projects_ProjectStruct $project ){
        $this->project = $project;
        return $this;
    }

    /**
     * @param mixed $chunk
     */
    public function setChunk( $chunk ) {
        $this->chunk = $chunk;
    }

    public function composeView(){
        $decorator = new PreviewDecorator( $this->chunk );
        $this->setDefaultTemplateData() ;
        $decorator->decorate( $this->view );

        $this->response->body( $this->view->execute() );
        $this->response->send();

    }

}