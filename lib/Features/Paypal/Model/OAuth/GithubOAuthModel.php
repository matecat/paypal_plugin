<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 23/02/2018
 * Time: 16:09
 */

namespace Features\Paypal\Model\OAuth ;

use CatUtils;
use ConnectedServices\ConnectedServiceDao;
use ConnectedServices\ConnectedServiceStruct;
use Exception;
use Features\Paypal;
use INIT;
use Log;
use MultiCurlHandler;
use OAuthSignInModel;
use Users_UserDao;
use Users_UserStruct;
use Utils;

class GithubOAuthModel {

    protected $user ;

    protected $userInfo ;
    protected $token ;

    protected $user_email ;
    protected $user_name ;

    protected $remote_name ;
    protected $remote_email ;
    protected $remote_id ;

    const CONNECTED_SERVICE_NAME = 'github' ;

    public function __construct( ) {

    }

    public function updateOrCreateRecordByCode( $code ) {
        $this->__collectProperties( $code );

        list( $firstName, $lastName ) = explode(' ', $this->remote_name, 1) ;

        if ( is_null( $lastName ) ) {
            $lastName = '-';
        }

        $signIn = new OAuthSignInModel( $firstName, $lastName, $this->remote_email );
        $signIn->setAccessToken( $this->token );
        $signIn->signIn() ;

        $this->user = $signIn->getUser();

        // ok the user is created and set, what we miss is to create the corresponding service record

        $dao = new ConnectedServiceDao();
        $service = $dao->findByRemoteIdAndCode( $this->remote_id, self::CONNECTED_SERVICE_NAME ) ;

        if ( $service ) {
            $this->__updateService($service);
        }
        else {
            $service = $this->__insertService();
        }

        // $dao->setDefaultService( $service );
    }

    /**
     * @param ConnectedServiceStruct $service
     *
     * @throws \Exceptions\ValidationError
     */
    private function __updateService( ConnectedServiceStruct $service ) {
        $dao = new ConnectedServiceDao() ;
        $dao->updateOauthToken( $this->token, $service ) ;

        $service->disabled_at = null;
        $dao->updateStruct( $service ) ;
    }

    private function __insertService() {
        $service = new ConnectedServiceStruct(array(
                'uid'        => $this->user->uid,
                'email'      => $this->remote_email,
                'name'       => $this->remote_name,
                'service'    => self::CONNECTED_SERVICE_NAME,
                'remote_id'  => $this->remote_id,
                'is_default' => 0,
                'created_at' => Utils::mysqlTimestamp( time() )
        ));
        $service->setEncryptedAccessToken( $this->token ) ;
        $dao = new ConnectedServiceDao();

        $lastId = $dao->insertStruct( $service ) ;

        return $dao->findById( $lastId ) ;
    }

    private function __collectProperties( $code ) {
        $curl = new MultiCurlHandler();

        $config = Paypal::getConfig() ;

        $params = [
                'client_id'     => $config['GITHUB_OAUTH_CLIENT_ID'],
                'client_secret' => $config['GITHUB_OAUTH_CLIENT_SECRET'],
                'code'          => $code,
                'redirect_uri'  => Paypal\Utils\Routes::githubOauth(),
                'state'         => $_SESSION['paypal_github_oauth_state']
        ];

        $curlOptions = [
                CURLOPT_HTTPHEADER  => [
                        'Accept: application/json'
                ],
                CURLOPT_USERAGENT        => INIT::MATECAT_USER_AGENT,
                CURLOPT_HEADER           => 0,
                CURLOPT_RETURNTRANSFER   => true,
                CURLOPT_SSL_VERIFYPEER   => true,
                CURLOPT_SSL_VERIFYHOST   => 2,
                CURLOPT_POST             => true,
                CURLOPT_POSTFIELDS       => $params ,
                CURLOPT_TIMEOUT          => 10,
                CURLOPT_CONNECTTIMEOUT   => 5,
        ];

        $resource = $curl->createResource( 'https://github.com/login/oauth/access_token', $curlOptions );

        $curl->multiExec();

        if ( $curl->hasError( $resource ) ) {
            $error = $curl->getError( $resource );
            Log::doLog('OAUTH ERROR ' . var_export( $error, true ) );
            throw new Exception('Error while trying to get response token', 500);
        }

        $response = $curl->getSingleContent( $resource );
        $parsed_response = json_decode( $response, true );
        $curl->multiCurlCloseAll();

        $this->token = $parsed_response['access_token'] ;

        $this->__getUserInfo();
    }

    private function __getUserInfo() {
        $curl = new MultiCurlHandler();

        $curlOptions = [
                CURLOPT_HTTPHEADER  => [
                        'Accept: application/json',
                        'Authorization: token ' . $this->token
                ],
                CURLOPT_USERAGENT        => INIT::MATECAT_USER_AGENT,
                CURLOPT_HEADER           => 0,
                CURLOPT_RETURNTRANSFER   => true,
                CURLOPT_SSL_VERIFYPEER   => true,
                CURLOPT_SSL_VERIFYHOST   => 2,
                CURLOPT_HTTPGET          => true,
                CURLOPT_TIMEOUT          => 10,
                CURLOPT_CONNECTTIMEOUT   => 5,
        ];

        $resource = $curl->createResource('https://api.github.com/user', $curlOptions ) ;

        $curl->multiExec();
        $curl->multiCurlCloseAll() ;

        if ( $curl->hasError( $resource ) ) {
            $error = $curl->getError( $resource );
            Log::doLog('OAUTH ERROR ' . var_export( $error, true ) );
            throw new Exception('Error while gettitng user data', 500 );
        }

        $response = $curl->getSingleContent( $resource );

        $user = json_decode( $response, true ) ;

        $this->remote_email = $user[ 'email' ] ;
        $this->remote_name  = $user[ 'name' ] ;
        $this->remote_id    = $user[ 'id' ] ;
    }
}
