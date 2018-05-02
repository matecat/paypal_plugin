<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 21/02/2018
 * Time: 12:16
 */

namespace Features\Paypal\View\API\JSON;

use LQA\EntryCommentDao;
use LQA\EntryDao;

class SegmentTranslationIssue {

    public function __construct() {

    }

    public function renderItem( \DataAccess_AbstractDaoObjectStruct $record ) {

        $issues_records = EntryDao::findAllBySegmentId( $record->id_segment );
        $issues         = [];
        foreach ( $issues_records as $issue_record ) {

            $issues[] = [
                    'id'                  => (int)$issue_record->id,
                    'id_category'         => (int)$issue_record->id_category,
                    'is_full_segment'     => $issue_record->is_full_segment,
                    'severity'            => $issue_record->severity,
                    'start_node'          => $issue_record->start_node,
                    'start_offset'        => $issue_record->start_offset,
                    'end_node'            => $issue_record->end_node,
                    'end_offset'          => $issue_record->end_offset,
                    'translation_version' => $issue_record->translation_version,
                    'target_text'         => $issue_record->target_text,
                    'penalty_points'      => $issue_record->penalty_points,
                    'rebutted_at'         => $this->getDateValue( $issue_record->rebutted_at ),
                    'created_at'          => date( 'c', strtotime( $issue_record->create_date ) ),
            ];
        }

        $row = [
                'id_segment'          => (int)$record->id_segment,
                'autopropagated_from' => $record->autopropagated_from,
                'status'              => $record->status,
                'translation'         => $record->translation,
                'translation_date'    => $record->translation_date,
                'match_type'          => $record->match_type,
                'context_hash'        => $record->context_hash,
                'locked'              => $record->locked,
                'version_number'      => $record->version_number,
                'issues'              => $issues,
        ];

        return $row;
    }


    public function render( $array ) {
        $out = [];

        foreach ( $array as $record ) {
            $out[] = $this->renderItem( $record );
        }

        return $out;
    }


    private function getDateValue( $strDate ) {
        if ( $strDate != null ) {
            return date( 'c', strtotime( $strDate ) );
        }

        return null;
    }

}

