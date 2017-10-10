<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 22/12/2016
 * Time: 11:53
 */

namespace Features\Paypal\Decorator;


class NewProjectDecorator extends \AbstractDecorator {


    /**
     * @var \PHPTALWithAppend
     */
    protected $template;

    public function decorate() {
        $this->template->tag_projection_enabled = false;
    }

}