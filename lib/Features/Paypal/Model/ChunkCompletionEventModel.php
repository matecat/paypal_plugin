<?php
/**
 * Created by PhpStorm.
 * User: fregini
 * Date: 14/02/2018
 * Time: 18:05
 */

namespace Features\Paypal\Model ;

use Chunks_ChunkCompletionEventDao;
use Chunks_ChunkCompletionEventStruct;
use Features\ProjectCompletion\CompletionEventStruct;
use Features\ProjectCompletion\Model\EventModel;
use Utils;

class ChunkCompletionEventModel {

    /**
     * @var \Chunks_ChunkStruct
     */
    protected $chunk;

    public function __construct( $chunk ) {
        $this->chunk = $chunk;
    }

    public function setTranslationCompleted($params=[]) {
        $ip_address = isset($params['ip_address']) ? $params['ip_address'] : Utils::getRealIpAddr() ;

        // ensure a translation completed event does not already exist for the chunk
        // create the translation completion record
        $currentPhase = ( new Chunks_ChunkCompletionEventDao())->currentPhase( $this->chunk ) ;

        if ( $currentPhase == Chunks_ChunkCompletionEventDao::TRANSLATE ) {
            $struct = new CompletionEventStruct([
                    'remote_ip_address' => $ip_address,
                    'source'            => Chunks_ChunkCompletionEventStruct::SOURCE_USER,
                    'is_review'         => false
            ]);

            $model = new EventModel( $this->chunk, $struct ) ;
            $model->save();
        }
    }
}