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
        $this->appendValidator( new LoginValidator( $this ) );
    }

    public function create()
    {

        $projectValidator = new ProjectPasswordValidator($this);
        $this->appendValidator($projectValidator)->validateRequest();

        $emails = json_decode($this->params['emails']);
        if (!is_array($emails)) {
            throw new ValidationError("Param 'emails' is not a list", -1);
        }

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)){
                throw new ValidationError($email." is not a valid email address", -2);
            }
        }

        $this->project = $projectValidator->getProject();

        $metadata = new Projects_MetadataDao;
        $data = $metadata->set($this->project->id, Constants::PAYPAL_WHITELIST_KEY, json_encode($emails));

        $this->response->json(['code' => 1, 'data' => $data]);
    }

    public function delete()
    {
        $projectValidator = new ProjectPasswordValidator($this);
        $this->appendValidator($projectValidator)->validateRequest();

        $this->project = $projectValidator->getProject();

        $metadata = new Projects_MetadataDao;
        if(!$metadata->get($this->project->id, Constants::PAYPAL_WHITELIST_KEY)) {
            throw new NotFoundException("No emails found for this project", -1);
        }
        $metadata->delete($this->project->id, Constants::PAYPAL_WHITELIST_KEY);
        $this->response->json(['code' => 1, 'data' => true]);
    }
}