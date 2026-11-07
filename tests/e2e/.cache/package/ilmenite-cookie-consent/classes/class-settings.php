<?php

class ILCC_Settings {

	public static function hooks() {
		add_action( 'customize_register', [ self::class, 'customizer' ] );

		if ( true === apply_filters( 'ilcc_tracker_settings_enabled', true ) ) {
			add_action( 'admin_init', [ self::class, 'integrity_settings' ] );
			add_action( 'admin_menu', [ self::class, 'integrity_settings_page' ] );
		}
	}

	/**
	 * Get the default value of a text option.
	 *
	 * Used by both the getters and the Customizer, so what the Customizer
	 * shows is what visitors see.
	 *
	 * @param  string  $option  Option name.
	 *
	 * @return string
	 */
	public static function get_default( $option ) {
		switch ( $option ) {
			case 'ilcc_title':
				return __( 'This website uses cookies', 'ilmenite-cookie-consent' );
			case 'ilcc_text':
				/* translators: 1. Policy URL */
				return sprintf( __( 'We use cookies to analyze our traffic, personalize marketing and to provide social media features. <a href="%s" rel="nofollow">Privacy and cookies policy ›</a>.', 'ilmenite-cookie-consent' ), self::get_policy_url() );
			case 'ilcc_button':
				return __( 'Allow All Cookies', 'ilmenite-cookie-consent' );
			case 'ilcc_only_necessary_text':
				return __( 'Only Necessary', 'ilmenite-cookie-consent' );
			case 'ilcc_configure_settings_text':
				return __( 'Configure Settings', 'ilmenite-cookie-consent' );
			case 'ilcc_style':
				return 'overlay';
			case 'ilcc_settings_title':
				return __( 'Select Cookies', 'ilmenite-cookie-consent' );
			case 'ilcc_settings_description':
				return __( 'Cookies are small text files that the web server stores on your computer when you visit the website.', 'ilmenite-cookie-consent' );
			case 'ilcc_settings_necessary_heading':
				return __( 'Necessary', 'ilmenite-cookie-consent' );
			case 'ilcc_settings_necessary_description':
				return __( 'These cookies cannot be disabled. They are required for the website to work.', 'ilmenite-cookie-consent' );
			case 'ilcc_settings_analytics_heading':
				return __( 'Analytics', 'ilmenite-cookie-consent' );
			case 'ilcc_settings_analytics_description':
				return __( 'To be able to improve the website including information and functionality we want to gather analytics. We are not able to identify you personally using this data.', 'ilmenite-cookie-consent' );
			case 'ilcc_settings_marketing_heading':
				return __( 'Marketing', 'ilmenite-cookie-consent' );
			case 'ilcc_settings_marketing_description':
				return __( 'By sharing your browsing behavior on our website we are able to serve you with personalized content and offers.', 'ilmenite-cookie-consent' );
			case 'ilcc_save_settings_text':
				return __( 'Save Settings', 'ilmenite-cookie-consent' );
		}

		return '';
	}

	/**
	 * Get the Policy URL from the settings.
	 *
	 * @return string
	 */
	public static function get_policy_url() {
		$wp_policy_url_page_id = get_option( 'wp_page_for_privacy_policy' );
		$default_url           = '#';
		if ( $wp_policy_url_page_id ) {
			$default_url = get_permalink( $wp_policy_url_page_id );
		}

		return apply_filters( 'ilcc_policy_url', get_option( 'ilcc_policy_url', $default_url ) );
	}

	/**
	 * Get the informational consent title.
	 *
	 * @return string
	 */
	public static function get_consent_title() {
		$title = get_option( 'ilcc_title', self::get_default( 'ilcc_title' ) );

		return apply_filters( 'ilcc_consent_title', $title );
	}

