<?php

/*
Plugin Name: Memberlist
Plugin URI: https://github.com/mcguffin/wp-memberlist
Description: Extends userdata by physical address and phone numbers. Outputs a memberlist.
Author: Jörn Lund
Version: 0.0.8
Author URI: https://github.com/mcguffin
License: GPL2

Text Domain: wp-memberlist
Domain Path: /languages/
*/

/*  Copyright 2014  Jörn Lund  (email : joern AT podpirate DOT org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class Memberlist {
	private static $_instance = null;
	static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}
	private function __construct() {
		add_action('init',array(&$this,'init'),99);
		add_action('plugins_loaded',array(&$this,'plugins_loaded'));
		add_shortcode('members',array(&$this,'shortcode'));
		add_shortcode('members_link',array(&$this,'link_shortcode'));

		add_filter("memberlist_print_zip" , '__return_false' );
		add_filter("memberlist_print_city" , '__return_false' );
		add_filter("memberlist_print_mobile" , '__return_false' );

		add_filter("memberlist_phone_field" , array(&$this,'gather_phone') , 10 , 2 );
		add_filter("memberlist_address_field" , array(&$this,'gather_address') , 10 , 2 );
		
		if ( is_admin() )
			require_once plugin_dir_path(__FILE__) . '/inc/admin.php';
		
		require_once plugin_dir_path(__FILE__) . '/inc/member-page.php';
		require_once plugin_dir_path(__FILE__) . '/inc/widget.php';

		register_activation_hook( __FILE__, array(&$this,'activation')  );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

	}
	function activation() {
		MemberPage::instance()->register_post_type();
		flush_rewrite_rules();
    }
	function init() {
		register_post_type(	'member', 
			array(	'label' 			=> __('Members','wp-memberlist'),
					'public' 			=> true,
					'can_export'		=> false,
					'show_ui' 			=> false, // UI in admin panel
					'capability_type' 	=> 'page',
					'hierarchical' 		=> false,
					'supports' 			=> array( ) ,
					'show_in_nav_menus'	=> false,
					'taxonomies'		=> array( ),
					'has_archive'		=> true,
				)
		);
	
		add_option('memberlist_require_login',true);
		add_option('memberlist_require_role','author');
		
	}
	function plugins_loaded() {
		load_plugin_textdomain( 'wp-memberlist' , false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	function get_fields( ) {
		$memberlist_fields = array(
			'address' => array(
				'label' => __('Address','wp-memberlist'),
				'description' => '',
				'type'	=> 'text',
				'placement' => 'after',
				'vcard_prop' => 'home_address',
			),
			'zip' => array(
				'label' => __('ZIP','wp-memberlist'),
				'description' => '',
				'type'	=> 'text',
				'placement' => 'after',
				'vcard_prop' => 'home_postal_code'
			),
			'city' => array(
				'label' => __('City','wp-memberlist'),
				'description' => '',
				'type'	=> 'text',
				'placement' => 'after',
				'vcard_prop' => 'home_city'
			),
			'phone' => array(
				'label' => __('Phone','wp-memberlist'),
				'description' => '',
				'type'	=> 'text',
				'placement' => 'after',
				'vcard_prop' => 'home_tel'
			),
			'mobile' => array(
				'label' => __('Mobile','wp-memberlist'),
				'description' => '',
				'type'	=> 'text',
				'placement' => 'after',
				'vcard_prop' => 'cell_tel'
			),
		);
		return apply_filters( 'memberlist_fields' , $memberlist_fields );
	}
	function gather_phone( $val , $user ) {
		$callto = '<a href="tel:%s">%s</a>';
		$val = sprintf( $callto , $val , $val );
		$val .= "<br />".sprintf( $callto , $user->memberlist_mobile,$user->memberlist_mobile );
		return $val;
	}
	function gather_address( $val , $user ) {
		$val .= sprintf("<br />%s %s",$user->memberlist_zip , $user->memberlist_city );
		return $val;
	}

	function shortcode( $atts , $content=null ) {
		// first name, last name, loginname
		// email
		// fields
		$atts = extract(wp_parse_args( $atts, array(
			'capability' => '',
			'class' => 'memberlist',
		)));

		if ( is_array($class) )
			$class = implode(' ',$class);
		$fields = $this->get_fields();
		$users = get_users( );
		if ( ! get_option('memberlist_require_login') || is_user_logged_in() ) {
			ob_start();
			?><table class="<?php echo $class ?>"><?php
				?><thead><?php
					?><tr><?php
						// before
						foreach ( $fields as $key => $field ) {
							$this->print_field_head( $key , $field , 'before' );
						}
						?><th><?php 
						_e( 'Name' ) ;
						?>, <?php
						 _e( 'Email' ); ?></th><?php
						foreach ( $fields as $key => $field ) {
							$this->print_field_head( $key , $field , 'after' );
						}
						// after
					?></tr><?php
				?></thead><?php
				?><tbody><?php
				foreach ( $users as $user ) {
					?><tr><?php
						if ( $capability && ! $user->has_cap( $capability ) )
							continue;
						foreach ( $fields as $key => $field ) {
							$this->print_field( $key , $field , $user , 'before' );
						}
					
						?><td><?php
							printf( '%s %s' , $user->first_name , $user->last_name );
							printf( '<br /><a href="mailto:%s">%s</a>' , $user->user_email, $user->user_email );
						?></td><?php
						foreach ( $fields as $key => $field ) {
							$this->print_field( $key , $field , $user , 'after' );
						}
					
					?></tr><?php
				}
				?></tbody><?php
			?></table><?php
			$ret = ob_get_clean();
		} else {
			if ( ! is_user_logged_in() ) 
				$ret = sprintf(__('Please <a href="%s">login</a>','wp-memberlist'),wp_login_url());
			else 
				$ret = __('Insufficient Privileges');
		}
		return $ret;
	}

	function print_field_head( $key , $field , $section ) {
		if ( apply_filters( "memberlist_print_{$key}" , true ) ) {
			extract($field);
			if ( $placement == $section ) {
				?><th class="manage-column column-title"><?php echo $label ?></th><?php
			}
		}
	}
	function print_field( $key , $field , $user , $section ) {
		if ( apply_filters( "memberlist_print_{$key}" , true ) ) {
			extract($field);
			if ( $placement == $section ) {
				$var = "memberlist_{$key}";
				?><td><?php
				
					switch ( $type ) {
						default:
							$value = $user->$var;
							break;
						case 'none':
							$value = false;
							break;
						case "bool":
						case 'boolean':
						case 'checkbox':
							$value = $user->$var ? __('Yes') : __('No');
							break;
					}
					echo apply_filters("memberlist_{$key}_field" , $value , $user );
				?></td><?php
			}
		}
	}

}
Memberlist::instance();


