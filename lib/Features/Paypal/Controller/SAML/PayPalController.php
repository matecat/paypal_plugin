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
use OneLogin_Saml2_Auth;
use pingidentity\opentoken\agent ;


class PayPalController extends BaseKleinViewController {

    /**
     * This is for opentoken
     */
    public function login() {
        $agent = new agent();
        $opentoken_values = $agent->readTokenFromHTTPRequest();
        $opentoken_valuesMultiStringArray = $agent->readTokenFromHTTPRequestToMultiStringArray();

        PayPal::staticLogger()->info( 'readTokenFromHTTPRequest',
                ['response' => $opentoken_values ]
        ) ;

        PayPal::staticLogger()->info( 'readTokenFromHTTPRequestToMultiStringArray',
                ['response' => $opentoken_valuesMultiStringArray ]
        ) ;

        PayPal::staticLogger()->debug( 'POST params', ['params' => $this->request->paramsPost() ] ) ;
        PayPal::staticLogger()->debug( 'GET params',  ['params' => $this->request->paramsGet()  ] ) ;
        Paypal::staticLogger()->debug( 'lastError',   ['error'  => $agent->lastError ] );

        // TODO:
        $this->response->body('keep going');
        $this->response->body('https://ssoqa.paypalcorp.com/sp/startSSO.ping?PartnerIdpId=PPSSOALL06_OUD&TargetResource=http://dev.matecat.com/plugins/paypal/saml/login');
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
                ),
        );

        return $settings ;
    }

}