	/**
	 * Get the informational consent text.
	 *
	 * @return string
	 */
	public static function get_consent_text() {

		$policy_url = self::get_policy_url();

		$text = get_option( 'ilcc_text', self::get_default( 'ilcc_text' ) );

		// check if we have linkstart and linkend, replace with link
		if ( strpos( $text, '%linkstart%' ) !== false && strpos( $text, '%linkend%' ) !== false ) {
			$text = str_replace( '%linkstart%', '<a href="' . $policy_url . '" rel="nofollow">', $text );
			$text = str_replace( '%linkend%', '</a>', $text );
		} // if we only have linkstart but no linked, add linkend
		elseif ( strpos( $text, '%linkstart%' ) !== false && strpos( $text, '%linkend%' ) === false ) {
			$text = str_replace( '%linkstart%', '<a href="' . $policy_url . '" rel="nofollow">', $text );
			$text = $text . '</a>';
		} // if we have linkend, but no linkstart, remove linkend
		elseif ( strpos( $text, '%linkstart%' ) === false && strpos( $text, '%linkend%' ) !== false ) {
			$text = str_replace( '%linkend%', '', $text );
		} // if we have a start a-tag but no end, add the end
		elseif ( strpos( $text, '<a' ) !== false && strpos( $text, '</a' ) === false ) {
			$text = $text . '</a>';
		} // if we only have an end a-tag, remove the endtag.
		elseif ( strpos( $text, '<a' ) === false && strpos( $text, '</a' ) !== false ) {
			$text = str_replace( '</a>', '', $text );
		}


		return apply_filters( 'ilcc_consent_text', $text, $policy_url );
	}

	/**
	 * Get the text for the accept button.
	 *
	 * @return string
	 */
	public static function get_accept_text() {
		$text = get_option( 'ilcc_button', self::get_default( 'ilcc_button' ) );

		return apply_filters( 'ilcc_accept_text', $text );
	}

	/**
	 * Get the text for the accept button.
	 *
	 * @return string
	 */
	public static function get_only_necessary_text() {
		$text = get_option( 'ilcc_only_necessary_text', self::get_default( 'ilcc_only_necessary_text' ) );

		return apply_filters( 'ilcc_only_necessary_text', $text );
	}

	/**
	 * Get the text for the settings configuration link.
	 *
	 * @return string
	 */
	public static function get_configure_settings_text() {
		$text = get_option( 'ilcc_configure_settings_text', self::get_default( 'ilcc_configure_settings_text' ) );

		return apply_filters( 'ilcc_configure_settings_text', $text );
	}

	/**
	 * Get the style for the banner.
	 *
	 * @return string
	 */
	public static function get_style() {
		$style = get_option( 'ilcc_style', self::get_default( 'ilcc_style' ) );

		return apply_filters( 'ilcc_style', $style );
	}

	/**
	 * Get the heading for the necessary cookies section.
	 *
	 * @return string
	 */
	public static function get_settings_necessary_heading() {
		$text = get_option( 'ilcc_settings_necessary_heading', self::get_default( 'ilcc_settings_necessary_heading' ) );

		return apply_filters( 'ilcc_settings_necessary_heading', $text );
	}

	/**
	 * Get the description for the necessary cookies section.
	 *
	 * @return string
	 */
	public static function get_settings_necessary_description() {
		$text = get_option( 'ilcc_settings_necessary_description', self::get_default( 'ilcc_settings_necessary_description' ) );

		return apply_filters( 'ilcc_settings_necessary_description', $text );
	}

	/**
	 * Get the heading for the marketing cookies section.
	 *
	 * @return string
	 */
	public static function get_settings_marketing_heading() {
		$text = get_option( 'ilcc_settings_marketing_heading', self::get_default( 'ilcc_settings_marketing_heading' ) );

		return apply_filters( 'ilcc_settings_marketing_heading', $text );
	}

	/**
	 * Get the description for the marketing cookies section.
	 *
	 * @return string
	 */
	public static function get_settings_marketing_description() {
		$text = get_option( 'ilcc_settings_marketing_description', self::get_default( 'ilcc_settings_marketing_description' ) );

		return apply_filters( 'ilcc_settings_marketing_description', $text );
	}

