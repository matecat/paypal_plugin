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

    /**
     * TODO Implement Rules For Access
     */
    public function validate() {

        $user    = $this->controller->getUser();
        $project = $this->controller->getProject();

        $membership_dao = new MembershipDao;
        if($project != null){ //i'm watching a project
            if (! $membership_dao->findTeamByIdAndUser( $project->id_team, $user ) ) { // not is in team

                $metadata_dao = new Projects_MetadataDao;
                $metadata     = $metadata_dao->get( $project->id, Constants::PAYPAL_WHITELIST_KEY );
                $metadata_value = json_decode($metadata->value);

                if ( !in_array( $user->getEmail(), $metadata_value->emails ) ) { // not is in whitelist
                    throw new AuthenticationError( "Nein" );
                }
            }
        }


    }

}