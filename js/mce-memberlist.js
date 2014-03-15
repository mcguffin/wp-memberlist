/*
tinymce.create( 'tinymce.plugins.Members' , {
	init:function( editor ) {
		console.log(arguments);
		editor.addCommand( 'members_cmd' , function() {
			editor.insertContent('[members]');
		});
		editor.addButton('members', {
			tooltip : 'Insert Members', 
			cmd : 'members_cmd' 
		});
		editor.addMenuItem( 'members', {
			text: 'Insert Memberlist',
			icon: false,
			context: 'insert',
			onclick: function() {
				editor.execCommand( 'members_cmd' );
			}
		});
	}
});*/
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