	/**
	 * Get the heading for the analytics cookies section.
	 *
	 * @return string
	 */
	public static function get_settings_analytics_heading() {
		$text = get_option( 'ilcc_settings_analytics_heading', self::get_default( 'ilcc_settings_analytics_heading' ) );

		return apply_filters( 'ilcc_settings_analytics_heading', $text );
	}

	/**
	 * Get the description for the analytics cookies section.
	 *
	 * @return string
	 */
	public static function get_settings_analytics_description() {
		$text = get_option( 'ilcc_settings_analytics_description', self::get_default( 'ilcc_settings_analytics_description' ) );

		return apply_filters( 'ilcc_settings_analytics_description', $text );
	}

	/**
	 * Get the save settings button title.
	 *
	 * @return string
	 */
	public static function get_save_settings_button_title() {
		$text = get_option( 'ilcc_save_settings_text', self::get_default( 'ilcc_save_settings_text' ) );

		return apply_filters( 'ilcc_save_settings_text', $text );
	}

	/**
	 * Get the settings title.
	 *
	 * @return string
	 */
	public static function get_settings_title() {
		$accept = get_option( 'ilcc_settings_title', self::get_default( 'ilcc_settings_title' ) );

		return apply_filters( 'ilcc_settings_title', $accept );
	}

	/**
	 * Get the save settings button title.
	 *
	 * @return string
	 */
	public static function get_settings_description() {
		$text = get_option( 'ilcc_settings_description', self::get_default( 'ilcc_settings_description' ) );

		return apply_filters( 'ilcc_settings_description', $text );
	}

	/**
	 * Check whether the analytics section should be shown or not.
	 *
	 * @return bool
	 */
	public static function is_analytics_shown() {
		$hidden = empty( get_option( 'ilcc_settings_analytics_is_shown' ) );
		$shown  = ! $hidden;

		return apply_filters( 'ilcc_settings_analytics_is_shown', $shown );
	}

	/**
	 * Check whether the marketing section should be shown or not.
	 *
	 * @return bool
	 */
	public static function is_marketing_shown() {
		$hidden = empty( get_option( 'ilcc_settings_marketing_is_shown' ) );
		$shown  = ! $hidden;

		return apply_filters( 'ilcc_settings_marketing_is_shown', $shown );
	}

	/**
	 * Check whether the Google Consent Mode integration is enabled.
	 *
	 * @return bool
	 */
	public static function is_consent_mode_integration_enabled() {
		$enabled = get_option( 'ilcc_settings_google_consent_mode_enabled' );
		$enabled = $enabled === '1' || $enabled === true || $enabled === 'true';


		return apply_filters( 'ilcc_settings_google_consent_mode_enabled', $enabled );
	}

	/**
	 * Get the Customizer sections, keyed by ID.
	 *
	 * @return array
	 */
	protected static function get_customizer_sections() {
		return [
			'ilmenite_cookie_banner_style'        => __( 'Style', 'ilmenite-cookie-consent' ),
			'ilmenite_cookie_banner_general'      => __( 'General', 'ilmenite-cookie-consent' ),
			'ilmenite_cookie_banner_necessary'    => __( 'Section: Necessary', 'ilmenite-cookie-consent' ),
			'ilmenite_cookie_banner_analytics'    => __( 'Section: Analytics', 'ilmenite-cookie-consent' ),
			'ilmenite_cookie_banner_marketing'    => __( 'Section: Marketing', 'ilmenite-cookie-consent' ),
			'ilmenite_cookie_banner_integrations' => __( 'Integrations', 'ilmenite-cookie-consent' ),
		];
	}

