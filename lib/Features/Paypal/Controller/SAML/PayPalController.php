<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 20/04/2018
 * Time: 15:05
 */

namespace Features\Paypal\Controller\SAML ;

use BaseKleinViewController;
use Features\Paypal;
use Features\Paypal\Utils\Routes;
use OAuthSignInModel;
use OneLogin_Saml2_Auth;


class PayPalController extends BaseKleinViewController {

    /**
     * This is for opentoken
     */
    public function login() {
        $logger = PayPal::staticLogger() ;

        $logger->debug( 'POST params', ['params' => $this->request->paramsPost() ] ) ;
        $logger->debug( 'GET params',  ['params' => $this->request->paramsGet()  ] ) ;

        $auth = new \OneLogin_Saml2_Auth( $this->getSamlSettings() ); // Constructor of the SP, loads settings.php
        $auth->processResponse();

        $errors = $auth->getErrors();

        $logger->debug('errors', $auth->getErrors() );

        if (!empty($errors)) {
            echo '<p>',implode(', ', $errors),'</p>';
        }

        if (!$auth->isAuthenticated()) {
            $logger->debug('Not authenticated') ;
            echo "<p>Not authenticated</p>";
            exit();
        }

        $logger->debug( 'uniqueID is:', ['uniqueID' => \Log::$uniqID ] );
        $logger->debug( 'getAttributes', $auth->getAttributes() ) ;
        $logger->debug( 'getNameId', $auth->getNameId() ) ;
        $logger->debug( 'getNameIdFormat', $auth->getNameIdFormat() ) ;
        $logger->debug( 'getSessionIndex', $auth->getSessionIndex() ) ;

        // At some point when the attributes are validated we will have
        // First name
        // Last name
        // email
        // so we can process the sign in
        $firstName = 'John';
        $lastName = 'Doe' ;
        $email = 'johndoe@example.org' ;

        $signIn = new OAuthSignInModel( $firstName, $lastName, $email );
        $signIn->signIn() ;

        $body = <<<EOF
<html>
<body>
<script>window.close();</script>
</body>
</html>
EOF;
        $this->response->body( $body ) ;

        // TODO: process authentication here
        // TODO: this is supposedly heppning in a modal window so we should close the modal window on successful login

        // if (isset($_POST['RelayState']) && OneLogin_Saml2_Utils::getSelfURL() != $_POST['RelayState']) {
        //     $auth->redirectTo($_POST['RelayState']);
        // }
    }

    public function forward() {
        $auth = new OneLogin_Saml2_Auth( $this->getSamlSettings() ); // Constructor of the SP, loads settings.php
        $auth->login();   // Method that sent the AuthNRequest
    }

    protected function getSamlSettings() {
        $settings = array (
                'strict' => false,
                'sp' => array (
                        'entityId' => 'MateCat',
                        'assertionConsumerService' => array (
                                'url' => Routes::samlConsumer(),
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                        ),
                        'singleLogoutService' => array (
                                'url' => Routes::samlOwnLoginURL(),
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        ),
                        'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
                        'x509cert'   => file_get_contents( realpath( Paypal::getPluginBasePath() . '/../config/cert.pem' ) ),
                        'privateKey' => file_get_contents( realpath( Paypal::getPluginBasePath() . '/../config/privkey.pem' ) ),
                ),
                'idp' => array (
                        'entityId' => 'PayPal',
                        'singleSignOnService' => array (
                                'url' => Routes::samlLoginURL(),
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        ),
                        'singleLogoutService' => array (
                                'url' => Routes::samlLoginURL(),
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        ),
                        'x509cert' => file_get_contents( realpath( Paypal::getPluginBasePath() . '/../config/idp.pem' ) )
                ),
        );

        return $settings ;
    }

}
