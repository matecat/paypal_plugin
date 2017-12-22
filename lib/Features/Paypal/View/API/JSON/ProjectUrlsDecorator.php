<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 20/12/2017
 * Time: 12:08
 */

namespace Features\Paypal\View\API\JSON;


use API\V2\Json\ProjectUrls;
use Features\Paypal\Utils\Routes;


class ProjectUrlsDecorator {

    public function __construct( ProjectUrls $projectUrls ) {
        $this->projectUrls = $projectUrls;
    }

    public function render() {

        $jobs_lqa_urls  = [];
        $jobs_passwords = [];

        $render = $this->projectUrls->render();
        $data   = $this->projectUrls->getData();

        foreach ( $data as $key => $record ) {
            $jobs_lqa_urls[ $record[ 'jid' ] ]  = $this->lqaUrl( $record );
            $jobs_passwords[ $record[ 'jid' ] ] = $record[ 'jpassword' ];

        }

        foreach ( $render[ 'urls' ][ 'jobs' ] as &$job ) {
            if ( in_array( $job[ 'id' ], array_keys( $jobs_lqa_urls ) ) ) {
                foreach ( $job[ 'chunks' ] as &$chunk ) {
                    if ( $chunk[ 'password' ] == $jobs_passwords[ $job[ 'id' ] ] ) {
                        $chunk[ 'lqa_url' ] = $jobs_lqa_urls[ $job[ 'id' ] ];
                    }
                }
            }
        }

        return $render;

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