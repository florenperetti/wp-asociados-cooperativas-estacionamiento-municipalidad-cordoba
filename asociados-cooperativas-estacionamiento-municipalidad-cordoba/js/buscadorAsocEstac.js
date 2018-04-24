(function(window, document, $) {

  const $ACEM = $('#ACEM');
  const $form = $ACEM.find('form');
  const $reset = $ACEM.find('#filtros__reset');
  const $html = $('html,body');
  let $resultados = $ACEM.find('.resultados');
  let $resultadosContainer = $resultados.find('.resultado__container');
  let $gifCarga = $resultados.find('.cargando');
  let $paginacion = $resultados.find('.paginacion');

  $reset.click(function(e) {
    e.preventDefault();
    $form[0].reset();
    $form.submit();
  });

  const iniciarCarga = () => {
    $html.animate({scrollTop: $ACEM.offset().top-100},'slow');
    $resultadosContainer.hide();
    $paginacion.hide();
    $gifCarga.show();
  }

  const referenciar = () => {
    $resultados = $('#ACEM .resultados');
    $resultadosContainer = $resultados.find('.resultado__container');
    $gifCarga = $resultados.find('.cargando');
    $paginacion = $resultados.find('.paginacion');
  }

  $form.submit(function(e) {
    e.preventDefault();
    const datos = $form.serializeArray();
    console.log(datos);
    iniciarCarga();
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarAsocEstac.url,
      data: {
        action: 'buscar_asociado_estac',
        nonce: buscarAsocEstac.nonce,
        q: datos[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
          referenciar();
        }
      }
    });
  });

  $(document).on('click','#ACEM .paginacion__boton', function(e) {
    const pagina = $(this).data('pagina');
    const $boton = $(e.target);
    const texto = $boton.html();
    $boton.html('...');
    const datos = $form.serializeArray()
    iniciarCarga();
    $.ajax({
      type: "POST",
      dataType: "JSON",
      url: buscarAsocEstac.url,
      data: {
        action: 'buscar_asociado_estac_pagina',
        nonce: buscarAsocEstac.nonce,
        pagina: pagina,
        q: datos[0].value
      },
      success: function(response) {
        if (response.data) {
          $resultados.html(response.data);
          referenciar();
          $('body').animate({scrollTop: 50}, 1000);
        }
      },
      done: function() {
        $boton.html(texto);
      }
    });
  });
})(window, document, jQuery);