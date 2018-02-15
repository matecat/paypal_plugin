<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 15/02/2018
 * Time: 13:10
 */

namespace Features\Paypal\Decorator;

use AbstractDecorator;
use Chunks_ChunkStruct;
use Features\Dqf\Utils\Functions;
use Features\Paypal\Controller\LqaController;
use INIT;

class LqaDecorator extends AbstractDecorator {

    /**
     * @var LqaController
     */
    protected $controller ;
    /**
     * @var \PHPTALWithAppend
     */
    protected $template ;

    protected $project ;

    protected $chunk ;

    public function decorate() {
        $this->chunk = new Chunks_ChunkStruct( $this->controller->getJobValidator()->getJob()->toArray() );
        $this->project = $this->chunk->getProject() ;

        $this->template->basepath     = INIT::$BASEURL ;
        $this->template->build_number = INIT::$BUILD_NUMBER;

        $this->template->isReview = true ;
        $this->template->pname    = $this->project->name ;

        $this->template->create_date = $this->chunk->create_date ;
        $this->template->jid         = $this->chunk->id ;
    }

}