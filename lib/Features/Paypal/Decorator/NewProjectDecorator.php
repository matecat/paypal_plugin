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
        //do not show Tag Projection
        $this->template->show_tag_projection = false;
    }

}