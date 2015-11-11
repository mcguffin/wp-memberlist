# WordPress Memberlist #

Extends WordPress default user data by physical address. Shows a memberlist in the backend.
Adds a member page post type, where a member has the ability to edit ones personal page.

Tested with up to WP 4.0-beta3

## Plugin API: ##

### Filter `memberlist_fields` ###

Add or remove properties.
```
// Example:
function my_fields( $fields ) {
	$fields['another_text_field'] = array(
		'label' => 'Thing',
		'description' => 'Add some information about Your thing',
		'type'	=> 'text',
		'placement' => 'after',
	);
	$fields['another_checkbox_field'] = array(
		'label' => 'Yes or no',
		'description' => 'Choose between yes and no',
		'type'	=> 'checkbox',
		'placement' => 'after',
	);
	
	return $fields;
}
add_filter('memberlist_fields','my_fields');`
```

### Filter `memberlist_mail_links` ###

Add mailto links above members list.
```
// Example:
function memberlist_mail_links( $links ) {
	$links[] = '<a href="http://127.0.0.1">Go home</a>';
	return $links;
}
add_filter('memberlist_mail_links','memberlist_mail_links');
```

### Filter `memberlist_print_{$key}` ###

Enable or disable certain fields.
```
// Example:
add_filter('memberlist_print_secret','__return_false');`
```

### Filter: `memberlist_{$key}_field` ###

Control field output.
```
// Example:
function my_thing_output( $field_content , $user ) {
	if ( empty( $field_content ) )
		return 'No information on this property';
	return $field_content;
}
add_filter('memberlist_thing_field','my_thing_output',10,2);
```

## ToDo ##
 - [ ] Checkbox User is member
 - [ ] Populate userdata by vcard upload
 - [ ] Make MemberPages optional
 - [ ] ...