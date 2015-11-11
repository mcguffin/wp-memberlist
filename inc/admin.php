<?php



class MemberlistAdmin {
	private static $_instance = null;
	static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	private function __construct() {
		add_action( 'admin_menu' , array(&$this,'menu_item'));
		add_action( 'personal_options' , array(&$this,'add_user_fields') );
		add_action( 'personal_options_update' , array(&$this,'update_user') );
		add_action( 'edit_user_profile_update' , array(&$this,'update_user') );
		add_action( 'load-toplevel_page_memberlist' , array(&$this,'do_vcard') );
		add_action( 'load-users.php' , array(&$this,'do_vcard'));
		
		add_filter( 'mce_external_plugins' , array(&$this,"mce_memberlist_js") );
		add_filter( 'mce_buttons_2' , array(&$this,"mce_buttons") );
		
		add_action( 'load-post.php' , array(&$this,'editor') );
		add_action( 'load-post-new.php' , array(&$this,'editor') );
		
		add_filter( 'manage_users_columns' ,  array( &$this , 'add_user_list_vcard_column' ) );
		add_filter( 'manage_users_custom_column' , array(&$this , 'user_list_vcard_column') , 10 ,3 );
	}
	
	/* mce functions */
	function editor() {
		wp_enqueue_style( 'memberlist-editor' , plugins_url('/css/editor.css',dirname(__file__)) );
	}
	function mce_buttons( $buttons ) {
		$buttons[] = "|";
		$buttons[] = "members";
		return $buttons;
	}
	function mce_memberlist_js( $plugins_array ){
		$plugins_array['members'] = plugins_url('/js/mce-memberlist.js',dirname(__file__));
		return $plugins_array;
	}
	
	
	function menu_item() {
		add_menu_page(__('Members','wp-memberlist'),__('Members','wp-memberlist'),'read','memberlist',array(&$this,'admin_page'),'',50);
	}
	function admin_page() {
		$links = array( $this->link_shortcode(array('label'=>__('Mail everybody','wp-memberlist'))) );
		$links = apply_filters('memberlist_mail_links',$links );
		?><div class="wrap"><?php
		?><h2><?php _e('Members','wp-memberlist'); ?></h2><?php
			if ( $links ) {
				?><ul class="subsubsub"><?php
					foreach ( $links as $i=>$link ) {
						?><li><?php 
						echo $link;
						if ( $i < (count($links)-1) ) 
							echo ' | ';
						?></li><?php
					}
				?></ul><?php
			}
		
			add_filter("memberlist_fields",array(&$this,'add_vcard_field'));
			add_filter("memberlist_vcard_field" , array(&$this,'vcard_field') , 10 , 2 );
			
			echo Memberlist::instance()->shortcode( array('capability'=>'read','class'=>array( 'wp-list-table','widefat' )) );
		?></div><?php
	}
	
