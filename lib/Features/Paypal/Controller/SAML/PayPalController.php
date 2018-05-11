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

        $logger->info( 'POST params', ['params' => $this->request->paramsPost() ] ) ;
        $logger->info( 'GET params',  ['params' => $this->request->paramsGet()  ] ) ;

        $auth = new \OneLogin_Saml2_Auth( $this->getSamlSettings() ); // Constructor of the SP, loads settings.php
        $auth->processResponse();

        $errors = $auth->getErrors();

        $logger->info('errors', $auth->getErrors() );

        if (!empty($errors)) {
            echo '<p>',implode(', ', $errors),'</p>';
        }

        if (!$auth->isAuthenticated()) {
            $logger->info('Not authenticated') ;
            echo "<p>Not authenticated</p>";
            exit();
        }

        $logger->info( 'uniqueID is:', ['uniqueID' => \Log::$uniqID ] );
        $logger->info( 'getAttributes', $auth->getAttributes() ) ;

        $attributes = $auth->getAttributes() ;

        // At some point when the attributes are validated we will have
        // First name
        // Last name
        // email
        // so we can process the sign in
        $firstName = $attributes['FirstName'][0]  ;
        $lastName  = $attributes['LastName'][0] ;
        $email     = $attributes['Email'][0] ;

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
        $config = Paypal::getConfig() ;

        $settings = array (
                'strict' => true,
                'security' => [
                        'authnRequestsSigned' => true
                ],
                'sp' => array (
                        'entityId' => $config['SP_ENTITY_ID'],
                        'assertionConsumerService' => array (
                                'url' => Routes::samlConsumer(),
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                        ),
                        'singleLogoutService' => array (
                                'url' => Routes::samlOwnLoginURL(),
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        ),
                        'NameIDFormat' => \OneLogin_Saml2_Constants::NAMEID_TRANSIENT,
                        'x509cert'   => file_get_contents( realpath( Paypal::getPluginBasePath() . '/../config/cert.pem' ) ),
                        'privateKey' => file_get_contents( realpath( Paypal::getPluginBasePath() . '/../config/privkey.pem' ) ),
                ),
                'idp' => array (
                        'entityId' => $config['IDP_ENTITY_ID'],
                        'singleSignOnService' => array (
                                'url' => $config['IDP_SSO_URL'],
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        ),
                        'singleLogoutService' => array (
                                'url' => $config['IDP_SLO_URL'],
                                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                        ),
                        'x509cert' => file_get_contents( realpath( Paypal::getPluginBasePath() . '/../config/idp.pem' ) )
                ),
        );

        return $settings ;
    }

}
