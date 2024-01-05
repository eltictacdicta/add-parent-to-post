(function() {
    tinymce.PluginManager.add('cluster-child-button', function( editor, url ) {
        editor.addButton( 'cluster-child-button', {
            text: 'Añadir shortcode hijas',
            icon: 'mce-ico mce-i-pluscircle',
            onclick: function() {
                editor.windowManager.open( {
                    title: 'Añsdir shortcode hijas',
                    body: [
                        /*{
                            type: 'listbox',
                            label: 'Size',
                            name: 'size',
                            values: [
                                { text: 'Small', value: 'small' },
                                { text: 'Regular', value: 'regular' },
                                { text: 'Wide', value: 'wide' },
                                { text: 'Large', value: 'large' },
                            ],
                            value: ''
                        },*/
                        
                        {
                            type: 'textbox',
                            label: 'Titulo',
                            name: 'titulo',
                            // tooltip: 'Some nice tooltip to use',
                            value: ' También te puede interesar:'
                        },
                        
                    ],
                    onsubmit: function( e ) {
                        editor.insertContent( '[mi_child_pages titulo="' + e.data.titulo + '"]');
                    }
                });
            },
        });
        
    });
    

})();
