<?php
/**
 * Plugin Name: Comic Easel - The Story So Far
 * Plugin URI: https://github.com/dreadfulgate
 * Description: Add-on to Comic Easel, adds a "story so far" meta box, shortcode and widget.
 * Author: Max Vähling
 * Author URI: http://dreadfulgate.de
 * Version: 0.1.0
 */

// Meta-Box 

function ce_story_metabox() {
	add_meta_box( 
		'ce_storysofar',
		'The Story So Far',
		'ce_storybox',
		'comic',
		'normal',
		'high' );
}

add_action( 'add_meta_boxes', 'ce_story_metabox' );
  
function ce_storybox ($post) { 
	wp_nonce_field( 'basename( __FILE__ )', 'do_the_nonce' );
	$ce_storysofar = get_post_meta( $post->ID, 'ce_storysofar', true );					
	echo '<textarea rows="3" name="ce_storysofar" style="width:100%;">'. $ce_storysofar .'</textarea>';
	echo "<p>Add a story summary for readers to catch up quickly. You can display the summary in a sidebar using the 'Comic Easel - The Story so far' widget or in your comic post using the [storysofar] shortcode. (Feel free to change the headline by adding a 'title' tag to the shortcode.)</p>";
}
 
function save_story_meta( $post_id ) {   
	// Pre-game: Check if nonce is set
 if ( ! isset( $_POST['do_the_nonce'] ) ) {
        return $post_id;
	}
    if ( ! wp_verify_nonce( $_POST['do_the_nonce'], 'basename( __FILE__ )' ) ) {
        return $post_id;
	} 
	// Check that the logged in user has permission to edit this post
    if ( ! current_user_can( 'edit_post' ) ) {
        return $post_id;
	}
	// Game: 
	if ($_POST['ce_storysofar']) {
		update_post_meta( 
				$post_id, 
				"ce_storysofar", 
				strip_tags($_POST['ce_storysofar'])
		);
	}
}

add_action( 'save_post', 'save_story_meta' );


// Shortcode

function ce_shortcode_so_far($atts) { 
	global $post;
	$summary = get_post_meta( $post->ID, 'ce_storysofar', true );
	$att = shortcode_atts( array( 'title' => 'The Story so far:' ), $atts );		
	if ( $summary ) {
		$storysofar = '<div>
							<h3>'. esc_html__($att[ 'title' ], 'sofar' )  .'</h3>
							<p>'. $summary .'</p>
						</div>';
	}
	
	echo $storysofar;
}
	
add_shortcode( 'storysofar', 'ce_shortcode_so_far' );

 
// Widget

class CE_StorySoFar extends WP_Widget {

	function __construct () { 
		$widget_options = array( 'classname' 	=> 'CE_StorySoFar',
							'description' 	=> 'Spoilers!' );
		parent::__construct( 'ce_storysofar', 'Comic Easel - The Story So Far', $widget_options );
	}

	function widget( $args, $instance ) { 
		global $post;
		$summary = get_post_meta( $post->ID, 'ce_storysofar', true );
		if ( $summary ) {
			echo $args[ 'before_widget' ];
			if ( $instance[ 'with_title' ] ) {
				echo $args[ 'before_title' ] . $instance[ 'title' ] . $args[ 'after_title' ] ;
				}
			echo '<p>'. $summary .'</p>'; 
			echo $args['after_widget'];
		}
	}
		
	function form($instance) {
		$defaults= array('with_title' => true,
					'title'     => 'The Story So Far:' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>	
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:</label>
				<input name = "<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance[ 'title' ]); ?>" />	
			</p><p>
				<label for="<?php echo $this->get_field_id( 'with_title' ); ?>">Show Title</label>
				<input type="checkbox" name="<?php echo $this->get_field_name( 'with_title' ); ?>" value="true" <?php checked( isset( $instance[ 'with_title' ]) ? $instance[ 'with_title' ] : 0); ?> />
			</p>
		<?php
	}
	
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance[ 'with_title' ] = $new_instance[ 'with_title' ] ? true : false;
		$instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
		return $instance;
	}	
}

function ce_register_story_widgets() { 
	register_widget( CE_StorySoFar ); 
}
	
add_action( 'widgets_init', 'ce_register_story_widgets' );	