<?php 
require_once( dirname(__FILE__).'/people-control-db.php');

class DreamKyivPeopleControlHooks {
    private static $__oSelf = null;
    
    var $options = array(
            1 => 'Не голосував',
            2 => 'Відсутній',
            3 => 'За',
            4 => 'Утримався',
            5 => 'Проти',
        );    
    
    static function setHooks() {
        if( !self::$__oSelf ) {
            self::$__oSelf = new DreamKyivPeopleControlHooks();
        }

        add_action( 'acf/create_field', array( self::$__oSelf, 'people_control_create_field' ), 11, 1 );
        add_action( 'edit_form_after_title', array( self::$__oSelf, 'edit_form_after_title' ), 10, 1 );
        
        add_action( 'wp_ajax_peoplecontrolsetvoting', array( self::$__oSelf, 'ajax_set_voting' ) );
		add_action( 'wp_ajax_nopriv_peoplecontrolsetvoting', array( self::$__oSelf, 'ajax_set_voting' ) );
		
		add_action( 'wp_ajax_peoplecontrolnewdecisions', array( self::$__oSelf, 'ajax_deputy_new_decisions' ) );
		add_action( 'wp_ajax_nopriv_peoplecontrolnewdecisions', array( self::$__oSelf, 'ajax_deputy_new_decisions' ) );
		
		add_action( 'wp_ajax_peoplecontrolalldecisions', array( self::$__oSelf, 'ajax_deputy_all_decisions' ) );
		add_action( 'wp_ajax_nopriv_peoplecontrolalldecisions', array( self::$__oSelf, 'ajax_deputy_all_decisions' ) );
		
		wp_enqueue_style('people-control', plugins_url('/people-control/css/people-control.css'), 'css');
		wp_enqueue_script('people-control', plugins_url('/people-control/js/people-control.js'), array('jquery'));
    }
    
    private function _deputy_decisions( $deputy_id, $decisions, $block_id, $hide=true ) {
    	$hide_class = $hide ? ' class="acf-tab_group-hide"' : '';
    	$ret = '<div id="'.$block_id.'"'.$hide_class.'><table class="people-control-decisions-table widefat acf-input-table">';
    	$ret .="<thead><tr><th>Рішення</th><th>Дата голосування</th><th>Голос</th></tr></thead>\n";
    	 
    	$ret .= "<tbody>\n";
    	if( $deputy_id ) {
    		$dkpc = new DreamKyivPeopleControlDb();

    		foreach ($decisions as $dd){
    			$decision_date = date( 'd/m/Y', strtotime( get_field('rada_decision_voting_date', $dd->ID ) ) );
    			$data = $dkpc->get_voting( $deputy_id, $dd->ID );
    			$vote = ( $data ? $data->vote : '' );
    			
    			$vote_class = $vote ? ' people-control-voting-selector-' . $vote : '';

    			$ret .= '<tr>';
    			$ret .= '<td>' .  $dd->post_title .  '</td>';
    			$ret .= '<td class="people-control-decision-date">' .  $decision_date .  '</td>';
    			$ret .= '<td class="people-control-decision-vote' . $vote_class . '">' .  $this->get_voting_option_selector( $deputy_id, $dd->ID, $vote ) .  '</td>';
    			$ret .= '</tr>';
    		}
    
    	}
    	 
    	$ret .= "</tbody>\n";
    	$ret .= '</table></div>';
    	 
    	return $ret;
    }
    
    function ajax_deputy_new_decisions() {
    	$deputy_id  = intval( $_GET['deputy_id'] );
    	$hide  = intval( $_GET['order_no'] ) > 0;

    	if( $deputy_id ) {
            $dkpc = new DreamKyivPeopleControlDb();

            $decisions = $dkpc->get_undefined_decisions_posts( $deputy_id );
            echo $this->_deputy_decisions( $deputy_id, $decisions, 'kmda_new_decisions', false );
    	}

    	die();
    }
    
