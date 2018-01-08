<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 15/04/16
 * Time: 11:40
 */

namespace Features\Paypal\Controller\API;

use API\V2\KleinController;
use API\V2\Validators\LoginValidator;
use API\V2\Exceptions\ValidationError;
use API\V2\Exceptions\NotFoundException;
use API\V2\Validators\ProjectPasswordValidator;
use Features\Paypal\Utils\Constants;
use Projects_MetadataDao;

class WhitelistController extends KleinController {

    /**
     * @var \Projects_ProjectStruct
     */
    protected $project;

    protected function afterConstruct() {
        $projectValidator = (new ProjectPasswordValidator( $this ));

        $projectValidator->onSuccess(function() use ($projectValidator){
            $this->project = $projectValidator->getProject();
        });
        $this->appendValidator( $projectValidator )->validateRequest();
    }

    public function create() {

        $emails = json_decode( $this->params[ 'emails' ] );
        if ( !is_array( $emails ) ) {
            throw new ValidationError( "Param 'emails' is not a list", -1 );
        }

        foreach ( $emails as $email ) {
            if ( !filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
                throw new ValidationError( $email . " is not a valid email address", -2 );
            }
        }

        $metadata = new Projects_MetadataDao;
        if ( $metadata_row = $metadata->get( $this->project->id, Constants::PAYPAL_METADATA_KEY ) ) {
            $metadata_value             = json_decode( $metadata_row->value, true );
            $metadata_value[ 'emails' ] = $emails;
        } else {
            $metadata_value = [ 'emails' => $emails ];
        }

        $data = $metadata->set( $this->project->id, Constants::PAYPAL_METADATA_KEY, json_encode( $metadata_value ) );

        $this->response->json( [ 'code' => 1, 'data' => $data ] );
    }

    public function delete() {

        $metadata = new Projects_MetadataDao;

        if ( $metadata_row = $metadata->get( $this->project->id, Constants::PAYPAL_METADATA_KEY ) ) {
            $metadata_value = json_decode( $metadata_row->value, true );
            unset( $metadata_value[ 'emails' ] );
            if ( !empty( $metadata_value ) ) {
                $metadata->set( $this->project->id, Constants::PAYPAL_METADATA_KEY, json_encode( $metadata_value ) );
            } else {
                $metadata->delete( $this->project->id, Constants::PAYPAL_METADATA_KEY );
            }

            $this->response->json( [ 'code' => 1, 'data' => true ] );
        } else {
            throw new NotFoundException( "No emails found for this project", -1 );
        }

    }
}