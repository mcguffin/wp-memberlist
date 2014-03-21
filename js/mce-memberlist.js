tinymce.PluginManager.add( 'members' , function( editor ){

	editor.addCommand('InsertMemberlist', function() {
		
		editor.execCommand('mceInsertContent', false, '[members]');
	});

	editor.addButton('members', {
		icon: 'memberlist',
		tooltip: 'Memberlist',
		cmd: 'InsertMemberlist'
	});

	editor.addMenuItem('members', {
		icon: 'memberlist',
		text: 'memberlist',
		cmd: 'InsertMemberlist',
		context: 'insert'
	});


} );
