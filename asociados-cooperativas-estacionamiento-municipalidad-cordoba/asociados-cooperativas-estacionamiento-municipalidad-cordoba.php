<?php
/*
Plugin Name: Buscador de Asociados de Cooperativas de Estacionamiento de la Municipalidad de C&oacute;rdoba
Plugin URI: https://github.com/ModernizacionMuniCBA/plugin-wordpress-asociado-estacionamiento-municipales
Description: Este plugin a&ntilde;ade un shortcode que genera un buscador de los naranjitas de la Municipalidad de C&oacute;rdoba.
Version: 1.0.0
Author: Florencia Peretti
Author URI: https://github.com/florenperetti/wp-asociados-cooperativas-estacionamiento-municipalidad-cordoba
*/

add_action('plugins_loaded', array('AsociadoCoopEstacMuniCordoba', 'get_instancia'));

class AsociadoCoopEstacMuniCordoba
{
	public static $instancia = null;
	
	public static $array_cooperativas = null;

	private static $URL_API_GOB = 'https://gobiernoabierto.cordoba.gob.ar/api/v2/cooperativas-estacionamiento/asociados-cooperativas-de-estacionamiento/';

	public $nonce_busquedas = '';

	public static function get_instancia()
	{
		if (null == self::$instancia) {
			self::$instancia = new AsociadoCoopEstacMuniCordoba();
		} 
		return self::$instancia;
	}

	private function __construct()
	{
		add_action('wp_enqueue_scripts', array($this, 'cargar_assets'));

		add_action('wp_ajax_buscar_asociado_estac', array($this, 'buscar_asociado_estac')); 
		add_action('wp_ajax_nopriv_buscar_asociado_estac', array($this, 'buscar_asociado_estac'));
		
		add_action('wp_ajax_buscar_asociado_estac_pagina', array($this, 'buscar_asociado_estac_pagina')); 
		add_action('wp_ajax_nopriv_buscar_asociado_estac_pagina', array($this, 'buscar_asociado_estac_pagina'));
		
		add_shortcode('buscador_asociado_estac_cba', array($this, 'render_shortcode_buscador_asociado_estac'));

		add_action('init', array($this, 'boton_shortcode_buscador_asociado_estac'));
	}

	public function render_shortcode_buscador_asociado_estac($atributos = [], $content = null, $tag = '')
	{
	    $atributos = array_change_key_case((array)$atributos, CASE_LOWER);
	    $atr = shortcode_atts([
            'pag' => 12
        ], $atributos, $tag);

	    $cantidad_por_pagina = $atr['pag'] == 0 ? '' : '?page_size='.$atr['pag'];

	    $url = self::$URL_API_GOB.$cantidad_por_pagina;

    	$api_response = wp_remote_get($url, [ 'timeout' => 10 ]);

    	$resultado = $this->chequear_respuesta($api_response, 'los gu&iacute;as', 'asociado_estac_muni_cba');
		
		$sc = '<div id="ACEM">
	<form>
		<div class="filtros">
			<div class="filtros__columnas">
				<label class="filtros__label" for="q">Nombre o DNI</label>
				<input type="text" id="q" name="q" placeholder="Nombre, apellido o DNI">
			</div>
			<div class="filtros__columnas">';
		if (count(self::$array_cooperativas)>0) {
			$sc .= '<label class="filtros__label" for="cooperativa_id">Cooperativa</label>
					<select id="cooperativa_id" name="cooperativa_id"><option value="0">Todos</option>';
			foreach (self::$array_cooperativas as $key => $cooperativa) {
				$sc .= '<option value="'.$cooperativa['id'].'">'.$cooperativa['nombre'].'</option>';
			}
			$sc .= '</select>';
		}
		
		$sc .= '
			</div>
			<div class="filtros__columnas">
				<button id="filtros__buscar" type="submit">Buscar</button>
				<button id="filtros__reset">Todos</button>
			</div>
		</div>
	</form>
	<div class="resultados">';
		$sc .= $this->renderizar_resultados($resultado,$atr['pag']);
		$sc .= '</div></div>';
		return $sc;
	}
	
