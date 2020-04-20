<?php

/*
Class Name: VI_WNOTIFICATION_F_Admin_Admin
Author: Andy Ha (support@villatheme.com)
Author URI: http://villatheme.com
Copyright 2015-2018 villatheme.com. All rights reserved.
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_WNOTIFICATION_F_Admin_Admin {
	protected $settings;

	function __construct() {
		$this->settings = new VI_WNOTIFICATION_F_Data();
		add_filter( 'plugin_action_links_woo-notification/woo-notification.php', array(
			$this,
			'settings_link'
		) );
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 99999 );
	}

	public function admin_print_styles() {
		$background_image = $this->settings->get_background_image();
		$custom_css       = '';
		if ( $background_image ) {
			$background_image_url = woocommerce_notification_background_images( $background_image );

			$custom_css .= "#message-purchased .message-purchase-main::before{
				background-image: url('{$background_image_url}');  
				 border-radius:0;
			}";
		}
		?>
		<style id="woo-notification-close-icon-color"></style>
		<style id="woo-notification-background-image"><?php echo $custom_css ?></style>
		<?php
	}

	/**
	 * Init Script in Admin
	 */
	public function admin_enqueue_scripts() {
		$this->settings = new VI_WNOTIFICATION_F_Data();
		$page           = isset( $_REQUEST['page'] ) ? wp_unslash( sanitize_text_field( $_REQUEST['page'] ) ) : '';
		if ( $page == 'woo-notification' ) {
			add_action( 'admin_print_styles', array( $this, 'admin_print_styles' ) );
			global $wp_scripts;
			$scripts = $wp_scripts->registered;
			//			print_r($scripts);
			foreach ( $scripts as $k => $script ) {
				preg_match( '/^\/wp-/i', $script->src, $result );
				if ( count( array_filter( $result ) ) < 1 ) {
					wp_dequeue_script( $script->handle );
				}
			}

			/*Stylesheet*/
			wp_enqueue_style( 'woo-notification-close-icon', VI_WNOTIFICATION_F_CSS . 'icons-close.css' );
			wp_enqueue_style( 'woo-notification-image', VI_WNOTIFICATION_F_CSS . 'image.min.css' );
			wp_enqueue_style( 'woo-notification-transition', VI_WNOTIFICATION_F_CSS . 'transition.min.css' );
			wp_enqueue_style( 'woo-notification-form', VI_WNOTIFICATION_F_CSS . 'form.min.css' );
			wp_enqueue_style( 'woo-notification-icon', VI_WNOTIFICATION_F_CSS . 'icon.min.css' );
			wp_enqueue_style( 'woo-notification-dropdown', VI_WNOTIFICATION_F_CSS . 'dropdown.min.css' );
			wp_enqueue_style( 'woo-notification-checkbox', VI_WNOTIFICATION_F_CSS . 'checkbox.min.css' );
			wp_enqueue_style( 'woo-notification-segment', VI_WNOTIFICATION_F_CSS . 'segment.min.css' );
			wp_enqueue_style( 'woo-notification-menu', VI_WNOTIFICATION_F_CSS . 'menu.min.css' );
			wp_enqueue_style( 'woo-notification-tab', VI_WNOTIFICATION_F_CSS . 'tab.css' );
			wp_enqueue_style( 'woo-notification-button', VI_WNOTIFICATION_F_CSS . 'button.min.css' );
			wp_enqueue_style( 'woo-notification-grid', VI_WNOTIFICATION_F_CSS . 'grid.min.css' );
			wp_enqueue_style( 'woo-notification-front', VI_WNOTIFICATION_F_CSS . 'woo-notification.css' );
			wp_enqueue_style( 'woo-notification-admin', VI_WNOTIFICATION_F_CSS . 'woo-notification-admin.css' );
			wp_enqueue_style( 'woo-notification-admin-templates', VI_WNOTIFICATION_F_CSS . 'woo-notification-templates.css' );
			wp_enqueue_style( 'select2', VI_WNOTIFICATION_F_CSS . 'select2.min.css' );
			if ( woocommerce_version_check( '3.0.0' ) ) {
				wp_enqueue_script( 'select2' );
			} else {
				wp_enqueue_script( 'select2-v4', VI_WNOTIFICATION_F_JS . 'select2.js', array( 'jquery' ), '4.0.3' );
			}
			/*Script*/
			wp_enqueue_script( 'woo-notification-dependsOn', VI_WNOTIFICATION_F_JS . 'dependsOn-1.0.2.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'woo-notification-transition', VI_WNOTIFICATION_F_JS . 'transition.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'woo-notification-dropdown', VI_WNOTIFICATION_F_JS . 'dropdown.js', array( 'jquery' ) );
			wp_enqueue_script( 'woo-notification-checkbox', VI_WNOTIFICATION_F_JS . 'checkbox.js', array( 'jquery' ) );
			wp_enqueue_script( 'woo-notification-tab', VI_WNOTIFICATION_F_JS . 'tab.js', array( 'jquery' ) );
			wp_enqueue_script( 'woo-notification-address', VI_WNOTIFICATION_F_JS . 'jquery.address-1.6.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'woo-notification-admin', VI_WNOTIFICATION_F_JS . 'woo-notification-admin.js', array( 'jquery' ) );

			/*Color picker*/
			wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array(
				'jquery-ui-draggable',
				'jquery-ui-slider',
				'jquery-touch-punch'
			), false, 1 );

			/*Custom*/
			$highlight_color  = $this->settings->get_highlight_color();
			$text_color       = $this->settings->get_text_color();
			$background_color = $this->settings->get_background_color();
			$border_radius    = $this->settings->get_border_radius();
			$image_padding    = $this->settings->image_padding();
			$close_icon_color = $this->settings->close_icon_color();
			$custom_css       = '#notify-close:before{color:' . $close_icon_color . ';}';
			$custom_css       .= "#message-purchased .message-purchase-main{
                background-color: {$background_color};                       
                color:{$text_color};
                border-radius:{$border_radius}px;
                overflow:hidden;}
                .tab.segment #message-purchased img{border-radius:{$border_radius} 0 0 {$border_radius};}
                .tab.segment #message-purchased a, #message-purchased p span{color:{$highlight_color};}";

			$is_rtl = is_rtl();
			if ( $image_padding ) {
				$padding_right = 20 - $image_padding;
				$custom_css    .= "#message-purchased .wn-notification-image-wrapper{padding:{$image_padding}px;}";
				if ( $is_rtl ) {
					$custom_css .= "#message-purchased .wn-notification-message-container{padding-right:{$padding_right}px;}";
				} else {
					$custom_css .= "#message-purchased .wn-notification-message-container{padding-left:{$padding_right}px;}";
				}
				$custom_css .= "#message-purchased .wn-notification-image{border-radius:{$border_radius}px;}";
			} else {
				$custom_css .= "#message-purchased .wn-notification-image-wrapper{padding:0;}";
				if ( $is_rtl ) {
					$custom_css .= "#message-purchased .wn-notification-message-container{padding-right:20px;}";
				} else {
					$custom_css .= "#message-purchased .wn-notification-message-container{padding-left:20px;}";
				}
			}

			wp_add_inline_style( 'woo-notification-admin', $custom_css );

		}
	}

	/**
	 * Link to Settings
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	public function settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=woo-notification" title="' . __( 'Settings', 'woo-notification' ) . '">' . __( 'Settings', 'woo-notification' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}


	/**
	 * Function init when run plugin+
	 */
	function init() {
		/*Register post type*/

		load_plugin_textdomain( 'woo-notification' );
		$this->load_plugin_textdomain();
		if ( class_exists( 'VillaTheme_Support' ) ) {
			new VillaTheme_Support(
				array(
					'support'   => 'https://wordpress.org/support/plugin/woo-notification',
					'docs'      => 'http://docs.villatheme.com/?item=woocommerce-notification',
					'review'    => 'https://wordpress.org/support/plugin/woo-notification/reviews/?rate=5#rate-response',
					'pro_url'   => 'https://1.envato.market/djEZj',
					'css'       => VI_WNOTIFICATION_F_CSS,
					'image'     => VI_WNOTIFICATION_F_IMAGES,
					'slug'      => 'woo-notification',
					'menu_slug' => 'woo-notification',
					'version'   => VI_WNOTIFICATION_F_VERSION
				)
			);
		}
	}


	/**
	 * load Language translate
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woo-notification' );
		// Admin Locale
		if ( is_admin() ) {
			load_textdomain( 'woo-notification', VI_WNOTIFICATION_F_LANGUAGES . "woo-notification-$locale.mo" );
		}

		// Global + Frontend Locale
		load_textdomain( 'woo-notification', VI_WNOTIFICATION_F_LANGUAGES . "woo-notification-$locale.mo" );
		load_plugin_textdomain( 'woo-notification', false, VI_WNOTIFICATION_F_LANGUAGES );
	}

	/**
	 * Register a custom menu page.
	 */
	public function menu_page() {
		add_menu_page( esc_html__( 'Notification for WooCommerce', 'woo-notification' ), esc_html__( 'Woo Notification', 'woo-notification' ), 'manage_options', 'woo-notification', array(
			'VI_WNOTIFICATION_F_Admin_Settings',
			'page_callback'
		), 'dashicons-megaphone', 2 );

	}

}
