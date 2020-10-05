<?php
	
/*
//Plugin Name: BZ Optimize
//Plugin URI: http://none
//Description: Conditionally defer or remove chosen scripts and styles.
//Version: 1.0
//Author: Boris Zhuk
//Author URI: http://t.me/b_zhuk
*/

class BZOptimize {
	private $opts;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'bz_optimize_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'bz_optimize_page_init' ) );
	}

	public function bz_optimize_add_plugin_page() {
		add_management_page(
			'BZ Optimize', // page_title
			'BZ Optimize', // menu_title
			'manage_options', // capability
			'bz-optimize', // menu_slug
			array( $this, 'bz_optimize_create_admin_page' ) // function
		);
	}

	public function bz_optimize_create_admin_page() {
		$this->bz_optimize_options = get_option( 'bz_optimize_option_name' ); ?>
		<script>
		jQuery(document).ready(($)=>{
			$('form>h2').each((i,el)=>{
				$(el).attr('data-id', i);
			});
			$('.form-table').hide();
			$('.form-table').eq(0).show();
			$('form>h2').on('click',(e)=>{
				$('.form-table').hide();
				$('.form-table').eq($(e.target).attr('data-id')).show();
			});
		});
		</script>
		<style>
			form>h2{
				text-decoration:underline;
				cursor: pointer;
			}
		</style>
		<div class="wrap">
			<h2>BZ Optimize</h2>
			<p>Type 'keywords' (any part of URL) for styles and scripts you'd like to defer until page load or remove, separated by spaces.</p>
			<p>You can make page-specific lists by typing $PAGE_ID before keywords; $0 is for home page;
			<br>Example. Select "font-awesome", "captcha", "menu" for all pages; "comments" and "social" at home page; "woocommerce" at page with ID 22:
			<br><code>font-awesome captcha menu $0 comments social $22 woocommerce</code>	
			</p>
			<p>You can also wrap any css/js declarations you need to defer in <code>&lt;noscript data-load='defer'&gt;&lt;/noscript&gt;</code></p>
			<p>Use with other optimization plugins at your own risk!</p>
			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
					settings_fields( 'bz_optimize_option_group' );
					do_settings_sections( 'bz-optimize-admin' );
					submit_button();
				?>
			</form>
		</div>
	<?php }

	public function bz_optimize_page_init() {
		register_setting(
			'bz_optimize_option_group', // option_group
			'bz_optimize_option_name', // option_name
			array( $this, 'bz_optimize_sanitize' ) // sanitize_callback
		);

		add_settings_section(
			'bz_optimize_setting_section', // id
			'Defer', // title
			array( $this, 'bz_optimize_section_info' ), // callback
			'bz-optimize-admin' // page
		);
		
		add_settings_section(
			'bz_optimize_setting_section_replace', // id
			'Replace', // title
			array( $this, 'bz_optimize_section_info_replace' ), // callback
			'bz-optimize-admin' // page
		);
		
		add_settings_section(
			'bz_optimize_setting_section_remove', // id
			'Remove', // title
			array( $this, 'bz_optimize_section_info_remove' ), // callback
			'bz-optimize-admin' // page
		);

		add_settings_field(
			'styles_0', // id
			'styles to defer (add on load)', // title
			array( $this, 'styles_0_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section' // section
		);

		add_settings_field(
			'scripts_1', // id
			'scripts to defer (add on load)', // title
			array( $this, 'scripts_1_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section' // section
		);
		
		add_settings_field(
			'scripts_onscroll', // id
			'code to load on first SCROLL', // title
			array( $this, 'scripts_onscroll_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section' // section
		);
		
		add_settings_field(
			'custom_code', // id
			'custom code to defer<br>[html, style & script tags are up to you]', // title
			array( $this, 'custom_code_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section' // section
		);
		
		add_settings_field(
			'defer_all_scripts_2', // id
			'defer_all_scripts', // title
			array( $this, 'defer_all_scripts_2_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section' // section
		);
		
		//Section 2
		
		add_settings_field(
			'replace_styles', // id
			'Replace styles', // title
			array( $this, 'replace_styles_callback'), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section_replace' // section
		);
		
		//Section 3
		
		add_settings_field(
			'styles_3', // id
			'styles to remove [must match link tag\'s id]', // title
			array( $this, 'styles_3_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section_remove' // section
		);

		add_settings_field(
			'scripts_4', // id
			'scripts to remove [must match script tag\'s id]', // title
			array( $this, 'scripts_4_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section_remove' // section
		);
		
		add_settings_field(
			'emoji', // id
			'Emoji?', // title
			array( $this, 'emoji_callback' ), // callback
			'bz-optimize-admin', // page
			'bz_optimize_setting_section_remove' // section
		);
	}

	public function bz_optimize_sanitize($input) {
		$sanitary_values = array();
		if ( isset( $input['styles_0'] ) ) {
			$sanitary_values['styles_0'] = esc_textarea( $input['styles_0'] );
		}

		if ( isset( $input['scripts_1'] ) ) {
			$sanitary_values['scripts_1'] = esc_textarea( $input['scripts_1'] );
		}
		
		if ( isset( $input['scripts_onscroll'] ) ) {
			$sanitary_values['scripts_onscroll'] = esc_textarea( $input['scripts_onscroll'] );
		}

		if ( isset( $input['defer_all_scripts_2'] ) ) {
			$sanitary_values['defer_all_scripts_2'] = $input['defer_all_scripts_2'];
		}
		
		if ( isset( $input['styles_3'] ) ) {
			$sanitary_values['styles_3'] = esc_textarea( $input['styles_3'] );
		}

		if ( isset( $input['scripts_4'] ) ) {
			$sanitary_values['scripts_4'] = esc_textarea( $input['scripts_4'] );
		}
		
		if ( isset( $input['custom_code'] ) ) {
			$sanitary_values['custom_code'] = esc_textarea( $input['custom_code'] );
		}
		
		if ( isset( $input['replace_styles'] ) ) {
			$sanitary_values['replace_styles'] = esc_textarea( $input['replace_styles'] );
		}

		return $sanitary_values;
	}

	public function bz_optimize_section_info() {
		
	}
	
	public function bz_optimize_section_info_replace() {
		echo '<p>Print styles you want to replace with your own.<br>The format is:<code>$keyword url_or_css</code></p>';
	}
	
	public function bz_optimize_section_info_remove() {
		
	}

	public function styles_0_callback() {
		printf(
			'<textarea class="large-text" rows="5" name="bz_optimize_option_name[styles_0]" id="styles_0">%s</textarea>',
			isset( $this->bz_optimize_options['styles_0'] ) ? esc_attr( $this->bz_optimize_options['styles_0']) : ''
		);
	}

	public function scripts_1_callback() {
		printf(
			'<textarea class="large-text" rows="5" name="bz_optimize_option_name[scripts_1]" id="scripts_1">%s</textarea>',
			isset( $this->bz_optimize_options['scripts_1'] ) ? esc_attr( $this->bz_optimize_options['scripts_1']) : ''
		);
	}
	
	public function scripts_onscroll_callback() {
		printf(
			'<textarea class="large-text" rows="10" name="bz_optimize_option_name[scripts_onscroll]" id="scripts_onscroll">%s</textarea>',
			isset( $this->bz_optimize_options['scripts_onscroll'] ) ? esc_attr( $this->bz_optimize_options['scripts_onscroll']) : ''
		);
	}

	public function defer_all_scripts_2_callback() {
		printf(
			'<input type="checkbox" name="bz_optimize_option_name[defer_all_scripts_2]" id="defer_all_scripts_2" value="defer_all_scripts_2" %s> <label for="defer_all_scripts_2">defer all scripts (beta, no function)</label>',
			( isset( $this->bz_optimize_options['defer_all_scripts_2'] ) && $this->bz_optimize_options['defer_all_scripts_2'] === 'defer_all_scripts_2' ) ? 'checked' : ''
		);
	}
	
	public function emoji_callback() {
		printf(
			'<input type="checkbox" name="bz_optimize_option_name[emoji]" id="emoji" value="emoji" %s> <label for="emoji">I DO Need Emoji</label>',
			( isset( $this->bz_optimize_options['emoji'] ) && $this->bz_optimize_options['emoji'] === true ) ? 'checked' : ''
		);
	}
	
	public function styles_3_callback() {
		printf(
			'<textarea class="large-text" rows="5" name="bz_optimize_option_name[styles_3]" id="styles_3">%s</textarea>',
			isset( $this->bz_optimize_options['styles_3'] ) ? esc_attr( $this->bz_optimize_options['styles_3']) : ''
		);
	}

	public function scripts_4_callback() {
		printf(
			'<textarea class="large-text" rows="5" name="bz_optimize_option_name[scripts_4]" id="scripts_4">%s</textarea>',
			isset( $this->bz_optimize_options['scripts_4'] ) ? esc_attr( $this->bz_optimize_options['scripts_4']) : ''
		);
	}
	
	public function custom_code_callback() {
		printf(
			'<textarea class="large-text" rows="10" name="bz_optimize_option_name[custom_code]" id="custom_code">%s</textarea>',
			isset( $this->bz_optimize_options['custom_code'] ) ? esc_attr( $this->bz_optimize_options['custom_code']) : ''
		);
	}
	
	public function replace_styles_callback() {
		printf(
			'<textarea class="large-text" rows="10" name="bz_optimize_option_name[replace_styles]" id="replace_styles">%s</textarea>',
			isset( $this->bz_optimize_options['replace_styles'] ) ? esc_attr( $this->bz_optimize_options['replace_styles']) : ''
		);
	}

}


function bz_opt_normalize($str){
	return str_replace(array("\r", "\n"), ' ', $str);
}

function bz_opt_strposa($haystack, $needle, $offset=0) {
		if(!$needle || empty($needle)) return false;
		if(!is_array($needle)) $needle = array($needle);
		if(empty($needle))return false;
		
		foreach($needle as $query) {
			if(empty($query)) continue;
			if(strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
		}
		return false;
	}

	
	//Front-end script
class BZOptimize_front {
	
	public $list = array();
	public $custom_code = false;
	public $onscroll_code = false;
	public $replace_styles = false;
	public $defer_all_scripts = false;
	
	public $deferred_styles = '';
	public $deferred_scripts = '';
	
	public function decode($key, $arr){
		$tmp = explode('$', $arr);
		$this->list[$key] = explode(' ', array_shift($tmp));
		
		if(!isset($this->list[$key.'_by_id']))$this->list[$key.'_by_id'] = array();
		
		if(!empty($tmp)){
			foreach($tmp as $cut){
				$cut_cut = explode(' ', $cut);
				$this->list[$key.'_by_id'][array_shift($cut_cut)] = $cut_cut;
			}
		}
		//echo 'list '.$key.'<br>';
		//var_dump($this->list[$key]);
		//echo '<br>by id:<br>';
		//var_dump($this->list[$key.'_by_id']);
		//echo '<br>';
	}
	
	public function bz_map($arr){
		$res = array();
		$arr = explode('$', $arr);
		foreach($arr as $case){
			$tmp = explode(' ', $case);
			$key = array_shift($tmp);
			$res[$key] = implode(' ', $tmp);
		}
		
		//echo 'REPLACE STYLES';
		//var_dump($res);
		
		return $res;
	}
	
	public function get_checklist($key){
		$keyby = $key.'_by_id';
		$tmp = $this->list[$key];
		if(isset($this->list[$keyby][get_the_ID()])){
			$tmp = array_merge($tmp, $this->list[$keyby][get_the_ID()]);
		}
		if((is_home() || is_front_page()) && isset($this->list[$keyby]['0'])){
			$tmp = array_merge($tmp, $this->list[$keyby]['0']);
		}
		
		return $tmp;
	}

	
	public function __construct() {
		$opts = get_option( 'bz_optimize_option_name' ); // Array of All Options
		
		$styles_0 = isset($opts['styles_0']) ? bz_opt_normalize($opts['styles_0']) : ''; // styles
		$scripts_1 = isset($opts['scripts_1']) ? bz_opt_normalize($opts['scripts_1']) : ''; // scripts
		$styles_3 = isset($opts['styles_3']) ? bz_opt_normalize($opts['styles_3']) : ''; // styles
		$scripts_4 = isset($opts['scripts_4']) ? bz_opt_normalize($opts['scripts_4']) : ''; // scripts
		//$defer_all_scripts_2 = $opts['defer_all_scripts_2']; // defer_all_scripts
	 
		$this->decode('defer_css', $styles_0);
		$this->decode('defer_js', $scripts_1);
		$this->decode('kill_css', $styles_3);
		$this->decode('kill_js', $scripts_4);
		
		if(isset($opts['custom_code']))
			$this->custom_code = htmlspecialchars_decode(wp_kses_decode_entities($opts['custom_code']));
		
		if(isset($opts['scripts_onscroll']))
			$this->onscroll_code = htmlspecialchars_decode(wp_kses_decode_entities($opts['scripts_onscroll']));
		
		if(isset($opts['replace_styles']))
			$this->replace_styles = $this->bz_map(htmlspecialchars_decode(wp_kses_decode_entities($opts['replace_styles'])));
		
		//echo 'bz_defer params:<br> --- CSS --- <br>';
		//var_dump($this->css_defer_list);
		//echo '<br>---- JS ----<br>';
		//var_dump($this->js_defer_list);
		
		if(!isset($opts['emoji']) || $opts['emoji'] === false)
			add_action( 'init', 'bz_opt_disable_emojis' );

		add_action( 'wp_enqueue_scripts', array($this, 'dequeue'), 100 );
		add_filter( 'style_loader_tag', array($this, 'defer_styles'), 1, 4 );
		add_filter('script_loader_tag', array($this, 'defer_scripts'), 1, 2);
		add_action('wp_footer', array($this, 'footer_load'), 1 );
	}

	public function defer_styles($html, $handle, $href, $media) {
		$replaced = false;
		foreach($this->replace_styles as $key=>$value){
			if(strpos($href, $key)!==false || strpos($handle, $key)!==false){
				if (strpos($value, 'http') === 0) {
					$href = $value;
				}else if(strpos($value, '<style') === 0){
					$html = $value;
					$replaced = true;
				}else{
					$html = '<style id="'.$handle.'">'.$value.'</style>';
					$replaced = true;
				}
			}
		}
		
		if(bz_opt_strposa($href, $this->get_checklist('defer_css'))!==false || bz_opt_strposa($handle, $this->get_checklist('defer_css'))!==false){	
			$this->deferred_styles .= $html;
			$html = "";
		}else if(!$replaced){
			$html = "
			<link rel='preload' as='style' href='$href' type='text/css' media='all' />
			<link rel='stylesheet' id='$handle'  href='$href' type='text/css' media='all' />
			";
		}
		return $html;
	}

	//SCRIPTS DEFER
	public function defer_scripts($tag, $handle) {
		
		if(bz_opt_strposa($tag, $this->get_checklist('defer_js'))!==false){
			$this->deferred_scripts .= $tag;
			return '';
		}
		
		//dont defer jquery 
		if(strpos($handle, 'jquery')!==false) 
			return $tag;
		else
			return str_replace( ' src', ' defer="defer" src', $tag );
	}

	public function footer_load(){
		if($this->custom_code){
			echo '<noscript data-load="defer" data-type="script">';
			echo html_entity_decode($this->custom_code);
			echo '</noscript>';
		}
		echo '<noscript data-load="defer">';
		if($this->deferred_styles !== ''){
			echo $this->deferred_styles;
		}
		if($this->deferred_scripts !== ''){
			echo $this->deferred_scripts;
		}
		echo '</noscript>';
		
		if($this->onscroll_code){
			echo '<noscript data-load="scroll">';
			echo html_entity_decode($this->onscroll_code);
			echo '</noscript>';
		}
		?>
		
		<script>
		let addScript = (dataNode)=>{
			let newScript = document.createElement("script");
			let inlineScript = document.createTextNode(dataNode.textContent);
			newScript.appendChild(inlineScript); 
			document.body.appendChild(newScript);
		}
		let addStyle = (el, par)=>{
			let val = el.textContent;
			console.log('deferred -> ');
			console.log(val);
			//if(el.parentNode)
				//el.parentNode.removeChild(el);
			par.innerHTML += val;
		}
		
		let nNode = document.createElement('div');
		var loadDeferredStyles = function() {
			var addStylesNode = document.querySelectorAll("noscript[data-load='defer']");
			
			nNode.classList.add('bz-defer-loaded');
			addStylesNode.forEach(
			(el)=>{
				if(el.getAttribute('data-type') === 'script'){
					addScript(el);
				}else{
					let timer = el.getAttribute('data-delay');
					if(timer)
						setTimeout(addStyle, timer, el,nNode)
					else
						addStyle(el, nNode);
				}
			});
			document.body.append(nNode);
		};
		window.addEventListener('load', function() { 
			if(requestAnimationFrame)
					requestAnimationFrame(loadDeferredStyles);
				else 
					window.setTimeout(loadDeferredStyles, 10); 
		});
		
		let addScrollNodes = () => {
			console.log('on scroll load -> ');
			let scrollNodes = document.querySelectorAll("noscript[data-load='scroll']");
			scrollNodes.forEach((el)=>{
				addScript(el);
			});
		}
		let bz_first_scroll = true;
		let on_fst_scroll = () => {
			if (bz_first_scroll) {
				bz_first_scroll = false;
				window.removeEventListener('scroll', on_fst_scroll);
				setTimeout(addScrollNodes, 1000);
			}
		};
		window.addEventListener('scroll', on_fst_scroll);
				
		</script>
	<?php
	}

	public function dequeue(){
		foreach($this->get_checklist('kill_css') as $hndl)
			wp_dequeue_style( $hndl );
		foreach($this->get_checklist('kill_js') as $hndl)
			wp_dequeue_script( $hndl );
	}
}

if ( is_admin() )
	$bz_optimize = new BZOptimize();
else{
	 $bz_optimize = new BZOptimize_front();
} 

/**
	* Disable the emoji's
 */
function bz_opt_disable_emojis() {
 remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
 remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
 remove_action( 'wp_print_styles', 'print_emoji_styles' );
 remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
 remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
 remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
 remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
 add_filter( 'tiny_mce_plugins', 'bz_opt_disable_emojis_tinymce' );
 add_filter( 'wp_resource_hints', 'bz_opt_disable_emojis_remove_dns_prefetch', 10, 2 );
}

/**
 * Filter function used to remove the tinymce emoji plugin.
 * 
 * @param array $plugins 
 * @return array Difference betwen the two arrays
 */
function bz_opt_disable_emojis_tinymce( $plugins ) {
 if ( is_array( $plugins ) ) {
 return array_diff( $plugins, array( 'wpemoji' ) );
 } else {
 return array();
 }
}

/**
 * Remove emoji CDN hostname from DNS prefetching hints.
 *
 * @param array $urls URLs to print for resource hints.
 * @param string $relation_type The relation type the URLs are printed for.
 * @return array Difference betwen the two arrays.
 */
function bz_opt_disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
 if ( 'dns-prefetch' == $relation_type ) {
 /** This filter is documented in wp-includes/formatting.php */
 $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );

$urls = array_diff( $urls, array( $emoji_svg_url ) );
 }

return $urls;
}