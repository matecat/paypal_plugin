<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 20/12/2017
 * Time: 12:08
 */

namespace Features\Paypal\View\API\JSON;


use \Features\ReviewExtended\View\API\JSON\ProjectUrlsDecorator as UrlDecorator;
use Features\Paypal\Utils\Routes;


class ProjectUrlsDecorator extends UrlDecorator {

    protected function generateChunkUrls( $record){

        if ( !array_key_exists( $record['jpassword'], $this->chunks ) ) {
            $this->chunks[ $record['jpassword'] ] = 1 ;
            $this->jobs[ $record['jid'] ][ 'chunks' ][] = array(
                    'password'      => $record['jpassword'],
                    'translate_url' => $this->translateUrl( $record ),
                    'revise_url'    => $this->reviseUrl( $record ),
                    'lqa_url'       => $this->lqaUrl( $record )
            );
        }

    }

    public function lqaUrl( $record ) {

        //http://dev.matecat.com/lqa/proj_name/it-IT-es-ES/591-b90581eb0471
        return Routes::lqa(
                $record[ 'name' ],
                $record[ 'jid' ],
                $record[ 'jpassword' ],
                $record[ 'source' ],
                $record[ 'target' ]
        );
    }

}