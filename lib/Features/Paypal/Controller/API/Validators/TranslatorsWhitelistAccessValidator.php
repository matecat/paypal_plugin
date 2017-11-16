<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 21/09/17
 * Time: 15.38
 *
 */

namespace Features\Paypal\Controller\API\Validators;

use API\V2\Exceptions\AuthenticationError;
use API\V2\Validators\WhitelistAccessValidator as WListAccessValidator;
use Users\MetadataDao;


class TranslatorsWhitelistAccessValidator extends WListAccessValidator {

    /**
     * TODO Implement Rules For Access
     */
    public function validate() {

        $user = $this->controller->getUser();
        if( stripos( $user->getEmail(), '@translated.net' ) === false ){ //TODO implement a better rule
            throw new AuthenticationError("Nein");
        }

    }

}