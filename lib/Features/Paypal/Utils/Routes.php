<?php

/**
 * Created by PhpStorm.
 * User: riccio
 * Date: 22/09/17
 * Time: 10.47
 */

namespace Features\Paypal\Utils;


use Features\Paypal;

class Routes {

    public static function staticBuild( $file, $options = [] ) {
        $host = \Routes::pluginsBase( $options );

        return $host . "/paypal/static/build/$file";
    }

    public static function staticSrc( $file, $options = [] ) {
        $host = \Routes::pluginsBase( $options );

        return $host . "/paypal/static/$file";
    }

    public static function projectImageReferences( $projectStructure, $fileName, $options = [] ) {
        $host = \Routes::pluginsBase( $options );

        return [ 'path' => $host . "/paypal/preview/{$projectStructure[ 'id_project' ]}/{$projectStructure[ 'ppassword' ]}/", 'file_index' => $fileName ];
    }

    public static function lqa( $project_name, $id_job, $password, $source, $target, $options = [] ) {
        $host = \Routes::httpHost( $options );

        return "$host/lqa/$project_name/$source-$target/$id_job-$password";

    }

    public static function samlConsumer() {
        $host = \Routes::pluginsBase( [] );
        return "$host/paypal/saml/login" ;
    }

    public static function samlLoginURL( $params = [] ) {
        $config = PayPal::getConfig();

        $params['PartnerIdpId'] = $config['PartnerIdpId'];
        $params['TargetResource'] = self::samlConsumer() ;

        return "https://ssoqa.paypalcorp.com/sp/startSSO.ping?" . http_build_query( $params ) ;
    }

    public static function githubOauth() {
        $host = \Routes::pluginsBase( [] );
        return "$host/paypal/oauth/github/response" ;
    }

}