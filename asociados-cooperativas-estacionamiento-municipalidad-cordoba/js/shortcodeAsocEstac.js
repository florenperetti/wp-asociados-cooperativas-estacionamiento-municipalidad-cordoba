(function() {
  tinymce.create('tinymce.plugins.buscasocestaccba_button', {
    init: function(ed, url) {
      ed.addCommand('buscasocestaccba_insertar_shortcode', function() {
        //selected = tinyMCE.activeEditor.selection.getContent();
        //var content = '';
        ed.insertContent( '[buscador_asociado_estac_cba]' );
        /*
        ed.windowManager.open({
          title: 'Buscador de Guías Turísticos',
          body: [{
            type: 'textbox',
            name: 'pag',
            label: 'Cantidad de Resultados'
          }],
          onsubmit: function(e) {
            var pags = Number(e.data.pag.trim());
            ed.insertContent( '[buscador_asociado_estac_cba' + (pags && Number.isInteger(pags) ? ' pag="'+pags+'"' : '') + ']' );
          }
        });
        tinymce.execCommand('mceInsertContent', false, content);*/
      });
      ed.addButton('buscasocestaccba_button', {title : 'Insertar buscador de Naranjitas', cmd : 'buscasocestaccba_insertar_shortcode', image: url.replace('/js', '') + '/images/logo-shortcode.png' });
    }
  });
  tinymce.PluginManager.add('buscasocestaccba_button', tinymce.plugins.buscasocestaccba_button);
})();