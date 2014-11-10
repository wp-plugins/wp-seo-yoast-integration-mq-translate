<?php
/**
 * @package Admin
 *
 * This code generates the metabox on the edit post / page as well as contains all page analysis functionality.
 */


if ( class_exists( 'WPSEO_Metabox' ) && ! class_exists( 'WPSEO_Metabox_wpSEOyoast_integr' ) ) {
	/**
	 * class WPSEO_Metabox_wpSEOyoast_integr
	 *
	 * This class extends funcionality to make compatible with the mqtranslate module
	 */
	class WPSEO_Metabox_wpSEOyoast_integr extends WPSEO_Metabox {

		private $default_lang;
		private $langs;
		private $current_lang;
		private $results;			// cache
		
		/**
		 * Class constructor
		 */
		function __construct() {
			parent::__construct();
			$this->default_lang = $GLOBALS['q_config']['default_language'];
			$this->langs = qtrans_getSortedLanguages();
			$this->results = array();
			
			// Declaring hooks
			add_filter('wpseo_pre_analysis_post_content', array($this, 'filter_content_prior_to_analisis'), 0, 2);
			add_filter('wpseo_title_filter_lang', array($this, 'filter_content_by_lang'), 0, 2);
		}
		
		
		/**
		 * Enqueues all the needed JS and CSS.
		 * @todo [JRF => whomever] create css/metabox-mp6.css file and add it to the below allowed colors array when done
		 */
		public function enqueue() {
			
			//  Wordpress automatically calls the parent
			//  parent::enqueue();
			
			global $pagenow;
			
			if ( $pagenow != 'edit.php' ) {
				
				wp_enqueue_style( 'metabox-integration', plugins_url(  '/' . IYWSM . '/css/metabox-integration' . '.css' ), array(), WPSEO_VERSION );
				
				wp_enqueue_script( 'wp-seo-metabox_integration', plugins_url( '/' . IYWSM . '/js/wp-seo-metabox_integration'  . '.js'), array(
				'jquery',
				'jquery-ui-core',
				'jquery-ui-autocomplete',
				), WPSEO_VERSION, true );
			
				
				// Text strings to pass to metabox for keyword analysis
				wp_localize_script( 'wp-seo-metabox_integration', 'wpseoMetaboxIntegration', $this->localize_script_integr() );		
			}
		}
		
		/**
		 * Sets up all the functionality related to the prominence of the page analysis functionality.
		 */
		public function setup_page_analysis(){
			parent::setup_page_analysis();
			
			// Remove action form the parent class
			remove_action( 'post_submitbox_misc_actions', array( $GLOBALS['old_wpseo_metabox']  , 'publish_box' ));
			
			// Remove action to avoid duplicate actions in bulk actions
			remove_action( 'restrict_manage_posts', array( $GLOBALS['old_wpseo_metabox'] , 'posts_filter_dropdown' ));
			
			$post_types = get_post_types( array( 'public' => true ), 'names' );
			if ( is_array( $post_types ) && $post_types !== array() ) {
				foreach ( $post_types as $pt ) {
					if ( $this->is_metabox_hidden( $pt ) === false ) {
						// add_filter( 'manage_' . $pt . '_posts_columns', array( $this, 'column_heading' ), 10, 1 );
						remove_action( 'manage_' . $pt . '_posts_custom_column', array( $GLOBALS['old_wpseo_metabox'], 'column_content', 10, 2 ) );
						/*
						add_action( 'manage_edit-' . $pt . '_sortable_columns', array(
						$this,
						'column_sort',
						), 10, 2 );
						*/
					}
				}
			}
		}
		
		
		/**
		 * Pass some variables to js for the edit / post page overview, snippet preview, etc.
		 *
		 * @return  array
		 */
		public function localize_script_integr() {
			
			// Assign Variables
			return array(
			  'default_lang' => $this->default_lang,
			  'langs' => $this->langs,
			);
			
		}

		
		/**
		 * Adds the WordPress SEO meta box to the edit boxes in the edit post / page  / cpt pages.
		 */
		public function add_meta_box() {
			$post_types = get_post_types( array( 'public' => true ) );
		
			if ( is_array( $post_types ) && $post_types !== array() ) {
				foreach ( $post_types as $post_type ) {
					if ( $this->is_metabox_hidden( $post_type ) === false ) {
						add_meta_box( 
							'wpseo_meta', 
							__( 'WP SEO by Yoast Integration with (m)qtranslate', 'wp-seo-yoast-integration-mqtranslate' ), 
							array( $this, 'meta_box',),
							$post_type, 
							'normal', 
							apply_filters( 'wpseo_metabox_prio', 'high' ) 
						);
					}
				}
			}
		}
		
		
		

		/**
		 * Output the meta box
		 */
		function meta_box() {
			
			global $q_config;
			
			$default_language = $this->default_lang; 
			
			if ( isset( $_GET['post'] ) ) {
				$post_id = (int) WPSEO_Option::validate_int( $_GET['post'] );
				$post    = get_post( $post_id );
			} else {
				global $post;
			}

			$options = WPSEO_Options::get_all();
			
			
			?> 
				<div class="attention">
					<h2> <?php echo __("Tips to use WP SEO by Yoast Integration with mqtranslate") ?></h2>
					<ul>
						<li><?php echo __("The plugin starts to work when you fill fields.")?></li>
						<li><?php echo __("This plugin is an Alpha Version. It needs test.")?></li>
						<li><?php echo __("If you find a bug or you want to improve the plugin, please, report it in the <a href=\"https://wordpress.org/support/plugin/wp-seo-yoast-integration-mq-translate\">support forum</a>.")?></li>
					</ul>
				</div>
			
				<div class="wpseo-metabox-tabs-div">  
					<ul class="wpseo-metabox-select-langs-tabs">
			<?php 
			// Header of tabs
			foreach ($this->langs as $l){
				?>
					<li class="wpseo-lang-<?php echo $l ?>">
						<a class="wpseo_tablink_lang" tab="#wpseo-metabox-lang-tabs-div-<?php echo $l ?>">
							<?php echo '<img src="' . trailingslashit(WP_CONTENT_URL) . $q_config['flag_location'] . $q_config['flag'][$l] . '" alt="' . $q_config['language_name'][$l] . '"/>'; ?>
							<?php echo qtrans_getLanguageName($l) ?>
						</a>
					</li>
				<?php
			}
			?> </ul> <?php
			
			
			// Content of tabs
			foreach ($this->langs as $l){
				
				?>
				<div class="wpseo-metabox-lang-tabs-div" id="wpseo-metabox-lang-tabs-div-<?php echo $l ?>">
					<ul class="wpseo-metabox-lang-tabs" id="wpseo-metabox-lang-tabs<?php if ($default_language != $l) echo "-" . $l ?>">
						<li class="general">
							<a class="wpseo_tablink" href="#wpseo_general<?php if ($default_language != $l) echo "-" . $l ?>"><?php _e( 'General', 'wordpress-seo' ); ?></a></li>
						<li id="linkdex" class="linkdex<?php if ($default_language != $l) echo "-" . $l ?>">
							<a class="wpseo_tablink" href="#wpseo_linkdex<?php if ($default_language != $l) echo "-" . $l ?>"><?php _e( 'Page Analysis', 'wordpress-seo' ); ?></a>
						</li>
						<?php if ( $default_language == $l && current_user_can( 'manage_options' ) || $options['disableadvanced_meta'] === false ): ?>
							<li class="advanced">
								<a class="wpseo_tablink" href="#wpseo_advanced<?php if ($default_language != $l) echo "-" . $l ?>"><?php _e( 'Advanced', 'wordpress-seo' ); ?></a>
							</li>
						<?php endif; ?>
						<?php  if ($default_language == $l) do_action( 'wpseo_tab_header' ); ?>
					</ul>
				
				<?php
				
				$content = '';
				if ( is_object( $post ) && isset( $post->post_type ) ) {
					foreach ( $this->get_meta_field_defs( 'general', $post->post_type ) as $key => $meta_field ) {
						$content .= $this->do_meta_box( $meta_field, $key, $l );
					}
				}
				
				$general_id = 'general';
				if ( $default_language != $l ) { $general_id .= "-" . $l; }
				$this->do_tab( $general_id, __( 'General', 'wordpress-seo' ), $content );
	
				$linkdex_id = 'linkdex';
				if ( $default_language != $l ) { $linkdex_id .= "-" . $l; }
				
				$this->do_tab( $linkdex_id, __( 'Page Analysis', 'wordpress-seo' ), $this->linkdex_output( $post, $l ) );
				
	
				if ( current_user_can( 'manage_options' ) || $options['disableadvanced_meta'] === false ) {
					$advanced_id = 'advanced';
					if ( $default_language != $l ) { $advanced_id .= "-" . $l; }
					$content = '';
					foreach ( $this->get_meta_field_defs( 'advanced' ) as $key => $meta_field ) {
						$content .= $this->do_meta_box( $meta_field, $key );
					}
					if ($default_language == $l){
						$this->do_tab( $advanced_id , __( 'Advanced', 'wordpress-seo' ), $content );
					}
				}
	
				if ($default_language == $l) do_action( 'wpseo_tab_content' );
				
				echo '</div> <!-- End of language content tab -->';
	
			}
			
			echo '</div>';
		}
		
		/**
		 * Adds a line in the meta box
		 *
		 * @todo [JRF] check if $class is added appropriately everywhere
		 *
		 * @param   array  $meta_field_def Contains the vars based on which output is generated.
		 * @param   string $key            Internal key (without prefix)
		 *
		 * @return  string
		 */
		function do_meta_box( $meta_field_def, $key = '', $lang = '' ) {
			
			if(empty($lang)){
			  return parent::do_meta_box( $meta_field_def, $key);
			}
			$lang_suffix = $this->get_lang_suffix($lang);
			
			$content = '';
			$esc_form_key = esc_attr( self::$form_prefix . $key );
			$esc_form_key .= $lang_suffix;
			
			$meta_value   = self::get_value( $key, 0, $lang );
		
			$class = '';
			if ( isset( $meta_field_def['class'] ) && $meta_field_def['class'] !== '' ) {
				$class = ' ' . $meta_field_def['class'];
			}
		
			$placeholder = '';
			if ( isset( $meta_field_def['placeholder'] ) && $meta_field_def['placeholder'] !== '' ) {
				$placeholder = $meta_field_def['placeholder'];
			}
		
			switch ( $meta_field_def['type'] ) {
			  case 'snippetpreview':
			  	if (empty($lang) || $lang == $this->default_lang){
			  		$content .= parent::snippet();
			  	}else{
			  		$content .= $this->snippet($lang);
			  	}
			  	break;
		
			  case 'text':
			  	$ac = '';
			  	if ( isset( $meta_field_def['autocomplete'] ) && $meta_field_def['autocomplete'] === false ) {
			  		$ac = 'autocomplete="off" ';
			  	}
			  	if ( $placeholder !== '' ) {
			  		$placeholder = ' placeholder="' . esc_attr( $placeholder ) . '"';
			  	}
			  	$content .= '<input type="text"' . $placeholder . '" id="' . $esc_form_key . '" ' . $ac . 'name="' . $esc_form_key . '" value="' . esc_attr( $meta_value ) . '" class="large-text' . $class . '"/><br />';
			  	break;
		
			  case 'textarea':
			  	$rows = 3;
			  	if ( isset( $meta_field_def['rows'] ) && $meta_field_def['rows'] > 0 ) {
			  		$rows = $meta_field_def['rows'];
			  	}
			  	$content .= '<textarea class="large-text' . $class . '" rows="' . esc_attr( $rows ) . '" id="' . $esc_form_key . '" name="' . $esc_form_key . '">' . esc_textarea( $meta_value ) . '</textarea>';
			  	break;
		
			  case 'select':
			  	if ( isset( $meta_field_def['options'] ) && is_array( $meta_field_def['options'] ) && $meta_field_def['options'] !== array() ) {
			  		$content .= '<select name="' . $esc_form_key . '" id="' . $esc_form_key . '" class="yoast' . $class . '">';
			  		foreach ( $meta_field_def['options'] as $val => $option ) {
			  			$selected = selected( $meta_value, $val, false );
			  			$content .= '<option ' . $selected . ' value="' . esc_attr( $val ) . '">' . esc_html( $option ) . '</option>';
			  		}
			  		$content .= '</select>';
			  	}
			  	break;
		
			  case 'multiselect':
			  	if ( isset( $meta_field_def['options'] ) && is_array( $meta_field_def['options'] ) && $meta_field_def['options'] !== array() ) {
		
			  		// Set $meta_value as $selectedarr
			  		$selected_arr = $meta_value;
		
			  		// If the multiselect field is 'meta-robots-adv' we should explode on ,
			  		if ( 'meta-robots-adv' === $key ) {
			  			$selected_arr = explode( ',', $meta_value );
			  		}
		
			  		if ( ! is_array( $selected_arr ) ) {
			  			$selected_arr = (array) $selected_arr;
			  		}
		
			  		$options_count = count( $meta_field_def['options'] );
		
			  		// @todo [JRF => whomever] verify height calculation for older WP versions, was 16x, for WP3.8 20x is more appropriate
			  		$content .= '<select multiple="multiple" size="' . esc_attr( $options_count ) . '" style="height: ' . esc_attr( ( $options_count * 20 ) + 4 ) . 'px;" name="' . $esc_form_key . '[]" id="' . $esc_form_key . '" class="yoast' . $class . '">';
			  		foreach ( $meta_field_def['options'] as $val => $option ) {
			  			$selected = '';
			  			if ( in_array( $val, $selected_arr ) ) {
			  				$selected = ' selected="selected"';
			  			}
			  			$content .= '<option ' . $selected . ' value="' . esc_attr( $val ) . '">' . esc_html( $option ) . '</option>';
			  		}
			  		$content .= '</select>';
			  	}
			  	break;
		
			  case 'checkbox':
			  	$checked = checked( $meta_value, 'on', false );
			  	$expl    = ( isset( $meta_field_def['expl'] ) ) ? esc_html( $meta_field_def['expl'] ) : '';
			  	$content .= '<label for="' . $esc_form_key . '"><input type="checkbox" id="' . $esc_form_key . '" name="' . $esc_form_key . '" ' . $checked . ' value="on" class="yoast' . $class . '"/> ' . $expl . '</label><br />';
			  	break;
		
			  case 'radio':
			  	if ( isset( $meta_field_def['options'] ) && is_array( $meta_field_def['options'] ) && $meta_field_def['options'] !== array() ) {
			  		foreach ( $meta_field_def['options'] as $val => $option ) {
			  			$checked = checked( $meta_value, $val, false );
			  			$content .= '<input type="radio" ' . $checked . ' id="' . $esc_form_key . '_' . esc_attr( $val ) . '" name="' . $esc_form_key . '" value="' . esc_attr( $val ) . '"/> <label for="' . $esc_form_key . '_' . esc_attr( $val ) . '">' . esc_html( $option ) . '</label> ';
			  		}
			  	}
			  	break;
		
			  case 'upload':
			  	$content .= '<input id="' . $esc_form_key . '" type="text" size="36" name="' . $esc_form_key . '" value="' . esc_attr( $meta_value ) . '" />';
			  	$content .= '<input id="' . $esc_form_key . '_button" class="wpseo_image_upload_button button" type="button" value="Upload Image" />';
			  	break;
			}
		
		
			$html = '';
			if ( $content === '' ) {
				$content = apply_filters( 'wpseo_do_meta_box_field_' . $key, $content, $meta_value, $esc_form_key, $meta_field_def, $key );
			}
		
			if ( $content !== '' ) {
		
				$label = esc_html( $meta_field_def['title'] );
				if ( in_array( $meta_field_def['type'], array(
						'snippetpreview',
						'radio',
						'checkbox',
				), true ) === false
				) {
					$label = '<label for="' . $esc_form_key . '">' . $label . ':</label>';
				}
		
				$help = '';
				if ( isset( $meta_field_def['help'] ) && $meta_field_def['help'] !== '' ) {
					$help = '<img src="' . plugins_url( 'images/question-mark.png', WPSEO_FILE ) . '" class="alignright yoast_help" id="' . esc_attr( $key . 'help' . $lang_suffix ) . '" alt="' . esc_attr( $meta_field_def['help'] ) . '" />';
				}
		
				$html = '
				<tr>
					<th scope="row">' . $label . $help . '</th>
					<td>';
		
				$html .= $content;
		
				if ( isset( $meta_field_def['description'] ) ) {
					$html .= '<div>' . $meta_field_def['description'] . '</div>';
				}
		
				$html .= '
					</td>
				</tr>';
			}
		
			return $html;
		}
		
		/**
		 * Generate a snippet preview.
		 *
		 * @return string
		 */
		function snippet($lang) {
			if ( isset( $_GET['post'] ) ) {
				$post_id = (int) WPSEO_Option::validate_int( $_GET['post'] );
				$post    = get_post( $post_id );
			} else {
				global $post;
			}
		
			$options = WPSEO_Options::get_all();
		
			$date = '';
			if ( is_object( $post ) && isset( $options[ 'showdate-' . $post->post_type ] ) && $options[ 'showdate-' . $post->post_type ] === true ) {
				$date = $this->get_post_date( $post );
			}
		
			$title = self::get_value( 'title', 0, $lang );
			$desc  = self::get_value( 'metadesc', 0, $lang );
		
			$slug = ( is_object( $post ) && isset( $post->post_name ) ) ? $post->post_name : '';
			if ( $slug !== '' ) {
				$slug = sanitize_title( $title );
			}
		
			if ( is_string( $date ) && $date !== '' ) {
				$datestr = '<span class="date">' . $date . ' - </span>';
			} else {
				$datestr = '';
			}
			$content = '<div class="wpseosnippet" id="wpseosnippet-'. $lang . '">
				<a class="title" id="wpseosnippet_title-' . $lang . '" href="#">' . esc_html( $title ) . '</a>';
		
			$content .= '<span class="url">' . str_replace( 'http://', '', get_bloginfo( 'url' ) ) . '/' . esc_html( $slug ) . '/</span>';
		
			$content .= '<p class="desc">' . $datestr . '<span class="autogen"></span><span class="content">' . esc_html( $desc ) . '</span></p>';
		
			$content .= '</div>';
		
			$content = apply_filters( 'wpseo_snippet', $content, $post, compact( 'title', 'desc', 'date', 'slug' ) );
		
			return $content;
		}
		
		
		/**
		 * Get a custom post meta value
		 * Returns the default value if the meta value has not been set
		 *
		 * @internal Unfortunately there isn't a filter available to hook into before returning the results
		 * for get_post_meta(), get_post_custom() and the likes. That would have been the preferred solution.
		 *
		 * @static
		 *
		 * @param   string  $key		internal key of the value to get (without prefix)
		 * @param   int     $postid		post ID of the post to get the value for
		 * @return  string				All 'normal' values returned from get_post_meta() are strings.
		 *								Objects and arrays are possible, but not used by this plugin
		 *								and therefore discarted (except when the special 'serialized' field def
		 *								value is set to true - only used by add-on plugins for now)
		 *								Will return the default value if no value was found.
		 *								Will return empty string if no default was found (not one of our keys) or
		 *								if the post does not exist
		 */
		public static function get_value( $key, $postid = 0, $lang = '' ) {
			global $post;
			
			if($lang == ''){
			  return parent::get_value($key, $postid);
			}
		
			$postid = absint( $postid );
			if ( $postid === 0 ) {
				if ( ( isset( $post ) && is_object( $post ) ) && ( isset( $post->post_status ) && $post->post_status !== 'auto-draft' ) ){
					$postid = $post->ID;
				}
				else {
					return '';
				}
			}
		
			$custom = get_post_custom( $postid ); // array of strings or empty array
			$key_by_lang = self::$meta_prefix . $key;
			if ( !empty($lang) && $lang != $GLOBALS['q_config']['default_language'] ) {
			  $key_by_lang .= '-' . $lang;
			}
			
		
			if ( isset( $custom[ $key_by_lang ][0] ) ) {
				$unserialized = maybe_unserialize( $custom[ $key_by_lang ][0] );
				if ( $custom[ $key_by_lang ][0] === $unserialized ) {
					return $custom[ $key_by_lang ][0];
				}
				else {
					$field_def = self::$meta_fields[ self::$fields_index[ self::$meta_prefix . $key ]['subset'] ][ self::$fields_index[ self::$meta_prefix . $key ]['key'] ];
					if	( isset( $field_def['serialized'] ) && $field_def['serialized'] === true ) {
						// Ok, serialize value expected/allowed
						return $unserialized;
					}
				}
			}
		
			// Meta was either not found or found, but object/array while not allowed to be
			if ( isset( self::$defaults[ self::$meta_prefix . $key ] ) ) {
				return self::$defaults[ self::$meta_prefix . $key ];
			}
			else {
				/* Shouldn't ever happen, means not one of our keys as there will always be a default available
				 for all our keys */
				return '';
			}
		}
		
		/**
		 * Save the WP SEO metadata for posts.
		 *
		 * @internal $_POST parameters are validated via sanitize_post_meta()
		 *
		 * @param  int $post_id
		 *
		 * @return  bool|void   Boolean false if invalid save post request
		 */
		function save_postdata( $post_id ) {
			if ( $post_id === null ) {
				return false;
			}
		
			if ( wp_is_post_revision( $post_id ) ) {
				$post_id = wp_is_post_revision( $post_id );
			}
		
			clean_post_cache( $post_id );
			$post = get_post( $post_id );
		
			if ( ! is_object( $post ) ) {
				// non-existent post
				return false;
			}
		
			$meta_boxes = apply_filters( 'wpseo_save_metaboxes', array() );
			$meta_boxes = array_merge( $meta_boxes, $this->get_meta_field_defs( 'general', $post->post_type ), $this->get_meta_field_defs( 'advanced' ) );
		
			foreach ( $meta_boxes as $key => $meta_box ) {
              foreach ($this->langs as $l){
				$data = null;
				$key_lang = $key;
				if($this->default_lang != $l){
				  $key_lang .= '-' . $l;
				}
					
				if ( 'checkbox' === $meta_box['type'] ) {
					$data = isset( $_POST[ self::$form_prefix . $key_lang ] ) ? 'on' : 'off';
				} else {
					if ( isset( $_POST[ self::$form_prefix . $key_lang ] ) ) {
						$data = $_POST[ self::$form_prefix . $key_lang ];
					}
				}
				if ( isset( $data ) ) {
					self::set_value( $key_lang, $data, $post_id );
				}
			  }
			}
		
			do_action( 'wpseo_saved_postdata' );
		}
		
		/**
		 * Outputs the page analysis score in the Publish Box.
		 *
		 */
		public function publish_box($lang = '') {
			
			if ( $this->is_metabox_hidden() === true ) {
				return;
			}
			
			global $q_config;
			
			foreach ($this->langs as $l){
				
				$this->current_lang = $l;
				$suffix_lang = $this->get_lang_suffix($l);
				
				echo '<div class="misc-pub-section misc-yoast' . $suffix_lang . '">';
			
				if ( self::get_value( 'meta-robots-noindex' ) === '1' ) {
					$score_label = 'noindex';
					$title       = __( 'Post is set to noindex.', 'wordpress-seo' );
					$score_title = $title;
				} else {
					if ( isset( $_GET['post'] ) ) {
						$post_id = (int) WPSEO_Option::validate_int( $_GET['post'] );
						$post    = get_post( $post_id );
					} else {
						global $post;
					}
			
					$score   = '';
					
					$results = array();
					if( !isset($this->results[$l]) ){
						$this->results[$l] = $this->calculate_results( $post, $l );
					}
					$results = $this->results[$l];
					
					if ( ! is_wp_error( $results ) && isset( $results['total'] ) ) {
						$score = $results['total'];
						unset( $results );
					}
			
					if ( $score === '' ) {
						$score_label = 'na';
						$title       = __( 'No focus keyword set.', 'wordpress-seo' );
					} else {
						$score_label = wpseo_translate_score( $score );
					}
			
					$score_title = wpseo_translate_score( $score, false );
					if ( ! isset( $title ) ) {
						$title = $score_title;
					}
				}
			
				echo '<div class="qtrans_flag qtrans_flag_'. $l .'">';
				echo '<img src="' . trailingslashit(WP_CONTENT_URL) . $q_config['flag_location'] . $q_config['flag'][$l] . '" alt="' . $q_config['language_name'][$l] . '"/>';			
				echo '</div>';
				
				echo '<div title="' . esc_attr( $title ) . '" class="' . esc_attr( 'wpseo-score-icon ' . $score_label ) . '"></div>';
			
				echo __( 'SEO: ', 'wordpress-seo' ) . '<span class="wpseo-score-title">' . $score_title . '</span>';
			
				echo ' <a class="wpseo_tablink scroll" href="#wpseo_linkdex">' . __( 'Check', 'wordpress-seo' ) . '</a>';
			
				echo '</div>';
			
				$this->current_lang = null;
			} // End of the languages loop
		}
		
		/**
		 * Calculate the page analysis results for post.
		 *
		 * @todo [JRF => whomever] check whether the results of this method are always checked with is_wp_error()
		 * @todo [JRF => whomever] check the usage of this method as it's quite intense/heavy, see if it's only
		 * used when really necessary
		 * @todo [JRF => whomever] see if we can get rid of the passing by reference of $results as it makes
		 * the code obfuscated
		 *
		 * @param  object $post Post to calculate the results for.
		 *
		 * @return  array|WP_Error
		 */
		function calculate_results( $post, $lang = '' ) {
			
			if ($lang == ''){
				return parent::calculate_results( $post );
			}
			
			
			$options = WPSEO_Options::get_all();
			$lang_suffix = $this->get_lang_suffix($lang);
		
			if ( ! class_exists( 'DOMDocument' ) ) {
				$result = new WP_Error( 'no-domdocument', sprintf( __( "Your hosting environment does not support PHP's %sDocument Object Model%s.", 'wordpress-seo' ), '<a href="http://php.net/manual/en/book.dom.php">', '</a>' ) . ' ' . __( "To enjoy all the benefits of the page analysis feature, you'll need to (get your host to) install it.", 'wordpress-seo' ) );
		
				return $result;
			}
		
			if ( ! is_array( $post ) && ! is_object( $post ) ) {
				$result = new WP_Error( 'no-post', __( 'No post content to analyse.', 'wordpress-seo' ) );
		
				return $result;
			} elseif ( self::get_value( 'focuskw', $post->ID, $lang ) === '' ) {
				$result = new WP_Error( 'no-focuskw'. lang_suffix, sprintf( __( 'No focus keyword was set for this %s. If you do not set a focus keyword, no score can be calculated.', 'wordpress-seo' ), $post->post_type ) );
		
				self::set_value( 'linkdex' . lang_suffix, 0, $post->ID );
		
				return $result;
			} elseif ( apply_filters( 'wpseo_use_page_analysis', true ) !== true ) {
				$result = new WP_Error( 'page-analysis-disabled', sprintf( __( 'Page Analysis has been disabled.', 'wordpress-seo' ), $post->post_type ) );
		
				return $result;
			}
		
			$results = array();
			$job     = array();
		
			$sampleurl             = $this->get_sample_permalink( $post );
			$job['pageUrl']        = preg_replace( '`%(?:post|page)name%`', $sampleurl[1], $sampleurl[0] );
			$job['pageSlug']       = urldecode( $post->post_name );
			$job['keyword']        = self::get_value( 'focuskw' . $lang_suffix );
			$job['keyword_folded'] = $this->strip_separators_and_fold( $job['keyword'] );
			$job['post_id']        = $post->ID;
			$job['post_type']      = $post->post_type;
		
			$dom                      = new domDocument;
			$dom->strictErrorChecking = false;
			$dom->preserveWhiteSpace  = false;
		
			/**
			 * Filter: 'wpseo_pre_analysis_post_content' - Make the post content filterable before calculating the page analysis
			 *
			 * @api string $post_content The post content
			 *
			 * @param object $post The post
			 */
			
			$post_content = apply_filters( 'wpseo_pre_analysis_post_content_filter_lang', $post->post_content, $lang );
			$post_content = apply_filters( 'wpseo_pre_analysis_post_content', $post->post_content, $post );
			
		
			// Check if the post content is not empty
			if ( ! empty( $post_content ) ) {
				@$dom->loadHTML( $post_content );
			}
		
			unset( $post_content );
		
			$xpath = new DOMXPath( $dom );
		
			// Check if this focus keyword has been used already.
			$this->check_double_focus_keyword( $job, $results );
		
			// Keyword
			$this->score_keyword( $job['keyword'], $results );
		
			// Title
			$title = self::get_value( 'title', $post->ID, $lang );
			$title = apply_filters( 'wpseo_title_filter_lang', $title, $lang);
			
			if ( $title !== '' ) {
				$job['title'] = $title;
			} else {
				if ( isset( $options[ 'title-' . $post->post_type ] ) && $options[ 'title-' . $post->post_type ] !== '' ) {
					$title_template = $options[ 'title-' . $post->post_type ];
				} else {
					$title_template = '%%title%% - %%sitename%%';
				}
				$job['title'] = wpseo_replace_vars( $title_template, $post );
			}
			unset( $title );
			$this->score_title( $job, $results );
		
			// Meta description
			$description = '';
			$desc_meta   = self::get_value( 'metadesc', 0, $lang );
			if ( $desc_meta !== '' ) {
				$description = $desc_meta;
			} elseif ( isset( $options[ 'metadesc-' . $post->post_type ] ) && $options[ 'metadesc-' . $post->post_type ] !== '' ) {
				$description = wpseo_replace_vars( $options[ 'metadesc-' . $post->post_type ], $post );
			}
			unset( $desc_meta );
		
			self::$meta_length = apply_filters( 'wpseo_metadesc_length', self::$meta_length, $post );
		
			$this->score_description( $job, $results, $description, self::$meta_length );
			unset( $description );
		
			// Body
			$body   = $this->get_body( $post );
			$body   = $this->filter_content_by_lang($body, $lang);
			$firstp = $this->get_first_paragraph( $body );
			$this->score_body( $job, $results, $body, $firstp );
			unset( $firstp );
		
			// URL
			$this->score_url( $job, $results );
		
			// Headings
			$headings = $this->get_headings( $body );
			$this->score_headings( $job, $results, $headings );
			unset( $headings );
		
			// Images
			$imgs          = array();
			$imgs['count'] = substr_count( $body, '<img' );
			$imgs          = $this->get_images_alt_text( $post->ID, $body, $imgs );
		
			// Check featured image
			if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail() ) {
				$imgs['count'] += 1;
		
				if ( empty( $imgs['alts'] ) ) {
					$imgs['alts'] = array();
				}
		
				$imgs['alts'][] = $this->strtolower_utf8( get_post_meta( get_post_thumbnail_id( $post->ID ), '_wp_attachment_image_alt', true ) );
			}
		
			$this->score_images_alt_text( $job, $results, $imgs );
			unset( $imgs );
			unset( $body );
		
			// Anchors
			$anchors = $this->get_anchor_texts( $xpath );
			$count   = $this->get_anchor_count( $xpath );
		
			$this->score_anchor_texts( $job, $results, $anchors, $count );
			unset( $anchors, $count, $dom );
		
			$results = apply_filters( 'wpseo_linkdex_results', $results, $job, $post );
		
			$this->aasort( $results, 'val' );
		
			$overall     = 0;
			$overall_max = 0;
		
			foreach ( $results as $result ) {
				$overall += $result['val'];
				$overall_max += 9;
			}
		
			if ( $overall < 1 ) {
				$overall = 1;
			}
			$score = wpseo_calc( wpseo_calc( $overall, '/', $overall_max ), '*', 100, true );
		
			if ( ! is_wp_error( $score ) ) {
				self::set_value( 'linkdex' . $lang_suffix, absint( $score ), $post->ID );
		
				$results['total'] = $score;
			}
		
			return $results;
		}
		
		/**
		 * Output the page analysis results.
		 *
		 * @param object $post Post to output the page analysis results for.
		 *
		 * @return string
		 */
		function linkdex_output( $post , $lang ) {
			
			$this->current_lang = $lang;
			$results = array();
			if ( !isset($this->results[$lang]) ){
				$this->results[$lang] = $this->calculate_results( $post , $lang );
			}
			$results = $this->results[$lang];
			$this->current_lang = null;
			
			
			if ( is_wp_error( $results ) ) {
				$error = $results->get_error_messages();
		
				return '<tr><td><div class="wpseo_msg"><p><strong>' . esc_html( $error[0] ) . '</strong></p></div></td></tr>';
			}
			$output = '';
		
			if ( is_array( $results ) && $results !== array() ) {
		
				$output     = '<table class="wpseoanalysis">';
				$perc_score = absint( $results['total'] );
				unset( $results['total'] ); // unset to prevent echoing it.
		
				foreach ( $results as $result ) {
					if ( is_array( $result ) ) {
						$score = wpseo_translate_score( $result['val'] );
						$output .= '<tr><td class="score"><div class="' . esc_attr( 'wpseo-score-icon ' . $score ) . '"></div></td><td>' . $result['msg'] . '</td></tr>';
					}
				}
				$output .= '</table>';
		
				if ( WP_DEBUG === true || ( defined( 'WPSEO_DEBUG' ) && WPSEO_DEBUG === true ) ) {
					$output .= '<p><small>(' . $perc_score . '%)</small></p>';
				}
			}
		
			$output = '<div class="wpseo_msg"><p>' . __( 'To update this page analysis, save as draft or update and check this tab again', 'wordpress-seo' ) . '.</p></div>' . $output;
		
			unset( $results );
		
			return $output;
		}
		
		/**
		 * Check whether this focus keyword has been used for other posts before.
		 *
		 * @param array $job
		 * @param array $results
		 */
		function check_double_focus_keyword( $job, &$results, $lang = '' ) {

			if($lang == ''){
			  parent::check_double_focus_keyword( $job, $results );
			}

            $lang_suffix = $this->get_lang_suffix($lang);

			$posts = get_posts(
					array(
							'meta_key'    => self::$meta_prefix . 'focuskw' . $lang_suffix,
							'meta_value'  => $job['keyword'],
							'exclude'     => $job['post_id'],
							'fields'      => 'ids',
							'post_type'   => 'any',
							'numberposts' => - 1,
					)
			);
		
			if ( count( $posts ) == 0 ) {
				$this->save_score_result( $results, 9, __( 'You\'ve never used this focus keyword before, very good.', 'wordpress-seo' ), 'keyword_overused' );
			} elseif ( count( $posts ) == 1 ) {
				$this->save_score_result( $results, 6, sprintf( __( 'You\'ve used this focus keyword %1$sonce before%2$s, be sure to make very clear which URL on your site is the most important for this keyword.', 'wordpress-seo' ), '<a href="' . esc_url( add_query_arg( array(
						'post'   => $posts[0],
						'action' => 'edit',
				), admin_url( 'post.php' ) ) ) . '">', '</a>' ), 'keyword_overused' );
			} else {
				$this->save_score_result( $results, 1, sprintf( __( 'You\'ve used this focus keyword %3$s%4$d times before%2$s, it\'s probably a good idea to read %1$sthis post on cornerstone content%2$s and improve your keyword strategy.', 'wordpress-seo' ), '<a href="https://yoast.com/cornerstone-content-rank/">', '</a>', '<a href="' . esc_url( add_query_arg( array( 'seo_kw_filter' => $job['keyword'] ), admin_url( 'edit.php' ) ) ) . '">', count( $posts ) ), 'keyword_overused' );
			}
		}
		
		/**
		 * Display the column content for the given column
		 *
		 * @param string $column_name Column to display the content for.
		 * @param int    $post_id     Post to display the column content for.
		 */
		function column_content( $column_name, $post_id ) {
			if ( $this->is_metabox_hidden() === true ) {
				return;
			}
		
			if ( $column_name === 'wpseo-score' ) {
				foreach($this->langs as $l){
					$score = self::get_value( 'linkdex', $post_id, $l );
					if ( self::get_value( 'meta-robots-noindex', $post_id ) === '1' ) {
						$score_label = 'noindex';
						$title       = __( 'Post is set to noindex.', 'wordpress-seo' );
						self::set_value( 'linkdex', 0, $post_id );
					} elseif ( $score !== '' ) {
						$nr          = wpseo_calc( $score, '/', 10, true );
						$score_label = wpseo_translate_score( $nr );
						$title       = wpseo_translate_score( $nr, false );
						unset( $nr );
					} else {
						$this->calculate_results( get_post( $post_id ), $l );
						$score = self::get_value( 'linkdex', $post_id, $l );
						if ( $score === '' ) {
							$score_label = 'na';
							$title       = __( 'Focus keyword not set.', 'wordpress-seo' );
						} else {
							$score_label = wpseo_translate_score( $score );
							$title       = wpseo_translate_score( $score, false );
						}
					}
			
					echo '<div>' . $l . '</div><div title="' . esc_attr( $title ) . '" class="wpseo-score-icon ' . esc_attr( $score_label ) . '"></div>';
				}
			}
			if ( $column_name === 'wpseo-title' ) {
				$to_print = '';
			
				foreach($this->langs as $l){
					$lang_suffix = $this->get_lang_suffix($l);
					$to_print .= esc_html( $GLOBALS['q_config']['language_name'][$l] . ' => ' . self::get_value( 'title', $post_id, $l ) . ' ' );
					$to_print .= '<br>';
				}
				
				// $to_print .= apply_filters( 'wpseo_title', $to_print);
				echo $to_print;
			}
			if ( $column_name === 'wpseo-metadesc' ) {
				$to_print = '';

				foreach($this->langs as $l){
					$lang_suffix = $this->get_lang_suffix($l);
					$to_print .=  esc_html(  $GLOBALS['q_config']['language_name'][$l] . ' => ' . self::get_value( 'metadesc', $post_id, $l ) . ' ' );
					$to_print .= '<br>';
				}

				// $to_print .= apply_filters( 'wpseo_metadesc', $to_print);
				echo $to_print;
			}
			if ( $column_name === 'wpseo-focuskw' ) {
				$to_print = '';
				
				foreach($this->langs as $l){
					$lang_suffix = $this->get_lang_suffix($l);
					$to_print .=  esc_html( $GLOBALS['q_config']['language_name'][$l] . ' => ' . self::get_value( 'focuskw' . $lang_suffix, $post_id ) . ' ' );
					$to_print .= '<br>';
				}
				
				echo  $to_print ;
			}
		}
		
		
		
		/**
		 * 
		 * @param String $lang : Language code like en, es, it
		 *        These codes are defined in (m)qtranslated module
		 *        
		 * @return String $suffix_lang: 
		 *         If default language the suffix is empty
		 *         if it's another language different from default language, it will
		 *         return '-' + 'lang-code'- For example, '-es', '-en', '-it'
		 *         
		 * 
		 */
		public function get_lang_suffix($lang){
		  	$suffix_lang = "";
				if($this->default_lang != $lang){
				$suffix_lang .= "-" . $lang;
			}
			return $suffix_lang;
		}
		
		/**
		 *
		 *  Filter by language the content when the hook  "wpseo_pre_analysis_post_content" is called
		 *
		 *  @param String $content : Content stored in Database.
		 *         String $lang : Language code like en, es, it
		 *
		 *  @return $filtered_content : filter by language
		 *
		 */
		public function filter_content_prior_to_analisis($content, $post){
		  
			if( !isset($this->current_lang )){
				return $content;
			}
				
			return $this->filter_content_by_lang($content, $this->current_lang);	
		}
		
		
		/**
		 * 
		 *  Filter by language the content that it is stored in Database
		 * 
		 *  @param String $content : Content stored in Database.
		 *         String $lang : Language code like en, es, it
		 *         
		 *  @return $filtered_content : filter by language
		 * 
		 */
		public function filter_content_by_lang($content, $lang = ''){
			return qtrans_use($lang, $content);
		}


	} /* End of class */

} /* End of class-exists wrapper */
