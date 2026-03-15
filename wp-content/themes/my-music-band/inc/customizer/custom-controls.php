<?php
/**
 * Custom Controls
 *
 * @package My Music Band
 */

/**
 * Add Custom Controls
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 */
function my_music_band_custom_controls( $wp_customize ) {
	// Custom control for Important Links.
	class My_Music_Band_Important_Links_Control extends WP_Customize_Control {
		public $type = 'important-links';

		public function render_content() {
			// Add Theme instruction, Support Forum, Changelog, Donate link, Review, Facebook, Twitter, Google+, Pinterest links.
			$important_links = array(
				'theme_instructions' => array(
					'link'  => esc_url( 'https://catchthemes.com/theme-instructions/my-music-band/' ),
					'text'  => esc_html__( 'Theme Instructions', 'my-music-band' ),
					),
				'support' => array(
					'link'  => esc_url( 'https://catchthemes.com/support/' ),
					'text'  => esc_html__( 'Support', 'my-music-band' ),
					),
				'changelog' => array(
					'link'  => esc_url( 'https://catchthemes.com/changelogs/my-music-band-theme/' ),
					'text'  => esc_html__( 'Changelog', 'my-music-band' ),
					),
				'facebook' => array(
					'link'  => esc_url( 'https://www.facebook.com/catchthemes/' ),
					'text'  => esc_html__( 'Facebook', 'my-music-band' ),
					),
				'twitter' => array(
					'link'  => esc_url( 'https://twitter.com/catchthemes/' ),
					'text'  => esc_html__( 'Twitter', 'my-music-band' ),
					),
				'gplus' => array(
					'link'  => esc_url( 'https://plus.google.com/+Catchthemes/' ),
					'text'  => esc_html__( 'Google+', 'my-music-band' ),
					),
				'pinterest' => array(
					'link'  => esc_url( 'http://www.pinterest.com/catchthemes/' ),
					'text'  => esc_html__( 'Pinterest', 'my-music-band' ),
					),
			);

			foreach ( $important_links as $important_link ) {
				echo '<p><a target="_blank" href="' . $important_link['link'] . '" >' . $important_link['text'] . ' </a></p>';
			}
		}
	}

	// Custom control for dropdown category multiple select.
	class My_Music_Band_Multi_Categories_Control extends WP_Customize_Control {
		public $type = 'dropdown-categories';

		public function render_content() {
			$dropdown = wp_dropdown_categories(
				array(
					'name'             => $this->id,
					'echo'             => 0,
					'hide_empty'       => false,
					'show_option_none' => false,
					'hide_if_empty'    => false,
					'show_option_all'  => esc_html__( 'All Categories', 'my-music-band' ),
				)
			);

			$dropdown = str_replace( '<select', '<select multiple = "multiple" style = "height:150px;" ' . $this->get_link(), $dropdown );

			printf(
				'<label class="customize-control-select"><span class="customize-control-title">%s</span> %s</label>',
				$this->label,
				$dropdown
			);

			echo '<p class="description">' . esc_html__( 'Hold down the Ctrl (windows) / Command (Mac) button to select multiple options.', 'my-music-band' ) . '</p>';
		}
	}

	// Custom control for any note, use label as output description.
	class My_Music_Band_Note_Control extends WP_Customize_Control {
		public $type = 'description';

		public function render_content() {
			echo '<h2 class="description">' . $this->label . '</h2>';
		}
	}

	/**
	 * Customize Custom Background Control class.
	 *
	 * @since 1.0.0
	 *
	 * @see WP_Customize_Upload_Control
	 */
	class My_Music_Band_Background_Control extends WP_Customize_Upload_Control {

		/**
		 * The type of customize control being rendered.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    string
		 */
		public $type = 'background-image';

		/**
		 * Mime type for upload control.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    string
		 */
		public $mime_type = 'image';

		/**
		 * Labels for upload control buttons.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    array
		 */
		public $button_labels = array();

		/**
		 * Field labels
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    array
		 */
		public $field_labels = array();

		/**
		 * Background choices for the select fields.
		 *
		 * @since  1.0.0
		 * @access public
		 * @var    array
		 */
		public $background_choices = array();

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @uses WP_Customize_Upload_Control::__construct()
		 *
		 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
		 * @param string               $id      Control ID.
		 * @param array                $args    Optional. Arguments to override class property defaults.
		 */
		public function __construct( $manager, $id, $args = array() ) {

			// Calls the parent __construct
			parent::__construct( $manager, $id, $args );

			// Set button labels for image uploader
			$button_labels = $this->get_button_labels();
			$this->button_labels = apply_filters( 'customizer_background_button_labels', $button_labels, $id );

			// Set field labels
			$field_labels = $this->get_field_labels();
			$this->field_labels = apply_filters( 'customizer_background_field_labels', $field_labels, $id );

			// Set background choices
			$background_choices = $this->get_background_choices();
			$this->background_choices = apply_filters( 'customizer_background_choices', $background_choices, $id );

		}

		/**
		 * Add custom parameters to pass to the JS via JSON.
		 *
		 * @since  1.0.0
		 * @access public
		 * @return void
		 */
		public function to_json() {

			parent::to_json();

			$background_choices = $this->background_choices;
			$field_labels = $this->field_labels;

			// Loop through each of the settings and set up the data for it.
			foreach ( $this->settings as $setting_key => $setting_id ) {

				$this->json[ $setting_key ] = array(
					'link'  => $this->get_link( $setting_key ),
					'value' => $this->value( $setting_key ),
					'label' => isset( $field_labels[ $setting_key ] ) ? $field_labels[ $setting_key ] : ''
				);

				if ( 'image_url' === $setting_key ) {
					if ( $this->value( $setting_key ) ) {
						// Get the attachment model for the existing file.
						$attachment_id = attachment_url_to_postid( $this->value( $setting_key ) );
						if ( $attachment_id ) {
							$this->json['attachment'] = wp_prepare_attachment_for_js( $attachment_id );
						}
					}
				}
				elseif ( 'repeat' === $setting_key ) {
					$this->json[ $setting_key ]['choices'] = $background_choices['repeat'];
				}
				elseif ( 'size' === $setting_key ) {
					$this->json[ $setting_key ]['choices'] = $background_choices['size'];
				}
				elseif ( 'position' === $setting_key ) {
					$this->json[ $setting_key ]['choices'] = $background_choices['position'];
				}
				elseif ( 'attach' === $setting_key ) {
					$this->json[ $setting_key ]['choices'] = $background_choices['attach'];
				}
			}

		}

		/**
		 * Render a JS template for the content of the media control.
		 *
		 * @since 1.0.0
		 */
		public function content_template() {

			parent::content_template();
			?>

			<div class="background-image-fields">
			<# if ( data.attachment && data.repeat && data.repeat.choices ) { #>
				<li class="background-image-repeat">
					<# if ( data.repeat.label ) { #>
						<span class="customize-control-title">{{ data.repeat.label }}</span>
					<# } #>
					<select {{{ data.repeat.link }}}>
						<# _.each( data.repeat.choices, function( label, choice ) { #>
							<option value="{{ choice }}" <# if ( choice === data.repeat.value ) { #> selected="selected" <# } #>>{{ label }}</option>
						<# } ) #>
					</select>
				</li>
			<# } #>

			<# if ( data.attachment && data.size && data.size.choices ) { #>
				<li class="background-image-size">
					<# if ( data.size.label ) { #>
						<span class="customize-control-title">{{ data.size.label }}</span>
					<# } #>
					<select {{{ data.size.link }}}>
						<# _.each( data.size.choices, function( label, choice ) { #>
							<option value="{{ choice }}" <# if ( choice === data.size.value ) { #> selected="selected" <# } #>>{{ label }}</option>
						<# } ) #>
					</select>
				</li>
			<# } #>

			<# if ( data.attachment && data.position && data.position.choices ) { #>
				<li class="background-image-position">
					<# if ( data.position.label ) { #>
						<span class="customize-control-title">{{ data.position.label }}</span>
					<# } #>
					<select {{{ data.position.link }}}>
						<# _.each( data.position.choices, function( label, choice ) { #>
							<option value="{{ choice }}" <# if ( choice === data.position.value ) { #> selected="selected" <# } #>>{{ label }}</option>
						<# } ) #>
					</select>
				</li>
			<# } #>

			<# if ( data.attachment && data.attach && data.attach.choices ) { #>
				<li class="background-image-attach">
					<# if ( data.attach.label ) { #>
						<span class="customize-control-title">{{ data.attach.label }}</span>
					<# } #>
					<select {{{ data.attach.link }}}>
						<# _.each( data.attach.choices, function( label, choice ) { #>
							<option value="{{ choice }}" <# if ( choice === data.attach.value ) { #> selected="selected" <# } #>>{{ label }}</option>
						<# } ) #>
					</select>
				</li>
			<# } #>

			</div>

			<?php
		}

		/**
		 * Returns button labels.
		 *
		 * @since 1.0.0
		 */
		public static function get_button_labels() {

			$button_labels = array(
				'select'       => esc_html__( 'Select Image', 'my-music-band' ),
				'change'       => esc_html__( 'Change Image', 'my-music-band' ),
				'remove'       => esc_html__( 'Remove', 'my-music-band' ),
				'default'      => esc_html__( 'Default', 'my-music-band' ),
				'placeholder'  => esc_html__( 'No image selected', 'my-music-band' ),
				'frame_title'  => esc_html__( 'Select Image', 'my-music-band' ),
				'frame_button' => esc_html__( 'Choose Image', 'my-music-band' ),
			);

			return $button_labels;

		}

		/**
		 * Returns field labels.
		 *
		 * @since 1.0.0
		 */
		public static function get_field_labels() {

			$field_labels = array(
				'repeat'	=> esc_html__( 'Background Repeat', 'my-music-band' ),
				'size'		=> esc_html__( 'Background Size', 'my-music-band' ),
				'position'	=> esc_html__( 'Background Position', 'my-music-band' ),
				'attach'	=> esc_html__( 'Background Attachment', 'my-music-band' )
			);

			return $field_labels;

		}

		/**
		 * Returns the background choices.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_background_choices() {

			$choices = array(
				'repeat' => array(
					'no-repeat' => esc_html__( 'No Repeat', 'my-music-band' ),
					'repeat'    => esc_html__( 'Tile', 'my-music-band' ),
					'repeat-x'  => esc_html__( 'Tile Horizontally', 'my-music-band' ),
					'repeat-y'  => esc_html__( 'Tile Vertically', 'my-music-band' )
				),
				'size' => array(
					'auto'    => esc_html__( 'Default', 'my-music-band' ),
					'cover'   => esc_html__( 'Cover', 'my-music-band' ),
					'contain' => esc_html__( 'Contain', 'my-music-band' )
				),
				'position' => array(
					'left-top'      => esc_html__( 'Left Top', 'my-music-band' ),
					'left-center'   => esc_html__( 'Left Center', 'my-music-band' ),
					'left-bottom'   => esc_html__( 'Left Bottom', 'my-music-band' ),
					'right-top'     => esc_html__( 'Right Top', 'my-music-band' ),
					'right-center'  => esc_html__( 'Right Center', 'my-music-band' ),
					'right-bottom'  => esc_html__( 'Right Bottom', 'my-music-band' ),
					'center-top'    => esc_html__( 'Center Top', 'my-music-band' ),
					'center-center' => esc_html__( 'Center Center', 'my-music-band' ),
					'center-bottom' => esc_html__( 'Center Bottom', 'my-music-band' )
				),
				'attach' => array(
					'fixed'   => esc_html__( 'Fixed', 'my-music-band' ),
					'scroll'  => esc_html__( 'Scroll', 'my-music-band' )
				)
			);

			return $choices;

		}

	}
}
add_action( 'customize_register', 'my_music_band_custom_controls', 1 );