	/**
	 * Get the Customizer fields, in display order.
	 *
	 * Each field is stored as an option with the same name.
	 *
	 * @return array
	 */
	protected static function get_customizer_fields() {
		return [
			'ilcc_style'                                => [
				'section'     => 'ilmenite_cookie_banner_style',
				'capability'  => 'ilcc_edit_style_capability',
				'type'        => 'radio',
				'label'       => __( 'Style', 'ilmenite-cookie-consent' ),
				'description' => __( 'The banner can appear both at the top, or overlaid at the bottom of the page.', 'ilmenite-cookie-consent' ),
				'choices'     => self::get_style_choices(),
				'sanitize'    => [ self::class, 'sanitize_style' ],
			],
			'ilcc_title'                                => [
				'section'     => 'ilmenite_cookie_banner_general',
				'capability'  => 'ilcc_edit_title_capability',
				'label'       => __( 'Title', 'ilmenite-cookie-consent' ),
				'description' => __( 'Keep the title short. It is styled prominently.', 'ilmenite-cookie-consent' ),
				'sanitize'    => 'sanitize_text_field',
			],
			'ilcc_text'                                 => [
				'section'     => 'ilmenite_cookie_banner_general',
				'capability'  => 'ilcc_edit_text_capability',
				'type'        => 'textarea',
				'label'       => __( 'Description', 'ilmenite-cookie-consent' ),
				'description' => __( 'A secondary line of info about your cookie usage. Remember to link to the policy by using the %linkstart% and %linkend% placeholders.', 'ilmenite-cookie-consent' ),
				'sanitize'    => 'wp_kses_post',
			],
			'ilcc_policy_url'                           => [
				'section'     => 'ilmenite_cookie_banner_general',
				'capability'  => 'ilcc_edit_policy_url_capability',
				'default'     => get_privacy_policy_url(),
				'label'       => __( 'Cookie Policy Link', 'ilmenite-cookie-consent' ),
				'description' => __( 'Enter a link to your privacy and cookie policy where you outline the use of cookies. If left blank, the privacy policy page from Settings > Privacy will be used.', 'ilmenite-cookie-consent' ),
				'sanitize'    => 'esc_url_raw',
			],
			'ilcc_button'                               => [
				'section'     => 'ilmenite_cookie_banner_general',
				'label'       => __( 'Accept All Button Text', 'ilmenite-cookie-consent' ),
				'description' => __( 'Displays the message on the call to action button that adds consent for everything.', 'ilmenite-cookie-consent' ),
			],
			'ilcc_only_necessary_text'                  => [
				'section'     => 'ilmenite_cookie_banner_general',
				'label'       => __( 'Only Necessary Cookies Button Text', 'ilmenite-cookie-consent' ),
				'description' => __( 'A secondary button that displays a call to action where the user can accept only the necessary cookies on the page. This essentially just closes the banner.', 'ilmenite-cookie-consent' ),
			],
			'ilcc_configure_settings_text'              => [
				'section'     => 'ilmenite_cookie_banner_general',
				'label'       => __( 'Configure Settings Button Text', 'ilmenite-cookie-consent' ),
				'description' => __( 'A small button/link that the user can click on to open more fine-grained settings.', 'ilmenite-cookie-consent' ),
			],
			'ilcc_settings_title'                       => [
				'section'     => 'ilmenite_cookie_banner_general',
				'label'       => __( 'Settings Title', 'ilmenite-cookie-consent' ),
				'description' => __( 'The title for the settings selection area.', 'ilmenite-cookie-consent' ),
			],
			'ilcc_settings_description'                 => [
				'section'     => 'ilmenite_cookie_banner_general',
				'label'       => __( 'Settings Description', 'ilmenite-cookie-consent' ),
				'description' => __( 'A description to further let people know what cookies are for and why they can select different options.', 'ilmenite-cookie-consent' ),
				'sanitize'    => 'wp_kses_post',
			],
			'ilcc_settings_necessary_heading'           => [
				'section'     => 'ilmenite_cookie_banner_necessary',
				'label'       => __( 'Necessary Heading', 'ilmenite-cookie-consent' ),
				'description' => __( 'A title for the necessary cookie group.', 'ilmenite-cookie-consent' ),
			],
			'ilcc_settings_necessary_description'       => [
				'section'     => 'ilmenite_cookie_banner_necessary',
				'type'        => 'textarea',
				'label'       => __( 'Necessary Description', 'ilmenite-cookie-consent' ),
				'description' => __( 'Describes what the necessary cookies are used for.', 'ilmenite-cookie-consent' ),
				'sanitize'    => 'wp_kses_post',
			],
			'ilcc_settings_analytics_is_shown'          => [
				'section'     => 'ilmenite_cookie_banner_analytics',
				'type'        => 'checkbox',
				'default'     => '',
				'label'       => __( 'Show Analytics Section', 'ilmenite-cookie-consent' ),
				'description' => __( 'When checked the analytics configuration section is shown. If you have no analytics trackers you can disable this section.', 'ilmenite-cookie-consent' ),
				'sanitize'    => [ self::class, 'sanitize_checkbox' ],
			],
			'ilcc_settings_analytics_heading'           => [
				'section'     => 'ilmenite_cookie_banner_analytics',
				'label'       => __( 'Analytics Heading', 'ilmenite-cookie-consent' ),
				'description' => __( 'A title for the analytics cookie group.', 'ilmenite-cookie-consent' ),
			],
			'ilcc_settings_analytics_description'       => [
				'section'     => 'ilmenite_cookie_banner_analytics',
				'type'        => 'textarea',
				'label'       => __( 'Analytics Description', 'ilmenite-cookie-consent' ),
				'description' => __( 'Describes what the analytics cookies are used for.', 'ilmenite-cookie-consent' ),
				'sanitize'    => 'wp_kses_post',
			],
			'ilcc_settings_marketing_is_shown'          => [
				'section'     => 'ilmenite_cookie_banner_marketing',
				'type'        => 'checkbox',
				'default'     => '',
				'label'       => __( 'Show Marketing Section', 'ilmenite-cookie-consent' ),
				'description' => __( 'When checked the marketing configuration section is shown. If you have no marketing trackers you can disable this section.', 'ilmenite-cookie-consent' ),
				'sanitize'    => [ self::class, 'sanitize_checkbox' ],
			],
			'ilcc_settings_marketing_heading'           => [
				'section'     => 'ilmenite_cookie_banner_marketing',
				'label'       => __( 'Marketing Heading', 'ilmenite-cookie-consent' ),
				'description' => __( 'A title for the marketing cookie group.', 'ilmenite-cookie-consent' ),
			],
			'ilcc_settings_marketing_description'       => [
				'section'     => 'ilmenite_cookie_banner_marketing',
				'type'        => 'textarea',
				'label'       => __( 'Marketing Description', 'ilmenite-cookie-consent' ),
				'description' => __( 'Describes what the marketing cookies are used for.', 'ilmenite-cookie-consent' ),
				'sanitize'    => 'wp_kses_post',
			],
			'ilcc_settings_google_consent_mode_enabled' => [
				'section'     => 'ilmenite_cookie_banner_integrations',
				'type'        => 'checkbox',
				'default'     => '',
				'label'       => __( 'Enable Consent Mode Integration', 'ilmenite-cookie-consent' ),
				'description' => __( 'When checked the consent given by the user will integrate with Google Consent Mode v2.', 'ilmenite-cookie-consent' ),
				'sanitize'    => [ self::class, 'sanitize_checkbox' ],
			],
			'ilcc_save_settings_text'                   => [
				'section'     => 'ilmenite_cookie_banner_general',
				'label'       => __( 'Save Settings Button Text', 'ilmenite-cookie-consent' ),
				'description' => __( 'The label of the button that lets users save their settings.', 'ilmenite-cookie-consent' ),
			],
		];
	}

