<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 23/02/2018
 * Time: 15:41
 */

namespace Features\Paypal\Controller\OAuth;


use BaseKleinViewController;
use Features\Paypal\Model\OAuth\GithubOAuthModel;

class GithubOAuthController extends BaseKleinViewController {

    public function response() {
        $code  = $this->request->param( 'code' );
        $error = $this->request->param( 'error' );

        if ( isset($code) && $code ) {
            $this->__handleCode( $code ) ;
        } else if ( isset( $error ) ) {
            $this->__handleError( $error );
        }

        $body = <<<EOF
<html><head>
<script> window.close(); </script>
</head>
</html>
EOF;

        $this->response->body( $body );
    }

    private function __handleError( $error ) {
        // TODO:
    }

    private function __handleCode( $code ) {
        // use code to get an authorization token
        $model = new GithubOAuthModel( );
        $model->updateOrCreateRecordByCode( $code ) ;
    }

    protected function afterConstruct() {

    }

}