	private function renderizar_resultados($datos,$pag = 12,$query='')
	{
		$html = '';
		
		if (count($datos['results']) > 0) {
			$html .= '<p class="cantidad-resultados"><small><a href="https://gobiernoabierto.cordoba.gob.ar/data/datos-abiertos/categoria/legislacion/cooperativas-para-atencion-del-estacionamiento-controlado/239" rel="noopener" target="_blank"><b>&#161;Descarg&aacute; toda la informaci&oacute;n&#33;</b></a></small>
				<small>Mostrando '.count($datos['results']).' de '.$datos['count'].' resultados</small></p>';
			$html .= '<div class="cargando" style="display:none;"><img alt="Cargando..." src="'.plugins_url('images/loading.gif', __FILE__).'"></div>';
			$html .= '<div class="resultados__container">';
			
			foreach ($datos['results'] as $key => $asociado) {
				
				$foto = '';
				
				$nombre = $asociado['nombre'].' '.$asociado['apellido'];
				
				if (isset($asociado['foto'])) {
					$foto = '<div class="resultado__foto">
							<img alt="'.$nombre.'" src="'.$asociado['foto']['thumbnail'].'">
						</div>';
				}
				
				$html .= '<div class="resultado__container">
						<div class="resultado__cabecera">
							<img class="resultado__logo" alt="Control estacionamiento p&uacute;blico" src="https://www.cordoba.gob.ar/wp-content/uploads/2017/10/logo-controladores-estacionamiento-publico.png" />
							'.$foto.'
						</div>
						<div class="resultado__info">
							<span class="resultado__nombre">'.$nombre.'</span>
							<span class="resultado__dni"><b><small>DNI '.$asociado['dni'] .'</small></span></b>
							<div class="info__dato">
								<b>Cooperativa de trabajo:</b>
								<div class="dato__fondo">
								    <span><small>'.str_replace("Cooperativa de Trabajo","",$asociado['cooperativa']).'</small></span>
								</div>
							</div>
							<div class="info__dato">
								<b>N&uacute;mero de asociado:</b>
								<div class="dato__fondo">
								    <span><small>'.$asociado['numero_de_asociado'].'</small></span>
								</div>
							</div>
						</div>
					</div>';
			}
			$html .= '</div>';
			
			if ($datos['next'] != 'null' || $datos['previous'] != 'null') {
				$html .= $this->renderizar_paginacion($datos['previous'], $datos['next'], ($pag ? 12 : $pag), $datos['count'], $query);
			}
			
		} else {
			$html .= '<p class="resultados__mensaje">No hay resultados</p>';
		}
		
		return $html;
	}
	
	public function renderizar_paginacion($anterior, $siguiente, $tamanio, $total, $query)
	{
		$html = '<div class="paginacion">';
		
		$botones = $total % $tamanio == 0 ? $total / $tamanio : ($total / $tamanio) + 1;

		$actual = 1;
		if ($anterior != null) {
			$actual = $this->obtener_parametro($anterior,'page', 1) + 1;;
		} elseif ($siguiente != null) {
			$actual = $this->obtener_parametro($siguiente,'page', 1) - 1;
		}

		$query = $this->getFiltro($query);
		for	($i = 1; $i <= $botones; $i++) {
			if ($i == $actual) {
				$html .= '<button type="button" class="paginacion__boton paginacion__boton--activo" disabled>'.$i.'</button>';
			} else {
				$html .= '<button type="button" class="paginacion__boton" data-pagina="'.self::$URL_API_GOB.'?page='.$i.'&page_size='.$tamanio.$query.'">'.$i.'</button>';
			}
		}
		
		$html .= '</div>';
		
		return $html;
	}

