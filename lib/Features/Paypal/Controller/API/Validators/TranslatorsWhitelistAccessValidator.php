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
use Projects_MetadataDao;
use Features\Paypal\Utils\Constants;
use Teams\MembershipDao;


class TranslatorsWhitelistAccessValidator extends WListAccessValidator {


    public function validate() {

        $user    = $this->controller->getUser();

        /**
         * WARNING the controllers used by this validator must have the project loaded and a method getProject to get it
         */
        $project = $this->controller->getProject();

        $membership_dao = new MembershipDao;
        /*
         * $project is null only when controller is supposed to handle multiple projects,
         * in the other cases the user must to be validated when bumps into a project
         */
        if ( $project != null ) {
            if ( !$membership_dao->findTeamByIdAndUser( $project->id_team, $user ) ) { // not is in team

                $metadata_dao   = new Projects_MetadataDao;
                $metadata       = $metadata_dao->get( $project->id, Constants::PAYPAL_METADATA_KEY );
                $metadata_value = json_decode( $metadata->value );

                if ( !in_array( $user->getEmail(), $metadata_value->emails ) ) { // not is in whitelist
                    throw new AuthenticationError( "You are not in team or in whitelist" );
                }
            }

        }

    }

}