    function ajax_deputy_all_decisions() {
    	$deputy_id  = intval( $_GET['deputy_id'] );
    	$hide  = intval( $_GET['order_no'] ) > 0;

    	if( $deputy_id ) {
    		$query = new WP_Query(
    				array(
    						'post_type' => 'rada_decision',
    						'post_status' => 'publish',
    						'meta_key' => 'rada_decision_voting_date',
    						'orderby' => 'meta_value_num',
    						'order' => 'DESC'
    				)
    		);

			$decisions = $query->get_posts();
            echo $this->_deputy_decisions( $deputy_id, $decisions, 'kmda_all_decisions', false );
    	}

    	die();
    }    
    
    function ajax_set_voting() {
        $deputy_id  = intval( $_GET['deputy_id'] );
        $decision_id  = intval( $_GET['decision_id'] ); 
        $vote  = intval( $_GET['vote'] ); 
        
        if( $deputy_id && $decision_id ) {
            $dkpc = new DreamKyivPeopleControlDb();
            
            if( $vote ) {
                $dkpc->set_voting( $deputy_id, $decision_id, $vote);
            } else {
                $dkpc->delete_voting( $deputy_id, $decision_id);
            }
        } 

        die();
    }
    
    function edit_form_after_title($post) {
    	if( $post->post_type == 'deputy_control' ) {
?>
<script type="text/javascript">
oPeopleControl = { 'loaders' : {}, 'realod' : false };
</script>

<script type="text/javascript">
(function($){
	<?php $this->init_js_functions() ; ?>
})(jQuery);
</script>
<?php
    	} elseif( $post->post_type == 'rada_decision' ) {
            if( $post->post_status == 'publish') {
                
?>

<script type="text/javascript">
oPeopleControl = { 'loaders' : {}, 'realod' : false };

(function($){
	<?php $this->init_js_functions() ; ?>
	
    jQuery(document).ready( function() {
        <?php $current_user = wp_get_current_user(); ?>
    	<?php if( in_array( 'people_control', (array) $current_user->roles ) ) { ?>
    	$('#publish').hide();
    	<?php } ?>

    	oPeopleControl.people_control_init_voting_result_selectors();
    });
})(jQuery);

</script>
<?php
                
                $kandidats_per_row = 3;
                echo "<table class='people-control-voting-table'>\n";
                
                $query = new WP_Query(
                        array(
                            'post_type' => 'deputy_control',
                            'post_status' => 'publish',
                            'orderby' => 'title',
                            'order' => 'ASC'
                        )
                    );    
                    
                if ( $query->have_posts() ) {
                    $controls = $query->get_posts();
                    $dkpc = new DreamKyivPeopleControlDb();
                    $decision_id = $post->ID;
                    $i=0;
                    foreach( $controls as $c ) {                        
                        if( $i === 0 ) {
                            echo "<tr>";
                        }
                        
                        echo "<td>";
                        $deputy_id = url_to_postid( get_field('control_deputy_reference', $c->ID ) );                     
                        echo $this->get_deputy_link($deputy_id);
                        
                        $data = $dkpc->get_voting( $deputy_id, $decision_id );
                                       
                        if( current_user_can('edit_kandidat', $deputy_id) ) {
                            // can change voting result
                            echo "<div class='people-control-voting-result'>". $this->get_voting_option_selector( $deputy_id, $decision_id, $data->vote ) . "</div>";
                        } else {
                            // can only read voting result
                            echo "<div class='people-control-voting-result people-control-voting-result-".$data->vote."'>". $this->get_voting_option_label( $data->vote ) . "</div>";
                        }
                           
                        echo "</td>";
                        
                        $i++;
                        if( $i == $kandidats_per_row ) {
                            echo "</tr>\n";
                            $i = 0;
                        } 
                    }
                }            
                
                if( $i < $kandidats_per_row  ) {
                    echo str_repeat('<td>&nbsp;</td>',$kandidats_per_row-$i);
                    echo "</tr>\n";
                }
                
                echo "</table>";
            }
        }
    }
    