	/**
	 * Get the available banner styles.
	 *
	 * @return array
	 */
	public static function get_style_choices() {
		return [
			'top'      => __( 'Top', 'ilmenite-cookie-consent' ),
			'overlay'  => __( 'Overlay', 'ilmenite-cookie-consent' ),
			'takeover' => __( 'Take Over', 'ilmenite-cookie-consent' ),
		];
	}

	/**
	 * Sanitize the banner style.
	 *
	 * @param  string  $value
	 *
	 * @return string
	 */
	public static function sanitize_style( $value ) {
		return array_key_exists( $value, self::get_style_choices() ) ? $value : self::get_default( 'ilcc_style' );
	}

	/**
	 * Sanitize a checkbox value.
	 *
	 * @param  mixed  $value
	 *
	 * @return string '1' when checked, '' otherwise.
	 */
	public static function sanitize_checkbox( $value ) {
		return ( ! empty( $value ) && 'false' !== $value ) ? '1' : '';
	}

	/**
	 * Add settings in the customizer.
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @return void
	 */
	public static function customizer( $wp_customize ) {

		/**
		 * Set the filter to false to prevent the customizer
		 * settings from showing up.
		 */
		if ( ! apply_filters( 'ilcc_enable_customizer', true ) ) {
			return;
		}

		$wp_customize->add_panel( 'ilmenite_cookie_banner', [
			'priority'    => 120,
			'capability'  => apply_filters( 'ilcc_edit_style_capability', 'edit_theme_options' ),
			'title'       => __( 'Cookie Banner', 'ilmenite-cookie-consent' ),
			'description' => __( 'Customize the appearance and texts in the cookie banner, used for EU cookie compliance.', 'ilmenite-cookie-consent' ),
		] );

		foreach ( self::get_customizer_sections() as $id => $title ) {
			$wp_customize->add_section( $id, [
				'title' => $title,
				'panel' => 'ilmenite_cookie_banner',
			] );
		}

		foreach ( self::get_customizer_fields() as $id => $field ) {
			$capability = isset( $field['capability'] ) ? $field['capability'] : 'ilcc_edit_button_capability';

			$wp_customize->add_setting( $id, [
				'default'           => array_key_exists( 'default', $field ) ? $field['default'] : self::get_default( $id ),
				'type'              => 'option',
				'capability'        => apply_filters( $capability, 'edit_theme_options' ),
				'sanitize_callback' => isset( $field['sanitize'] ) ? $field['sanitize'] : 'sanitize_text_field',
			] );

			$control = [
				'label'       => $field['label'],
				'description' => $field['description'],
				'settings'    => $id,
				'section'     => $field['section'],
				'priority'    => 80,
			];

			if ( isset( $field['type'] ) ) {
				$control['type'] = $field['type'];
			}

			if ( isset( $field['choices'] ) ) {
				$control['choices'] = $field['choices'];
			}

			$wp_customize->add_control( new \WP_Customize_Control( $wp_customize, $id, $control ) );
		}
	}