	public function boton_shortcode_buscador_asociado_estac()
	{
		if (!current_user_can('edit_posts') && !current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
			return;

		add_filter("mce_external_plugins", array($this, "registrar_tinymce_plugin")); 
		add_filter('mce_buttons', array($this, 'agregar_boton_tinymce_shortcode_buscador_asociado_estac'));
	}

	public function registrar_tinymce_plugin($plugin_array)
	{
		$plugin_array['buscasocestaccba_button'] = $this->cargar_url_asset('/js/shortcodeAsocEstac.js');
	    return $plugin_array;
	}

	public function agregar_boton_tinymce_shortcode_buscador_asociado_estac($buttons)
	{
	    $buttons[] = "buscasocestaccba_button";
	    return $buttons;
	}

	public function cargar_assets()
	{
		$urlJSBuscador = $this->cargar_url_asset('/js/buscadorAsocEstac.js');
		$urlCSSBuscador = $this->cargar_url_asset('/css/shortcodeAsocEstac.css');
		
		wp_register_style('buscador_asociado_estac_cba.css', $urlCSSBuscador);
		wp_register_script('buscador_asociado_estac_cba.js', $urlJSBuscador);
		
		global $post;
	    if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'buscador_asociado_estac_cba') ) {
			wp_enqueue_script(
				'buscar_asociado_estac_ajax', 
				$urlJSBuscador, 
				array('jquery'), 
				'1.0.0',
				TRUE
			);
			wp_enqueue_style('buscador_asociado_estac.css', $this->cargar_url_asset('/css/shortcodeAsocEstac.css'));
			
			$nonce_busquedas = wp_create_nonce("buscar_asociado_estac_nonce");
			
			wp_localize_script(
				'buscar_asociado_estac_ajax', 
				'buscarAsocEstac', 
				array(
					'url'   => admin_url('admin-ajax.php'),
					'nonce' => $nonce_busquedas
				)
			);
		}
	}
	
	public function buscar_asociado_estac()
	{
		$q = $this->getFiltro($_REQUEST['q']);
		check_ajax_referer('buscar_asociado_estac_nonce', 'nonce');

		if(true) {
			$api_response = wp_remote_get(self::$URL_API_GOB.'?page_size=12'.$q, [ 'timeout' => 10 ]);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			wp_send_json_success($this->renderizar_resultados($api_data,12,$q));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}
	
	public function buscar_asociado_estac_pagina()
	{
		$pagina = $_REQUEST['pagina'];
		$q = $this->getFiltro($_REQUEST['q']);

		check_ajax_referer('buscar_asociado_estac_nonce', 'nonce');

		if(true && $pagina !== '') {
			$api_response = wp_remote_get($pagina.$q, [ 'timeout' => 10 ]);
			$api_data = json_decode(wp_remote_retrieve_body($api_response), true);
			
			wp_send_json_success($this->renderizar_resultados($api_data,12,$q));
		} else {
			wp_send_json_error($api_data);
		}
		
		die();
	}

	/*
	* Mira si la respuesta es un error, si no lo es, cachea por una hora el resultado.
	*/
	private function chequear_respuesta($api_response, $tipoObjeto)
	{
		if (is_null($api_response)) {
			return [ 'results' => [] ];
		} else if (is_wp_error($api_response)) {
			$mensaje = WP_DEBUG ? ' '.$this->mostrar_error($api_response) : '';
			return [ 'results' => [], 'error' => 'Ocurri&oacute; un error al cargar '.$tipoObjeto.'.'.$mensaje];
		} else {
			return json_decode(wp_remote_retrieve_body($api_response), true);
		}
	}


	/* Funciones de utilidad */

	private function mostrar_error($error)
	{
		if (WP_DEBUG === true) {
			return $error->get_error_message();
		}
	}

	private function formatear_fecha($original)
	{
		return date("d/m/Y", strtotime($original));
	}

	private function cargar_url_asset($ruta_archivo)
	{
		return plugins_url($this->minified($ruta_archivo), __FILE__);
	}

	// Se usan archivos minificados en producción.
	private function minified($ruta_archivo)
	{
		if (WP_DEBUG === true) {
			return $ruta_archivo;
		} else {
			$extension = strrchr($ruta_archivo, '.');
			return substr_replace($ruta_archivo, '.min'.$extension, strrpos($ruta_archivo, $extension), strlen($extension));
		}
	}
	
	private function obtener_parametro($url, $param, $fallback)
	{
		$partes = parse_url($url);
		parse_str($partes['query'], $query);
		$resultado = $query[$param] ? $query[$param] : $fallback;
		return $resultado;
	}

	private function getFiltro($valor)
	{
		$trimmed = trim($valor);
		if (empty($trimmed)) {
			return '';
		}
		return is_numeric($trimmed) ? "&n=".str_replace([".",",	"], "", $trimmed) : "&q=".$trimmed;
	}
}