    private function _deputy_decisions_list_field( $action, $args ) {
?>
<script type="text/javascript">
(function($){

	oPeopleControl.loaders.load_<?= $action ?> = function() {
        var deputy_id = $('#acf-field-control_deputy_reference').val();
        if( deputy_id ) {
            var container_id = 'rada-decisions-<?= $args['key'] ?>';
            var container = $('#' + container_id);

            loading = true;
            
            if( container.length == 0 ) {
            	$('div[data-field_key="<?= $args['key'] ?>"]').after('<div id="' + container_id + '"></div>');
            }
            $('#' + container_id).html('<span style="font-weight: bold;">Завантажується...</span>');
        	$.ajax({
		        url: ajaxurl,
		        dataType: "html",
		        data : {
		        	'action' : '<?= $action ?>',
		        	'order_no' : '<?= $args['order_no'] ?>',
			        'deputy_id' : $('#acf-field-control_deputy_reference').val()
			    },
		        success: function( data ) {
		        	$('#' + container_id).html( data );
		        	oPeopleControl.people_control_init_voting_result_selectors( '#' + container_id +' ' );

		        	$('a[data-key="<?= $args['key'] ?>"]').click( function () {
		            	if( oPeopleControl.reload ) {
		        			oPeopleControl.loaders.load_<?= $action ?>();
		        			oPeopleControl.reload = false;
		            	}
		        	});
		        },
		        error: function(jqXHR, textStatus, errorThrown) {
		        	console.log(jqXHR, textStatus, errorThrown);
		        }
		    });
        }
	}
	
    jQuery(document).ready( function() {
    	oPeopleControl.loaders.load_<?= $action ?>();
	})
})(jQuery);
</script>

<?php
    }
    
    function people_control_create_field( $args ) {
        if( $args['type'] == 'tab' ) {
			if( $args['label'] == 'Всі голосування' ) {
				$this->_deputy_decisions_list_field( 'peoplecontrolalldecisions', $args );
			} elseif( $args['label'] == 'Нові голосування' ) {
				$this->_deputy_decisions_list_field( 'peoplecontrolnewdecisions', $args );
	        }
	    }
    }

    function get_deputy_link( $deputy_id ) {
        $deputy = get_post( $deputy_id );
        $ret = "<div class='people-control-voting-deputy'><a href='".get_permalink($deputy_id )."' target='_blank' title='".$deputy->post_title ."'>";
        list( $n1, $n2, $n3) = explode( ' ', $deputy->post_title );
        $ret .= $n1 . ' ' . mb_substr($n2,0,1) . '.' . mb_substr($n3,0,1) . '.';
        $ret .= "</a></div>";
        
        return $ret;
    }
        
    function get_voting_option_selector( $deputy_id, $decision_id, $value) {
        $str = "<select deputy_id='$deputy_id' decision_id='$decision_id' class='people-control-voting-result-selector'>";
        $str .= "<option value='' ".( !$value? 'selected' : '')."></option>";
        foreach( $this->options as $v => $l ) {
            $str .= "<option value='$v' ".( $v == $value? 'selected' : '' ).">$l</option>";
        }
        $str .= '</select>';
        
        return $str;
    }
    
    function get_voting_option_label( $value) {
        return $this->options[ $value ];
    }
    
    function init_js_functions( $adds='' ) {
?>
	oPeopleControl.people_control_init_voting_result_selectors = function ( parent ) {
		var sel = 'select.people-control-voting-result-selector';
		if( parent ) {
			sel = parent + ' ' + sel;
		}
	
		$(sel).change( function( e ) {
			$.ajax({
		        url: ajaxurl,			    	
		        dataType: "json",
		        data : {
		        	'action' : 'peoplecontrolsetvoting',
			        'deputy_id' : $(this).attr('deputy_id'),
			        'decision_id' : $(this).attr('decision_id'),
			        'vote' : $(this).val()
			    },
		        success: function( data ) {
		        	oPeopleControl.reload = true;
		        	alert('Результати голосування збережено');
		        	<?= $adds ?>
		        },
		        error: function(jqXHR, textStatus, errorThrown) {
		        	console.log(jqXHR, textStatus, errorThrown);
		        }
		    });
		    
		    e.stopPropagation();
	   	});
	}
<?php 
    }

}

DreamKyivPeopleControlHooks::setHooks();

?>