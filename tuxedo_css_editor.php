<?php
/**
 * Plugin Name: Tuxedo CSS Editor
 * Plugin URI:  https://github.com/andtrev/Tuxedo-CSS-Editor
 * Description: Edit Sass and Less CSS live in the customizer.
 * Version:     1.0.1
 * Author:      Trevor Anderson
 * Author URI:  https://github.com/andtrev
 * License:     GPLv2 or later
 * Domain Path: /languages
 * Text Domain: tuxedo-css-editor
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @package TuxedoCSSEditor
 * @version 1.0.1
 */

/**
 * Tuxedo CSS Editor manager class.
 *
 * Bootstraps the plugin.
 *
 * @since 1.0.0
 */
class TuxedoCSSEditor {

	/**
	 * TuxedoCSSEditor instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 * @var TuxedoCSSEditor
	 */
	private static $instance = false;

	/**
	 * Get the instance.
	 * 
	 * Returns the current instance, creates one if it
	 * doesn't exist. Ensures only one instance of
	 * TuxedoCSSEditor is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 *
	 * @return TuxedoCSSEditor
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;

	}

	/**
	 * Plugin version.
	 * 
	 * @since 1.0.0
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Constructor.
	 * 
	 * Initializes and adds functions to filter and action hooks.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'customize_preview_init', array( $this, 'enqueue_customizer_preview_scripts' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_customizer_control_scripts' ) );
		add_action( 'customize_register', array( $this, 'register_customizer_settings_controls' ) );
		add_action( 'wp_head', array( $this, 'output_compiled_css' ), PHP_INT_MAX );

	}

	/**
	 * Enqueue scripts for customizer preview side.
	 * 
	 * @since 1.0.0
	 */
	public function enqueue_customizer_preview_scripts() {

		wp_enqueue_script( 'tux-customizer-live', plugin_dir_url( __FILE__ ) . 'js/tuxedo_customizer_live.js', array( 'jquery', 'customize-preview' ), $this->version, true );

	}