	/* user list table */
	function add_user_list_vcard_column($columns) {
		$columns['vcard'] = __('VCard','wp-memberlist');
		return $columns;
	}
	function user_list_vcard_column( $column_content , $column_name , $user_ID ) {
		if ( $column_name == 'vcard' ) {
			$wp_user = new WP_User($user_ID);
			return $this->vcard_field( '' , $wp_user);
		}
		return $column_content;
	}
	/* memberlist page */
	function add_vcard_field($fields) {
		$fields['vcard'] = array(
			'label' => __('VCard','wp-memberlist'),
			'description' => '',
			'type'	=> 'none',
			'placement' => 'after',
		);
		return $fields;
	}
	function vcard_field($value,$user) {
		$vcard_link = add_query_arg('vcard',$user->ID);
		$icon = plugins_url('img/vcard-icon-small.png',dirname(__FILE__));
		return sprintf('<a title="%s" href="%s"><img src="%s" alt="icon" /></a>',__('Download vCard','wp-memberlist'),$vcard_link,$icon);
	}
	function do_vcard( ) {
		if ( isset($_REQUEST['vcard']) && $user = new WP_User( $_REQUEST['vcard'] ) ) {
			if ( ! get_option('memberlist_require_login') || is_user_logged_in() ) {
				include plugin_dir_path(__FILE__)."vCard/vCard.class.php";
				
				$fields = Memberlist::instance()->get_fields();
				$vcard = new vCard();
				foreach ( array('first_name'=>'first_name','last_name'=>'last_name','display_name'=>'display_name', 'user_email' =>'email1') as $wp_prop => $vc_prop ) 
					if ( $value = $user->$wp_prop ) 
						$vcard->set($vc_prop, $value);
						
				foreach ( $fields as $key => $field )  {
				$var = "memberlist_{$key}";
				if ( isset($field['vcard_prop']) && ( $vc_prop = $field['vcard_prop']) && ( $value = $user->$var ) ) {
						$vcard->set($vc_prop, $value);
					}
				}

				if ( $vcard->download() )
					exit();
			}
		} else {
			if ( ! is_user_logged_in() ) 
				$ret = sprintf(__('Please <a href="%s">login</a>','wp-memberlist'),wp_login_url());
			else 
				$ret = __('Insufficient Privileges');
		}
	}
	function add_query_var_vcard( $vars ) {
		$vars[] = 'vcard';
		return $vars;
	}
	
	function link_shortcode( $atts , $content=null ) {
		extract(wp_parse_args( $atts, array(
			'label' => '',
		)));
		$emails = array();
		$users = get_users( );
		$fields = Memberlist::instance()->get_fields();
		$filter = array();
		foreach ( array_keys($fields) as $key )
			if ( isset( $$key ) ) 
				$filter["memberlist_{$key}"] = $$key;
			
		foreach ( $users as $user )	{
			if ( $filter ) {
				$addit = true;
				foreach ( $filter as $prop=>$val )
					$addit = $addit && $user->$prop == $val;
				if ($addit)
					$emails[]=$user->user_email;
			} else {
				$emails[]=$user->user_email;
			}
		}
		return sprintf( '<a href="mailto:%s">%s</a>' , implode(',',$emails) , $label );
	}

	function add_user_fields( $profileuser ) {
		$memberlist_fields = Memberlist::instance()->get_fields();

		foreach ( $memberlist_fields as $key => $item ) {
			extract( $item );
			?><th scope="row"><?php echo $label ?></th>
			<td><?php 
			
				?><fieldset><legend class="screen-reader-text"><?php
				?><span><?php echo $label ?></span></legend><?php
				switch ($type) {
					case 'bool':
					case 'boolean':
					case 'checkbox':
						?><label for="memberdata-<?php echo $key ?>">
							<input type="hidden" name="memberlist_<?php echo $key ?>" value="0" />
							<input name="memberlist_<?php echo $key ?>" type="checkbox" id="memberdata-<?php echo $key ?>" value="1" <?php checked( get_user_meta($profileuser->ID , "memberlist_{$key}" , true ) , 1 , true ) ?> />
							<?php
						
							if ( $description )
								echo $description;

						?></label><br /><?php
						break;
					case 'text':
						?><label for="memberdata-<?php echo $key ?>">
							<input class="regular-text" name="memberlist_<?php echo $key ?>" type="text" id="memberdata-<?php echo $key ?>" value="<?php echo get_user_meta($profileuser->ID , "memberlist_{$key}" , true ) ?>" />
						</label><br /><?php
						if ( $description ) {
							?><p class="description"><?php echo $description ?></p><?php
						}
						break;
				}
				?></fieldset><?php
			?></td>
			</tr><?php
		}
	}


	function update_user( $user_ID ) {
		$memberlist_fields = Memberlist::instance()->get_fields();
		foreach ( array_keys($memberlist_fields) as $key )
			if ( isset($_POST["memberlist_{$key}"]) )
				update_user_meta($user_ID , "memberlist_{$key}" , $_POST["memberlist_{$key}"]);
	}

}


MemberlistAdmin::instance();

