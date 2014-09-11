<?php

class DreamKyivPeopleControlDb {
	
	static function get_last_votings( $deputy_id, $limit=0 ) {
		$dkpc = new DreamKyivPeopleControlDb();
		return $dkpc->get_defined_decisions( $deputy_id, $limit );
	}

	function get_defined_decisions( $deputy_id, $limit=0 ) {
		global  $wpdb;
			
		$sql = "SELECT * FROM ".DK_PEOPLE_CONTROL_VOTINGS_TABLE." WHERE deputy_post_id=%s ORDER BY voting_date DESC";

		$limit = intval($limit);
		if( $limit ) {
			$sql .= ' LIMIT ' . $limit;
		}

		$sql = $wpdb->prepare( $sql, $deputy_id );

		return $wpdb->get_results( $sql );
	}
	
	function get_undefined_decisions_posts( $deputy_id ) {
		global  $wpdb;
		 
		// get already defined decisions
		$defined_decisions = $this->get_defined_decisions($deputy_id);
		
		$defined_decisions_ids = array();
		foreach($defined_decisions as $dd ) {
			$defined_decisions_ids[] = $dd->decision_post_id;
		}
		
		$query = new WP_Query(
            array(
                'post_type' => 'rada_decision',
                'post_status' => 'publish',
                'post__not_in' => $defined_decisions_ids
            )
        );

		$ret = array();
        if ( $query->have_posts() ) {
            $decisions = $query->get_posts();
            foreach( $decisions as $d ) {
            	$ret[] = $d;
            }
        }
        
        return $ret;
	}
    
    function get_voting( $deputy_id, $decision_id ) {
    	global  $wpdb;
    	
    	$sql = $wpdb->prepare( 
    		"SELECT * FROM ".DK_PEOPLE_CONTROL_VOTINGS_TABLE." WHERE decision_post_id=%d AND deputy_post_id=%s",
    	    $decision_id,
    	    $deputy_id
    	);

    	return $wpdb->get_row( $sql );
    }
    
    function set_voting( $deputy_id, $decision_id, $vote ) {
    	global  $wpdb;

    	$decision_date = get_field('rada_decision_voting_date', $decision_id);

    	$row = $this->get_voting( $deputy_id, $decision_id );
    	
    	if( $row ) {
    	    // update
    	    $sql = $wpdb->prepare( 
        		"UPDATE ".DK_PEOPLE_CONTROL_VOTINGS_TABLE." SET vote=%d, voting_date=%s WHERE decision_post_id=%d AND deputy_post_id=%d",
        	    $vote,
        	    $decision_date,
        	    $decision_id,
        	    $deputy_id    	    
        	);
    	} else {
    	    // insert
    	    $sql = $wpdb->prepare( 
        		"INSERT INTO ".DK_PEOPLE_CONTROL_VOTINGS_TABLE."( decision_post_id, deputy_post_id, vote, voting_date) VALUES(%d, %d, %d, %s)",
        	    $decision_id,
        	    $deputy_id,
        	    $vote,
        	    $decision_date
        	);
    	}

    	return $wpdb->query( $sql );
    }
    
    function delete_voting( $deputy_id, $decision_id ) {
    	global  $wpdb;

	    $sql = $wpdb->prepare( 
        		"DELETE FROM  ".DK_PEOPLE_CONTROL_VOTINGS_TABLE." WHERE decision_post_id=%d AND deputy_post_id=%d",

        	    $decision_id,
        	    $deputy_id    	    
        	);

    	return $wpdb->query( $sql );
    }
}
    
?>