	/**
	 * Enqueue scripts for customizer control side.
	 * 
	 * @since 1.0.0
	 */
	public function enqueue_customizer_control_scripts() {

		wp_register_script( 'ace', plugin_dir_url( __FILE__ ) . 'js/ace/src-min-noconflict/ace.js', array( 'jquery' ), $this->version, true );
		wp_register_script( 'less', plugin_dir_url( __FILE__ ) . 'js/less/less.min.js', array( 'jquery' ), $this->version, true );
		wp_register_script( 'sass', plugin_dir_url( __FILE__ ) . 'js/sass/sass.sync.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'tux-ace-editor', plugin_dir_url( __FILE__ ) . 'js/tuxedo_ace_editor.js', array( 'ace', 'less', 'sass' ), $this->version, true );

	}

	/**
	 * Register customizer settings and controls.
	 * 
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
	 * @since 1.0.0
	 */
	public function register_customizer_settings_controls( $wp_customize ) {

		tuxedo_css_editor_custom_controls();

		$wp_customize->add_panel( 'tuxedo_css_editor_panel', array(
			'title' => __( 'Tuxedo CSS Editor', 'tuxedo-css-editor' ),
		) );

		$wp_customize->add_section( 'tux_css_editor_section', array(
			'title' => __( 'CSS Editor', 'tuxedo-css-editor' ),
			'panel' => 'tuxedo_css_editor_panel',
		) );

		$wp_customize->add_section( 'tux_css_editor_options_section', array(
			'title' => __( 'CSS Editor Options', 'tuxedo-css-editor' ),
			'panel' => 'tuxedo_css_editor_panel',
		) );

		$wp_customize->add_setting( 'tux_css_editor_code', array(
			'default'           => '',
			'sanitize_callback' => '',
			'type'              => 'theme_mod',
			'transport'         => 'postMessage',
			'capability'        => 'edit_theme_options',
		) );

		$wp_customize->add_setting( 'tux_css_editor_compiled', array(
			'default'           => '',
			'sanitize_callback' => '',
			'type'              => 'theme_mod',
			'transport'         => 'postMessage',
			'capability'        => 'edit_theme_options',
		) );

		$wp_customize->add_setting( 'tux_css_editor[theme]', array(
			'default'           => 'github',
			'sanitize_callback' => array( $this, 'sanitize' ),
			'type'              => 'option',
			'transport'         => 'postMessage',
			'capability'        => 'edit_theme_options',
		) );

		$wp_customize->add_setting( 'tux_css_editor[font_size]', array(
			'default'           => '12px',
			'sanitize_callback' => array( $this, 'sanitize' ),
			'type'              => 'option',
			'transport'         => 'postMessage',
			'capability'        => 'edit_theme_options',
		) );

		$wp_customize->add_setting( 'tux_css_editor_compiler', array(
			'default'           => 'sass',
			'sanitize_callback' => array( $this, 'sanitize' ),
			'type'              => 'theme_mod',
			'transport'         => 'postMessage',
			'capability'        => 'edit_theme_options',
		) );

		$wp_customize->add_setting( 'tux_css_editor[compress]', array(
			'default'           => '1',
			'sanitize_callback' => array( $this, 'sanitize' ),
			'type'              => 'option',
			'transport'         => 'postMessage',
			'capability'        => 'edit_theme_options',
		) );

		$wp_customize->add_setting( 'tux_css_editor_output', array(
			'default'           => '1',
			'sanitize_callback' => array( $this, 'sanitize' ),
			'type'              => 'theme_mod',
			'transport'         => 'postMessage',
			'capability'        => 'edit_theme_options',
		) );

		$wp_customize->add_control( new Tuxedo_Customize_Ace_Editor_Control( $wp_customize, 'tux_ace_editor_control', array(
			'label'    => '',
			'settings' => 'tux_css_editor_code',
			'section'  => 'tux_css_editor_section',
		) ) );

		$wp_customize->add_control( 'tux_css_editor_theme_control', array(
			'label'    => __( 'Editor Theme', 'tuxedo-css-editor' ),
			'settings' => 'tux_css_editor[theme]',
			'section'  => 'tux_css_editor_options_section',
			'type'     => 'select',
			'choices'  => array( 'ambiance' => 'ambiance', 'chaos' => 'chaos', 'chrome' => 'chrome', 'clouds' => 'clouds', 'clouds_midnight' => 'clouds midnight', 'cobal' => 'cobalt', 'crimson_editor' => 'crimson editor', 'dawn' => 'dawn', 'dreamweaver' => 'dreamweaver', 'eclipse' => 'eclipse', 'github' => 'github', 'idle_fingers' => 'idle fingers', 'iplastic' => 'iplastic', 'katzenmilch' => 'katzenmilch', 'kr_theme' => 'kr theme', 'kuroir' => 'kurior', 'merbivore' => 'merbivore', 'merbivore_soft' => 'merbivore soft', 'mono_industrial' => 'mono industrial', 'monokai' => 'monokai', 'pastel_on_dark' => 'pastel on dark', 'solarized_dark' => 'solarized dark', 'solarized_light' => 'solarized light', 'sqlserver' => 'sqlserver', 'terminal' => 'terminal', 'textmate' => 'textmate', 'tomorrow' => 'tomorrow', 'tomorrow_night' => 'tomorrow night', 'tomorrow_night_blue' => 'tomorrow night blue', 'tomorrow_night_bright' => 'tomorrow night bright', 'tomorrow_night_eighties' => 'tomorrow night eighties', 'twilight' => 'twilight', 'vibrant_ink' => 'vibrant ink', 'xcode' => 'xcode' ),
		) );

		$font_sizes = array();
		for ( $i = 8; $i < 25; $i++ ) {
			$font_sizes += array( $i . 'px' => $i . 'px' );
		}

		$wp_customize->add_control( 'tux_css_editor_font_size_control', array(
			'label'    => __( 'Editor Font Size', 'tuxedo-css-editor' ),
			'settings' => 'tux_css_editor[font_size]',
			'section'  => 'tux_css_editor_options_section',
			'type'     => 'select',
			'choices'  => $font_sizes,
		) );

		$wp_customize->add_control( 'tux_css_editor_compiler_control', array(
			'label'    => __( 'Preprocessor', 'tuxedo-css-editor' ),
			'settings' => 'tux_css_editor_compiler',
			'section'  => 'tux_css_editor_options_section',
			'type'     => 'select',
			'choices'  => array( 'scss' => 'sass', 'less' => 'less' ),
		) );

		$wp_customize->add_control( 'tux_css_editor_compress_control', array(
			'label'    => __( 'Compress Output', 'tuxedo-css-editor' ),
			'settings' => 'tux_css_editor[compress]',
			'section'  => 'tux_css_editor_options_section',
			'type'     => 'checkbox',
		) );

		$wp_customize->add_control( 'tux_css_editor_output_control', array(
			'label'    => __( 'Output on Front End', 'tuxedo-css-editor' ),
			'settings' => 'tux_css_editor_output',
			'section'  => 'tux_css_editor_options_section',
			'type'     => 'checkbox',
		) );

		$wp_customize->add_control( 'tux_css_editor_compiled_control', array(
			'label'    => __( 'Compiled Output', 'tuxedo-css-editor' ),
			'settings' => 'tux_css_editor_compiled',
			'section'  => 'tux_css_editor_options_section',
			'type'     => 'textarea',
			''         => '',
		) );

	}

	/**
	 * Output compiled CSS inline to head.
	 * 
	 * @since 1.0.0
	 */
	 public function output_compiled_css() {

		global $wp_customize;
		if ( get_theme_mod( 'tux_css_editor_output', '1' ) !== '1' && ! ( is_a( $wp_customize, 'WP_Customize_Manager' ) && $wp_customize->is_preview() ) ) {
			return;
		}

		?><style type="text/css" id="tuxedo-css"><?php echo get_theme_mod( 'tux_css_editor_compiled', '' ); ?></style><?php

	 }

	/**
	 * Sanitize.
	 * 
	 * @since 1.0.0
	 */
	public function sanitize( $value, $setting = null ) {

		return sanitize_text_field( $value );

	}

}

/** Instantiate the plugin class. */
$tux_css_editor = TuxedoCSSEditor::get_instance();

function tuxedo_css_editor_custom_controls() {

	if ( class_exists( 'WP_Customize_Control' ) ) {

		/**
		 * Ace editor customizer control class.
		 * 
		 * @since 1.0.0
		 */
		class Tuxedo_Customize_Ace_Editor_Control extends WP_Customize_Control {

			public $type = 'ace_editor';

			protected function render() {
				$group = '';
				$id    = 'customize-control-' . str_replace( '[', '-', str_replace( ']', '', $this->id ) );
				$class = 'tux_customizer_setting customize-control customize-control-' . $this->type;
				?><li id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>">
				<?php $this->render_content(); ?>
				</li><?php
			}

			public function render_content() { ?>
				<style type="text/css" media="screen">
					#tux_ace_editor_control {
						display: inline-block;
						height: auto;
						position: absolute;
						top: 130px;
						right: 0;
						bottom: 0;
						left: 0;
						z-index: 99999999;
					}
				</style>
				<label>
					<span class="customize-control-title"><?php if ( ! empty( $this->label ) ) { echo esc_html( $this->label ); } ?></span>
					<?php if ( ! empty( $this->description ) ) : ?>
						<span class="description customize-control-description"><?php echo $this->description; ?></span>
					<?php endif; ?>
					<textarea <?php $this->link(); ?> id="<?php echo sanitize_html_class( $this->id ) ?>_textarea" class="tux_ace_editor_text"><?php echo esc_textarea( $this->value() ); ?></textarea>
					<div id="<?php echo sanitize_html_class( $this->id ); ?>"></div>
				</label>
			<?php
			}

		}

	}

}