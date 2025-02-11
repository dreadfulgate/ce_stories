<?php
/**
 * Plugin Name: Comic Easel - This Story Widget
 * Plugin URI: https://github.com/dreadfulgate
 * Description: Add-on to Comic Easel, collects some story (chapter) info and a link to the first page into a widget.
 * Author: Max Vaehling
 * Author URI: https://dreadfulgate.de
 * Version: 0.2.0
 */

function ce_get_first_in_chapter() {
	global $post;	
	$chapter = get_the_terms( $post->ID, 'chapters' );
	if (is_array($chapter)) { 
		$chapter = reset($chapter); 
	} else { 
		return; 
	}
	if (is_object( $chapter ) ) {
		$child_args = array( 
				'numberposts' => 1, 
				'post_type'   => 'comic',
				'orderby' 	  => 'post_date', 
				'order' 	  => 'ASC', 
				'post_status' => 'publish', 
				'chapters' 	  => $chapter->slug 
				);				
		$qcposts = get_posts( $child_args );
		if ( is_array( $qcposts ) ) {
			$qcposts = reset( $qcposts );
			return get_permalink( $qcposts->ID );
		}
	}
	return false;
}

class CE_ThisStory extends WP_Widget {

	function __construct () { 
		$widget_options = array( 'classname'   => 'CE_ThisStory',
							'description' => 'Displays custom story info for the current comic' );
		parent::__construct( 'ce_thisstory', 'Comic Easel - This Story Widget', $widget_options );
	}

	function widget( $args, $instance ) { 
		$terms = get_the_terms( $post, 'chapters' );
		if ( $terms ) { 
			foreach ( $terms as $term ) { 
				if ( ( get_term_children( $term->id, 'chapters' ) == 0 ) || ( ( get_term_children( $term->id, 'chapters' ) > 0 ) && ( count( $terms ) == 1 ) ) ) 
				{
					$storyline = $term->name; 
					$synopsis = $term->description; 
					$storylink = get_term_link( $term->slug, 'chapters');
				}
			}
			$first_comic = ce_get_first_in_chapter( $post->ID );
			$this_permalink = get_permalink( $post->ID );
			if ( ( $synopsis ) || ( ( $term->count > 1 ) && ( $first_comic !== $this_permalink ) ) || ( $instance['show-anyway'] ) )
			{
				echo $args[ 'before_widget' ]. $args[ 'before_title' ]; 
				if ( ! $instance[ 'archive' ] ) {
					echo $storyline;
				} else {
					echo '<a href="'. $storylink .'">'. $storyline . '</a>';
				}
				echo  $args[ 'after_title' ] . '<div class="comic-post-info">';
				if ( $instance[ 'synopsis' ] ) {
					echo $synopsis; 
				}
				if ( ( $instance[ 'firstlink' ] ) && ( $first_comic !== $this_permalink ) ) { 
					echo '<p><a href="'. $first_comic .'">'. $instance[ 'firstline' ] .'</a></p>'; }
				echo '</div>';
				echo $args[ 'after_widget' ];
			}
		}		
	}

	function form ($instance) { 
		$defaults = array ( 	'firstlink'	  => true,
					'firstline'	  => 'Read from the beginning',	
					'synopsis'	  => true,
					'archive' 	  => true,
					'show-anyway'     => true
					);		
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
			<p>
				<label for="<?php echo $this->get_field_id('archive'); ?>">Link title to story archive</label>
				<input type="checkbox" name="<?php echo $this->get_field_name('archive'); ?>" value="true" <?php checked(isset($instance['archive']) ? $instance['archive'] : 0); ?> />
			</p><p>
				<label for="<?php echo $this->get_field_id('firstlink'); ?>">Link to first page</label>
				<input type="checkbox" name="<?php echo $this->get_field_name('firstlink'); ?>" value="true" <?php checked(isset($instance['firstlink']) ? $instance['firstlink'] : 0); ?> />
			</p><p>
				<label for="<?php echo $this->get_field_id('firstline'); ?>">Link text</label>
				<input name = "<?php echo $this->get_field_name('firstline'); ?>" type="text" value="<?php echo esc_attr($instance['firstline']); ?>" />	
			</p><p>
				<label for="<?php echo $this->get_field_id('synopsis'); ?>">Include story description</label>
				<input type="checkbox" name="<?php echo $this->get_field_name('synopsis'); ?>" value="true" <?php checked(isset($instance['synopsis']) ? $instance['synopsis'] : 0); ?> />
			</p><p>
				<label for="<?php echo $this->get_field_id('show-anyway'); ?>">Always show widget, even if it would only show the title and this is the only page in that story anyway</label>
				<input type="checkbox" name="<?php echo $this->get_field_name('show-anyway'); ?>" value="true" <?php checked(isset($instance['show-anyway']) ? $instance['show-anyway'] : 0); ?> />
			</p>	
	
		<?php 
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance[ 'archive' ] 		= $new_instance[ 'archive' ] ?	true : false;
		$instance[ 'synopsis' ] 	= $new_instance[ 'synopsis' ] ? true : false;
		$instance[ 'firstlink' ] 	= $new_instance[ 'firstlink' ] ? true : false;
		$instance[ 'show-anyway' ] 	= $new_instance[ 'show-anyway' ] ? true : false;
		$instance[ 'firstline' ] 	= strip_tags( $new_instance[ 'firstline' ] );
		return $instance;
	}	
}

function ce_register_widgets() { 
	register_widget( CE_ThisStory ); 
}

add_action( 'widgets_init', 'ce_register_widgets' );
