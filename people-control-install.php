<?php

function people_control_activate() {
	global  $wpdb, $user_level, $user_ID;
	get_currentuserinfo();
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$sql = "CREATE TABLE ".DK_PEOPLE_CONTROL_VOTINGS_TABLE." (
		decision_post_id bigint(20) unsigned NOT NULL,
		deputy_post_id bigint(20) unsigned NOT NULL,
		vote smallint(5) unsigned NOT NULL,
		voting_date date NULL DEFAULT NULL,
		PRIMARY KEY  (decision_post_id, deputy_post_id)
		) DEFAULT CHARSET=utf8 ;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);	
	
	require_once( dirname(__FILE__).'/people-control-db.php');
	
	$query = new WP_Query(
        array(
            'post_type' => 'deputy_control',
            'post_status' => 'publish'
        )
    );
    
    // create records for existing deputy controls data
    if ( $query->have_posts() ) {
        $controls = $query->get_posts();
        $dkpc = new DreamKyivPeopleControlDb();
        foreach( $controls as $c ) {
            //error_log( '... ' . $c->ID );
            $deputy_id = url_to_postid( get_field('control_deputy_reference', $c->ID ) );
            //error_log( 'dep '.$deputy_id);

            $votings = get_field('control_voting', $c->ID);
            for( $i=0; $i < count($votings) ; $i++ ) {
                //error_log( print_r($votings[$i],1) );
                $decision_id = url_to_postid( $votings[$i]['control_voting_decision'] );
                $control_voting_vote = $votings[$i]['control_voting_vote'];
                error_log( 'des '.$decision_id.' : '.$control_voting_vote);
                if( $decision_id && $control_voting_vote) {
                    $dkpc->set_voting( $deputy_id, $decision_id, $control_voting_vote);
                }
            }
        }
    }
}

function people_control_deactivate() {
	global  $wpdb, $user_level, $user_ID;
	get_currentuserinfo();
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	$sql = "DROP TABLE ".DK_PEOPLE_CONTROL_VOTINGS_TABLE." ;";
    $wpdb->query( $sql );
}
?>