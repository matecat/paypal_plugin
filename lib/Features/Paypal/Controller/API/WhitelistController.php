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
use API\V2\Validators\ProjectPasswordValidator;
use Features\Paypal\Controller\API\Validators\TranslatorsWhitelistAccessValidator;
use Projects_MetadataDao;

class WhitelistController extends KleinController {

    const PAYPAL_WHITELIST_KEY = "paypal_whitelist";
    /**
     * @var \Projects_ProjectStruct
     */
    protected $project;

    protected function afterConstruct() {
        $this->appendValidator( new TranslatorsWhitelistAccessValidator( $this ) );
        $this->appendValidator( new LoginValidator( $this ) );
    }

    public function create()
    {

        $projectValidator = new ProjectPasswordValidator($this);
        $this->appendValidator($projectValidator)->validateRequest();

        $emails = json_decode($this->params['emails']);
        if (!is_array($emails)) {
            $this->result['success'] = false;
            $this->result[ 'errors' ][] = array( "code" => -1, "message" => "Param 'emails' is not a list" );
            $this->response->code(400);
            $this->response->json($this->result);
            return false;
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
                $this->result[ 'success' ] = false;
                $this->result[ 'errors' ][] = array( "code" => -2, "message" => $email." is not a valid email address" );
                $this->response->code(400);
                $this->response->json($this->result);
                return false;
            }
        }

        $this->project = $projectValidator->getProject();

        $metadata = new Projects_MetadataDao;
        $data = $metadata->set($this->project->id, self::PAYPAL_WHITELIST_KEY, json_encode($emails));

        $this->result[ 'success' ] = true;
        $this->result['code'] = 1;
        $this->result['data'] = $data;
        $this->response->json($this->result);
    }

    public function delete()
    {
        $projectValidator = new ProjectPasswordValidator($this);
        $this->appendValidator($projectValidator)->validateRequest();

        $this->project = $projectValidator->getProject();

        $metadata = new Projects_MetadataDao;
        if(!$metadata->get($this->project->id, self::PAYPAL_WHITELIST_KEY))
        {
            $this->result[ 'success' ] = false;
            $this->result[ 'errors' ][] = array( "code" => -1, "message" => "No emails found for this project");
            $this->response->json($this->result);
            return false;
        }
        $metadata->delete($this->project->id, self::PAYPAL_WHITELIST_KEY);
        $this->result[ 'success' ] = true;
        $this->result['code'] = 1;
        $this->result['data'] = "Done";
        $this->response->json($this->result);

    }
}