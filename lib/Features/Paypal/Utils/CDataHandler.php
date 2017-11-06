<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 27/09/17
 * Time: 10.27
 *
 */

namespace Features\Paypal\Utils;


use Jobs\MetadataDao;

class CDataHandler {

    const PREVIEWS_LOOKUP = 'previews';

    public function formatJson( $projectStructure ){

        $orderingVector = [];

        foreach ( $projectStructure[ 'notes' ] as $internal_id => $v ) {

            foreach ( $projectStructure[ 'notes' ][ $internal_id ][ 'json_segment_ids' ] as $segPos => $id_segment ) {

                foreach ( $projectStructure[ 'notes' ][ $internal_id ][ 'json' ] as $jsonPos => $json ) {

                    $decodedJson = json_decode( $json );
                    $decodedJson->segment = $id_segment;
                    foreach( $decodedJson->previews as $preview ){
                        $fileName = \FilesStorage::pathinfo_fix( $preview->path, PATHINFO_BASENAME );
                        list( $preview->path, $preview->file_index ) = array_values( Routes::projectImageReferences( $projectStructure, $fileName ) );

                        /*
                         * Prepare for Schwartzian transform
                         *
                         * When the user navigate between the flows, the segment focus must be placed to the first segment in the preview,
                         * so MateCat must order the segment list in the reverse lookup from top [x,y] position to lower ( y top -> y down ).
                         */
                        $orderingVector[ $fileName ][ $id_segment ] = ( 10000 + $preview->y ) . "-" . ( 10000 - $preview->x );

                    }
                    $projectStructure[ 'notes' ][ $internal_id ][ 'json' ][ $jsonPos ] = json_encode( $decodedJson );

                }

            }

        }

        //Schwartzian transform
        foreach( $orderingVector as $fileName => $segmentVector ){
            asort( $segmentVector );
            $projectStructure[ 'json_previews' ][ $fileName ] = array_keys( $segmentVector );
        }

    }

    public function storePreviewsMetadata( $projectStructure ){

        $jMetaDao = new MetadataDao();
        foreach( $projectStructure[ 'array_jobs' ][ 'job_list' ] as $position => $id_job){
            $jMetaDao->set( $id_job, $projectStructure[ 'array_jobs' ][ 'job_pass' ][ $position ], self::PREVIEWS_LOOKUP, json_encode( $projectStructure[ 'json_previews' ] ) );
        }

    }

    /**
     * @param $jsonNoteString
     *
     * @return mixed
     */
    public function parseJsonNotes( $jsonNoteString ){
        foreach ( $jsonNoteString as $k => $noteObj ){
            $jsonNoteString[ $k ][ 0 ][ 'json' ] = $noteObj[ 0 ][ 'json' ][ 'note' ];
        }
        return $jsonNoteString;
    }

}