	public static function integrity_settings() {

		add_settings_section( 'ilcc_trackers_necessary', __( 'Necessary', 'ilmenite-cookie-consent' ), function () {
			?>
			<p class="section-lead"><?php esc_html_e( 'A user cannot opt out of allowing necessary cookies. These should be cookies that are crucial to the functionality of the site.', 'ilmenite-cookie-consent' ); ?></p>
			<?php
		}, 'ilcc-trackers' );

		add_settings_field( 'ilcc_domains_necessary', __( 'Domains', 'ilmenite-cookie-consent' ), function () {
			?>
			<textarea name="ilcc_domains_necessary" id="ilcc_domains_necessary" style="width: 100%; max-width: 35rem;" rows="10"><?php echo esc_html( implode( "\n", ILCC_Trackers::get_necessary() ) ); ?></textarea>
			<p class="form-description"><?php esc_html_e( 'Enter one domain per line.', 'ilmenite-cookie-consent' ); ?></p>
			<?php
		}, 'ilcc-trackers', 'ilcc_trackers_necessary' );

		add_settings_section( 'ilcc_trackers_analytics', __( 'Analytics', 'ilmenite-cookie-consent' ), function () {
			?>
			<p class="section-lead"><?php esc_html_e( 'Analytics cookies measure how visitors use the website, such as which pages they visit and for how long. Many privacy regulations require consent before these are set.', 'ilmenite-cookie-consent' ); ?></p>
			<?php
		}, 'ilcc-trackers' );

		add_settings_field( 'ilcc_domains_analytics', __( 'Domains', 'ilmenite-cookie-consent' ), function () {
			?>
			<textarea name="ilcc_domains_analytics" id="ilcc_domains_analytics" style="width: 100%; max-width: 35rem;" rows="10"><?php echo esc_html( implode( "\n", ILCC_Trackers::get_analytics() ) ); ?></textarea>
			<p class="form-description"><?php esc_html_e( 'Enter one domain per line.', 'ilmenite-cookie-consent' ); ?></p>
			<?php
		}, 'ilcc-trackers', 'ilcc_trackers_analytics' );

		add_settings_section( 'ilcc_trackers_marketing', __( 'Marketing', 'ilmenite-cookie-consent' ), function () {
			?>
			<p class="section-lead"><?php esc_html_e( 'Marketing cookies normally track the user behavior either to log in a CRM system or to serve personalized advertising. It is essential for privacy regulation compliance to separate these out and give users an informed choice.', 'ilmenite-cookie-consent' ); ?></p>
			<?php
		}, 'ilcc-trackers' );

		add_settings_field( 'ilcc_domains_marketing', __( 'Domains', 'ilmenite-cookie-consent' ), function () {
			?>
			<textarea name="ilcc_domains_marketing" id="ilcc_domains_marketing" style="width: 100%; max-width: 35rem;" rows="10"><?php echo esc_html( implode( "\n", ILCC_Trackers::get_marketing() ) ); ?></textarea>
			<p class="form-description"><?php esc_html_e( 'Enter one domain per line.', 'ilmenite-cookie-consent' ); ?></p>
			<?php
		}, 'ilcc-trackers', 'ilcc_trackers_marketing' );

		register_setting( 'ilcc-trackers', 'ilcc_domains_necessary' );
		register_setting( 'ilcc-trackers', 'ilcc_domains_analytics' );
		register_setting( 'ilcc-trackers', 'ilcc_domains_marketing' );
	}

