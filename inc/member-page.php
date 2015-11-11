<?php



class MemberPage {
	private static $_instance = null;
	static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}
	
	private function __construct() {
		// register post type
		add_action( 'init' , array( &$this , 'register_post_type' ) );
		add_action( 'personal_options' , array(&$this,'add_memberpage_control') );
		add_action( 'personal_options_update' , array(&$this,'update_profile') , 11 );
		add_action( 'admin_bar_menu', array(&$this,'add_admin_bar_item') );
		add_filter( 'map_meta_cap' , array( &$this , 'map_meta_cap' ) , 10 , 4 );
	}
	function add_admin_bar_item( $wp_admin_bar ) {
		if ( $page = $this->get_member_page( ) ) {
			$wp_admin_bar->add_menu( array(
				'parent' => 'user-actions',
				'id'     => 'edit-memberpage',
				'title'  => __( 'Edit My Member Page','wp-memberlist' ),
				'href' => get_edit_post_link( $page->ID ),
			) );
		}
	}
	function register_post_type( ) {
		$labels = array(
			'name'               => __('Member Pages','wp-memberlist'),
			'singular_name'      => __('Member Page','wp-memberlist'),
			'add_new'            => __('Add New','wp-memberlist'),
			'add_new_item'       => __('Add New Member Page','wp-memberlist'),
			'edit_item'          => __('Edit Member Page','wp-memberlist'),
			'new_item'           => __('New Member Page','wp-memberlist'),
			'all_items'          => __('All Member Pages','wp-memberlist'),
			'view_item'          => __('View Member Page','wp-memberlist'),
			'search_items'       => __('Search Member Pages','wp-memberlist'),
			'not_found'          => __('No Member Pages found','wp-memberlist'),
			'not_found_in_trash' => __('No Member Pages found in Trash','wp-memberlist'),
			'parent_item_colon'  => '',
			'menu_name'          => __('Member Pages','wp-memberlist'),
		);
		$opts = array(
			'labels'				=> $labels,
			'public'				=> true,
			'publicly_queryable'	=> true,
			'show_ui'				=> true,
			'show_in_menu'			=> true,
			'query_var'				=> true,
			'rewrite'				=> array( 'slug' => 'member' ),
			'capability_type'		=> 'post',
			'has_archive'			=> true,
			'hierarchical'			=> false,
			'menu_position'			=> 45,
			'supports'				=> array( 'title', 'editor', 'thumbnail', 'excerpt' , 'comments' ),
		);
		register_post_type( 'member-page' , $opts );
	}
	// --------------------------------------------------
	// editing caps
	// --------------------------------------------------
	static function map_meta_cap($caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'edit_post':
			case 'delete_post':
				if ( isset($args[0]) ) {
					$post_ID = $args[0];
					$post = get_post($post_ID);
					if ( $post->post_type == 'member-page' ) {
						if ( ! is_admin() && $post->post_author != $user_id ) {
							$caps[] = 'do_not_allow';
							break;
						}
					}
				}
				break;
		}
		return $caps;
	}
	function add_memberpage_control( $profileuser ){
		$current_user = wp_get_current_user();
		
		if ( current_user_can('edit_posts') ) {
			?><th scope="row"><?php _e('Member page','wp-memberlist') ?></th>
			<td><?php 
				if ( $profileuser->ID == $current_user->ID ) {
					// create page link
					if ( $page = $this->get_member_page( $current_user->ID ) ) {
						edit_post_link( __('Edit my Member Page','wp-memberlist'),'','',$page->ID );
					} else {
						?><button name="create_member_page" value="1" class="button secondary"><?php _e('Create Member page','wp-memberlist') ?></button><?php
						// edit member page
					}
				} else if ( $page = $this->get_member_page( $profileuser->ID ) ) {
					//  visit memberpage
					if ( current_user_can( 'edit_post', $page->ID ) ) {
						edit_post_link( __('Edit Member Page','wp-memberlist'),'','',$page->ID );
					} else {
						?><a href="<?php echo get_permalink( $page->ID ); ?>"><?php __('Visit Member Page','wp-memberlist') ?></a><?php
					}
				}
				$this->get_member_page( $profileuser->ID );
			?></td>
			</tr><?php
		}
	}
	
	function user_has_member_page( $user_id = null ) {
		return (bool) $this->get_member_page( $user_id );
	}
	function create_member_page( ) {
		if ( current_user_can('edit_posts') && ! $this->user_has_member_page() ) {
			$user_id = get_current_user_id();
			$post_data = array(
				'post_type' => 'member-page',
				'post_name' => wp_get_current_user()->user_login,
				'post_title' => wp_get_current_user()->display_name,
				'post_status' => 'draft',
				'post_author' => $user_id,
			);
			$post_id = wp_insert_post( $post_data );
			update_user_meta( $user_id , '_member_page' , $post_id );
			wp_redirect( get_edit_post_link( $post_id , 'redirect' ) );
			exit();
		}
	}
	function get_member_page( $user_id = null ) {
		if ( is_null( $user_id ) )
			$user_id = get_current_user_id();
		
		$post_id = get_user_meta( $user_id , '_member_page' , true);
		if ( $post_id ) {
			$post = get_post($post_id);
			return $post;
		}
		return false;
	}
	function update_profile( $user_id ) {
		if ( isset( $_POST['create_member_page'] ) && ! $this->user_has_member_page() ) {
			$this->create_member_page();
		}
	}
}


MemberPage::instance();



