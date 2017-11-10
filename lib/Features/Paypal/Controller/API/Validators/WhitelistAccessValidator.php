<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 21/09/17
 * Time: 15.38
 *
 */

namespace Features\Paypal\Controller\API\Validators;

use API\V2\KleinController;
use API\V2\Validators\Base;
use Exceptions\NotFoundError;

class WhitelistAccessValidator extends Base {

    /**
     * @var KleinController
     */
    protected $controller;

    public function __construct( KleinController $controller ) {

        parent::__construct( $controller->getRequest() );
        $this->controller = $controller;

    }

    /**
     * TODO Implement Rules
     */
    public function validate() {
        \Log::doLog( "TODO Implements whitelist rules" );
        trigger_error( "TODO Implements whitelist rules", E_USER_WARNING );
    }

}