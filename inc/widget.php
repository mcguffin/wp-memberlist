<?php


class Memberlist_Widget extends WP_Widget {

	public function __construct() {
		// widget actual processes
		parent::__construct(
			'memberlist_widget', // Base ID
			__('Memberlist', 'memberlist'), // Name
			array( 'description' => __( 'Shows a list of your Blog members', 'memberlist' ), ) // Args
		);
	}

	public function widget( $args, $instance ) {
		// outputs the content of the widget

		extract( $args );
		
		
		$users = get_users(array(
			'orderby' => 'display_name',
			'order' => 'ASC',
		));
		
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		echo $before_widget;
		
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		
		?><ul class="memberlist"><?php
		foreach ( $users as $user ) {
			$url = false;
			if ( $memberpage = MemberPage::instance()->get_member_page( $user->ID ) )
				$url = get_permalink( $memberpage->ID );
			else if ( $user->user_url ) 
				$url = $user->user_url;
			
			if ( $url )
				printf( '<li class="memberlist-item"><a href="%s">%s</a></li>' ,  $url , $user->display_name );
			else 
				printf( '<li class="memberlist-item">%s</li>' ,  $user->display_name );
		}
		?></ul><?php
		echo $after_widget;
	}

 	public function form( $instance ) {
		// outputs the options form on admin
		/*
			ToDo:
			- options what 
		*/
		if ( isset( $instance[ 'title' ] ) )
			$title = $instance[ 'title' ];
		else
			$title = __( 'Members', 'memberlist' );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		
		
		<?php 
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}
}

add_action( 'widgets_init', function(){
     register_widget( 'Memberlist_Widget' );
});