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

use pingidentity\opentoken\Agent ;

class PayPalController extends BaseKleinViewController {

    public function response() {
        $agent = new Agent();
        $opentoken_values = $agent->readTokenFromHTTPRequest();
        $opentoken_valuesMultiStringArray = $agent->readTokenFromHTTPRequestToMultiStringArray();

        PayPal::staticLogger()->info( 'readTokenFromHTTPRequest',
                ['response' => $opentoken_values ]
        ) ;

        PayPal::staticLogger()->info( 'readTokenFromHTTPRequestToMultiStringArray',
                ['response' => $opentoken_valuesMultiStringArray ]
        ) ;


        PayPal::staticLogger()->info( 'POST params', ['params' => $this->request->paramsPost() ] ) ;
        PayPal::staticLogger()->info( 'GET params',  ['params' => $this->request->paramsGet()  ] ) ;

        // TODO:
        $this->response->body('keep going');
        $this->response->body('https://ssoqa.paypalcorp.com/sp/startSSO.ping?PartnerIdpId=PPSSOALL06_OUD&TargetResource=http://dev.matecat.com/plugins/paypal/saml/login');

    }

}
