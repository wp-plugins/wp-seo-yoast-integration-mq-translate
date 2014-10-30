<?php
/**
 * @package Frontend
 *
 * Main frontend code.
 */

if ( ! defined( 'WPSEO_VERSION' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

if ( ! class_exists( 'WPSEO_Frontend_mqtranslate_intgr' ) && class_exists( 'WPSEO_Frontend' ) ) {
	/**
	 * Main frontend class for WordPress SEO, responsible for the SEO output as well as removing
	 * default WordPress output.
	 *
	 * @package WPSEO_Frontend
	 */
	class WPSEO_Frontend_mqtranslate_intgr extends WPSEO_Frontend{

		/**
		 * @var boolean Boolean indicating wether output buffering has been started
		 */
		private $ob_started = false;
		private $current_lang;

		/**
		 * Class constructor
		 *
		 * Adds and removes a lot of filters.
		 */
		function __construct() {

			$this->options = WPSEO_Options::get_all();
			$this->current_lang = $GLOBALS['q_config']['language'];

			remove_action( 'wpseo_head', array( $GLOBALS['old_wpseo_front'] , 'metadesc' , 10) );
			remove_action( 'wp_title', array( $GLOBALS['old_wpseo_front'] , 'title' , 15, 3) );
			
			add_action( 'wpseo_head', array( $this, 'metadesc' ), 11 );
			add_filter( 'wp_title', array( $this, 'title' ), 16, 3 );
			
			// opengraph hooks
			add_filter( 'wpseo_opengraph_desc', array($this, 'get_metadesc'));
			add_filter( 'wpseo_opengraph_title', array($this, 'get_content_title'));
		}
		
		
		function get_content_title( $object = null ) {
			if ( is_null( $object ) ) {
				global $wp_query;
				$object = $wp_query->get_queried_object();
			}
		
			$title = WPSEO_Metabox_wpSEOyoast_integr::get_value( 'title', $object->ID, $this->current_lang );
		
			if ( $title !== '' ) {
				return wpseo_replace_vars( $title, $object );
			}
		
			$post_type = ( isset( $object->post_type ) ? $object->post_type : $object->query_var );
		
			return $this->get_title_from_options( 'title-' . $post_type, $object );
		}
		
		
		public function metadesc( $echo = true ) {
			global $post, $wp_query;
		
			$metadesc  = '';
			$post_type = '';
			$template  = '';
		
			if ( is_object( $post ) && ( isset( $post->post_type ) && $post->post_type !== '' ) ) {
				$post_type = $post->post_type;
			}
		
			if ( is_singular() ) {
				$metadesc = WPSEO_Metabox_wpSEOyoast_integr::get_value( 'metadesc' , 0, $this->current_lang );
				if ( ( $metadesc === '' && $post_type !== '' ) && isset( $this->options[ 'metadesc-' . $post_type ] ) ) {
					$template = $this->options[ 'metadesc-' . $post_type ];
					$term     = $post;
				}
			} else {
				if ( is_search() ) {
					return '';
				} elseif ( $this->is_home_posts_page() ) {
					$template = $this->options['metadesc-home-wpseo'];
					$term     = array();
				} elseif ( $this->is_posts_page() ) {
					$metadesc = WPSEO_Metabox_wpSEOyoast_integr::get_value( 'metadesc', get_option( 'page_for_posts' ), $this->current_lang  );
					if ( ( $metadesc === '' && $post_type !== '' ) && isset( $this->options[ 'metadesc-' . $post_type ] ) ) {
						$page     = get_post( get_option( 'page_for_posts' ) );
						$template = $this->options[ 'metadesc-' . $post_type ];
						$term     = $page;
					}
				} elseif ( $this->is_home_static_page() ) {
					$metadesc = WPSEO_Metabox_wpSEOyoast_integr::get_value( 'metadesc' , 0, $this->current_lang );
					if ( ( $metadesc === '' && $post_type !== '' ) && isset( $this->options[ 'metadesc-' . $post_type ] ) ) {
						$template = $this->options[ 'metadesc-' . $post_type ];
					}
				} elseif ( is_category() || is_tag() || is_tax() ) {
					$term     = $wp_query->get_queried_object();
					$metadesc = WPSEO_Taxonomy_Meta::get_term_meta( $term, $term->taxonomy, 'desc' );
					if ( ( ! is_string( $metadesc ) || $metadesc === '' ) && ( ( is_object( $term ) && isset( $term->taxonomy ) ) && isset( $this->options[ 'metadesc-tax-' . $term->taxonomy ] ) ) ) {
						$template = $this->options[ 'metadesc-tax-' . $term->taxonomy ];
					}
				} elseif ( is_author() ) {
					$author_id = get_query_var( 'author' );
					$metadesc  = get_the_author_meta( 'wpseo_metadesc', $author_id );
					if ( ( ! is_string( $metadesc ) || $metadesc === '' ) && '' !== $this->options[ 'metadesc-author-wpseo' ] ) {
						$template = $this->options[ 'metadesc-author-wpseo' ];
					}
				} elseif ( is_post_type_archive() ) {
					$post_type = get_query_var( 'post_type' );
					if ( is_array( $post_type ) ) {
						$post_type = reset( $post_type );
					}
					if ( isset( $this->options[ 'metadesc-ptarchive-' . $post_type ] ) ) {
						$template = $this->options[ 'metadesc-ptarchive-' . $post_type ];
					}
				} elseif ( is_archive() ) {
					$template = $this->options['metadesc-archive-wpseo'];
				}
		
				// If we're on a paginated page, and the template doesn't change for paginated pages, bail.
				if ( ( ! is_string( $metadesc ) || $metadesc === '' ) && get_query_var( 'paged' ) && get_query_var( 'paged' ) > 1 && $template !== '' ) {
					if ( strpos( $template, '%%page' ) === false ) {
						return '';
					}
				}
			}
		
			if ( ( ! is_string( $metadesc ) || '' === $metadesc ) && '' !== $template ) {
				if ( ! isset( $term ) ) {
					$term = $wp_query->get_queried_object();
				}
				$metadesc = wpseo_replace_vars( $template, $term );
			}
		}
		
		public function get_metadesc($metadesc){
			if ( is_singular() ) {
				$metadesc = WPSEO_Metabox_wpSEOyoast_integr::get_value( 'metadesc' , 0, $this->current_lang );
			}
			return $metadesc;
		} 
		
		

	} /* End of class */

} /* End of class-exists wrapper */