	public static function integrity_settings_page() {
		add_options_page( __( 'Tracker Control', 'ilmenite-cookie-consent' ), __( 'Tracker Control', 'ilmenite-cookie-consent' ), 'manage_options', 'ilcc-trackers', function () {
			?>
			<div class="wrap">
				<h1><?php esc_html_e( 'Tracker Control', 'ilmenite-cookie-consent' ); ?></h1>
				<p class="lead"><?php esc_html_e( 'For privacy regulation compliance it is important to give the user an informed decision to be tracked and profiled. We do this by dividing any cookie setting domains into three categories.', 'ilmenite-cookie-consent' ); ?></p>
				<p><?php esc_html_e( 'Adding domains to these disallow-lists will ensure that scripts aren\'t loading and sending data through before the user has consented.', 'ilmenite-cookie-consent' ); ?></p>
				<form action='options.php' method='post'>
					<?php
					settings_fields( 'ilcc-trackers' );
					do_settings_sections( 'ilcc-trackers' );
					submit_button();
					?>
				</form>
			</div>
			<style>

				p {
					max-width: 45rem;
				}

				p.lead {
					font-size: 1.1rem;
					opacity: 0.8;
					margin-top: 0;
				}

				h2 {
					margin-bottom: 0.5em;
				}

				.section-lead {
					margin-top: 0;
				}

				p.form-description {
					font-style: italic;
					font-size: 85%;
				}
			</style>
			<?php
		} );
	}


}
