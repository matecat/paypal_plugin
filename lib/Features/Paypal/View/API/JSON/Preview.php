<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 27/09/17
 * Time: 15.46
 *
 */

namespace Features\Paypal\View\API\JSON;


use Jobs\MetadataStruct;
use Segments_SegmentNoteStruct;

class Preview {

    /**
     * @param MetadataStruct $jobMetaStruct
     * @param Segments_SegmentNoteStruct[] $notes
     *
     * @return array
     */
    public function renderItem( MetadataStruct $jobMetaStruct, $notes ) {

        $noteArray = [];
        foreach( $notes as $note ){
            $noteArray[ 'segments' ][] = json_decode( $note->json );
        }

        return [ 'data' => array_merge( [ 'previews' => json_decode( $jobMetaStruct->value ) ], $noteArray ) ];

    }

}