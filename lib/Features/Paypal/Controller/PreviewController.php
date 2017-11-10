<?php

/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 3/18/16
 * Time: 2:48 PM
 */

namespace Features\Paypal\Controller;

use Analysis_AnalysisModel ;
use API\V2\Validators\LoginValidator;
use Features\Paypal\Controller\API\Validators\WhitelistAccessValidator;
use Features\Paypal\Decorator\PreviewDecorator;
use PHPTALWithAppend;

class PreviewController extends \BaseKleinViewController {

    /**
     * @var \PHPTAL ;
     */
    protected $view;

    protected $model ;

    public function afterConstruct() {
        $this->appendValidator( new WhitelistAccessValidator( $this ) );
        $this->appendValidator( new LoginValidator( $this ) );
    }

    public function setView( $template_name ) {
        $this->view = new PHPTALWithAppend( $template_name );
    }

    /**
     * @param $method
     */
    public function respond( $method = null ) {
        $decorator = new PreviewDecorator( $this->model );
        $this->performValidations();
        $this->setDefaultTemplateData() ;
        $decorator->decorate( $this->view );
        $this->response->body( $this->view->execute() );
        $this->response->send();
    }



}