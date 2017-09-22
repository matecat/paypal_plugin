<?php

/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 3/18/16
 * Time: 2:48 PM
 */

namespace Features\Paypal\Controller;

use Analysis_AnalysisModel ;
use Features\Paypal\Decorator\PreviewDecorator;
use PHPTALWithAppend;

class PreviewController extends \BaseKleinViewController {

    /**
     * @var \PHPTAL ;
     */
    protected $view;

    protected $model ;


    public function setView( $template_name ) {
        $this->view = new PHPTALWithAppend( $template_name );
    }


    public function respond() {
        $decorator = new PreviewDecorator( $this->model );

//        $decorator->setUser( $this->currentUser() ) ;
        $this->setLoggedUser() ;

        $this->setDefaultTemplateData() ;

        $decorator->decorate( $this->view );


        $this->response->body( $this->view->execute() );
        $this->response->send();
    }



}