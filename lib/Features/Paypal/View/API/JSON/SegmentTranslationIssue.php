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

    public function renderItem( \DataAccess_AbstractDaoSilentStruct $record ) {

        $issues = EntryDao::findAllBySegmentId( $record->id_segment );
        $row    = [
                'id_segment'          => (int)$record->id_segment,
                'autopropagated_from' => $record->autopropagated_from,
                'status'              => $record->status,
                'translation'         => $record->translation,
                'translation_date'    => $record->translation_date,
                'match_type'          => $record->match_type,
                'context_hash'        => $record->context_hash,
                'locked'              => $record->locked,
                'version_number'      => $record->version_number,
                'issues'              => $this->renderIssues( $issues ),
        ];

        return $row;
    }

    public function renderIssue( \DataAccess_AbstractDaoSilentStruct $record ) {
        /*$dao = new EntryCommentDao();

        $comments = $dao->findByIssueId( $record->id );*/

        $row = [
                'id'                  => (int)$record->id,
                'id_category'         => (int)$record->id_category,
                'is_full_segment'     => $record->is_full_segment,
                'severity'            => $record->severity,
                'start_node'          => $record->start_node,
                'start_offset'        => $record->start_offset,
                'end_node'            => $record->end_node,
                'end_offset'          => $record->end_offset,
                'translation_version' => $record->translation_version,
                'target_text'         => $record->target_text,
                'penalty_points'      => $record->penalty_points,
                'rebutted_at'         => $this->getDateValue( $record->rebutted_at ),
                'created_at'          => date( 'c', strtotime( $record->create_date ) ),
            //'comments'            => $comments
        ];

        return $row;
    }

    public function renderArray( $array ) {
        $out = [];

        foreach ( $array as $record ) {
            $out[] = $this->renderItem( $record );
        }

        return $out;
    }

    public function renderIssues( $array ) {
        $out = [];

        foreach ( $array as $record ) {
            $out[] = $this->renderIssue( $record );
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

