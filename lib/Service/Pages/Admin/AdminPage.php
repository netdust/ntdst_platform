<?php
/**
 * Admin Page
 *
 * @since   1.0.0
 */


namespace Netdust\Service\Pages\Admin;

use Netdust\App;
use Netdust\Logger\Logger;
use Netdust\Logger\LoggerInterface;
use Netdust\Traits\Classes;
use Netdust\Traits\Features;
use Netdust\Traits\Setters;
use Netdust\Traits\Templates;
use WP_Error;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Page
 *
 * @since   1.0.0
 */
class AdminPage {

	use Templates;
	use Features;
    use Setters;

	/**
	 * Parent slug for this menu. This can either be a slug, or a registered primary menu key
	 * If no slug is specified, this menu will still be registered, but it will not appear in the WordPress menu.
	 *
	 * @since 1.0.0
	 *
	 * @var string the parent slug.
	 */
	protected string $parent_menu = 'options-general.php';

	/**
	 * The title to display in the admin Page.
	 *
	 * @since 1.0.0
	 *
	 * @var string the menu title.
	 */
	protected string $menu_title = '';

	/**
	 * The page title to display on the page.
	 *
	 * @since 1.0.0
	 *
	 * @var string the page title.
	 */
	protected string $page_title = '';

	/**
	 * The capability required to visit this admin page.
	 *
	 * @since 1.0.0
	 *
	 * @var string the capability.
	 */
	protected string $capability = 'administrator';

	/**
	 * The unique identifier for this menu.
	 *
	 * @since 1.0.0
	 *
	 * @var string the menu slug.
	 */
	protected string $menu_slug = '';

	/**
	 * The slug for the menu that this should be placed under.
	 *
	 * @since 1.0.0
	 *
	 * @var string the menu slug.
	 */
	protected string $parent_slug = '';

	/**
	 * Icon to display in menu.
	 *
	 * @var string The URL to the icon to be used for this menu. Can be a base64-encoded SVG, a dashicons helper class,
	 *      or an empty string, if you want to manipulate this via CSS.
	 */
	public string $icon = '';

	/**
	 * The position in the menu order this item should appear.
	 *
	 * @since 1.0.0
	 *
	 * @var int the menu position.
	 */
	protected ?int $position = null;


	/**
	 * List of sections.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected array $sections = [];

	/**
	 * The nonce action used to validate when interfacing with this page.
	 *
	 * @since 1.0.0
	 *
	 * @var string the nonce action.
	 */
	protected string $nonce_action = 'netdust_options_action';

	/**
	 * Determines how this settings page will be laid out.
	 * This can be set to "single" or "tabbed".
	 * If it's set to "single", all sections will be put on a single settings page.
	 * If it is set to "tabbed", each section will be placed in its own tab.
	 *
	 * @since 1.0.0
	 *
	 * @var string The layout type. Either "single", or "tabs"
	 */
	protected string $layout = 'single';

    /**
     * AdminPage constructor
     *
     * @param array $args Overrides to default args in the object
     */
    public function __construct( array $args = [] ) {
        $this->set_values( $args );
        if( !empty($this->sections) ) foreach ($this->sections as &$section ) {
            App::make( $section['id'] , AdminSection::class, array_merge( $section, ['template_root'=>$this->template_root??''] ) );
            $section = App::get( $section['id'] );
        }
    }

	/**
	 * Callback function to render the actual settings content.
	 *
	 * @since 1.0.0
	 */
	public function render_callback(): void {

		echo $this->get_template( 'admin', [
			'title'        => $this->page_title,
			'section'      => $this->get_current_section_key(),
			'sections'     => $this->get_sections(),
			'menu_slug'    => $this->menu_slug,
			'nonce_action' => $this->nonce_action,
		] );
	}

	/**
	 * Retrieves the name of the current section.
	 *
	 * @since 1.0.0
	 *
	 * @return string The current section name, or an empty string.
	 */
	public function get_current_section_key(): ?string {

        if ( isset( $_GET['section'] ) && ! is_wp_error( $this->section( $_GET['section'] ) ) ) {
			return $_GET['section'];
		}
        if( is_array( $this->sections ) && count( $this->sections ) > 0 ) {
            return reset($this->sections )->id();
        }

        return null;
	}

	/**
	 * @inheritDoc
	 */
	public function do_actions(): void {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 8 );
		add_action( 'admin_post_save_settings', [ $this, 'handle_update_request' ], 99 );
	}

	/**
	 * Determines if the current page is this admin page.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_admin_page(): bool {
		return is_admin() && isset( $_GET['page'] ) && $this->menu_slug === $_GET['page'];
	}


	/**
	 * Retrieves the specified section.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The section to retrieve. If left blank, this will automatically retrieve from GET.
	 *
	 * @return AdminSection|\WP_Error
	 */
	public function section( string $id = '' ): AdminSection|\WP_Error {

		if ( '' === $id ) {
			$id = $this->get_current_section_key();
		}

		if ( isset( $this->sections[ $id ] ) && $this->sections[ $id ] instanceof AdminSection ) {
			return $this->sections[ $id ];
		}

		return app()->make( LoggerInterface::class )->error( 'No valid section could be found', 'no_admin_section_found',
			[ 'sections' => $this->sections, 'admin_page' => $this->parent_menu ]
		);
	}

    public function add_section( AdminSection $section ): void {
        $this->sections[ $section->id() ] = $section;
    }

	public function get_sections(): array {
		return apply_filters('admin_page:sections', $this->sections );
	}

	/**
	 * Validates a submitted request
	 *
	 * @since 1.1.0
	 *
	 * @return bool|WP_Error
	 */
	public function validate_request(): bool|WP_Error {
		$errors = new \WP_Error();

		// If this is not an admin page, bail
		if ( ! is_admin() ) {
			$errors->add(
				'update_request_settings_not_admin',
				__( 'An update request attempted to run outside of the admin area.' ),
				[ 'page' => $_SERVER['REQUEST_URI'] ]
			);
		}

		// If this is not the correct settings page, bail
		if ( ! $this->is_admin_page() ) {
			$errors->add(
				'update_request_settings_invalid_settings_page',
				__( 'An update request attempted to run outside of the specified settings settings page.' ),
				[ 'actual_page' => isset( $_GET['page'] ) ? $_GET['page'] : '', 'expected_page' => $this->menu_slug ]
			);
		}

		// If we don't have a nonce, bail.
		if ( ! isset( $_POST['netdust_nonce'] ) ) {
			$errors->add(
				'update_request_settings_no_nonce',
				__( 'An update request attempted to run without a nonce.' )
			);
		}

		// If the current user can't edit these options, bail
		if ( true !== current_user_can( $this->capability ) ) {
			$errors->add(
				'update_request_settings_invalid_permissions',
				__( 'An update request attempted to run without the privileges.' ),
				[ 'user' => get_current_user_id() ]
			);
		}

		// If the nonce is invalid, bail
		if ( isset( $_POST['netdust_nonce'] ) && 1 !== wp_verify_nonce( $_POST['netdust_nonce'], $this->nonce_action ) ) {
			$errors->add(
				'update_request_settings_invalid_nonce',
				__( 'An update requested attempted to run with an invalid nonce.' )
			);
		}

		return $errors->has_errors() ? $errors : true;
	}


	/**
	 * Callback to handle update requests from the options screen.
	 *
	 * @since 1.0.0
	 *
	 */
	public function handle_update_request():void {

        if ( is_wp_error( $errors = $this->validate_request() ) ) {
            app()->make( LoggerInterface::class )->error( $errors );
        }

        $errors = new \WP_Error();

		if ( 'single' === $this->layout ) {
			foreach ( $this->get_sections() as $section ) {
                $errors = $section->save();
			}
		} else {
            $errors = $this->section()->save();
		}

        if( is_wp_error( $errors ) ) {
            app()->make( LoggerInterface::class )->error( $errors );
        }
        else wp_redirect($_POST['_wp_http_referer']);

	}

	/**
	 * Registers sub menus
	 *
	 * @since 1.0.0
	 */
	public function register_menu(): void {
		if ( empty( $this->parent_slug ) ) {
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->menu_slug,
				[ $this, 'render_callback' ],
				$this->icon,
				$this->position
			);
		} else {
			add_submenu_page(
				$this->parent_slug,
				$this->page_title,
				$this->menu_title,
				$this->capability,
				$this->menu_slug,
				[ $this, 'render_callback' ],
				$this->position
			);
		}

        app()->make( LoggerInterface::class )->info(
			'A page has been added.',
			'admin_page_added'
		);
	}

	/**
	 * Retrieves the admin url of this admin page.
	 *
	 * @since 1.1.0
	 *
	 * @param array $query
	 *
	 * @return string
	 */
	public function get_url( array $query = [] ): string {
        $url = add_query_arg( array(
            'page' => $this->menu_slug,
        ), get_admin_url( null, 'admin.php' ) );

        return add_query_arg( $query, $url );

	}

	/**
	 * Retrieves the URL of the current section.
	 *
	 * @since 1.0.0
	 *
	 * @param string $section The name of the section.
	 *
	 * @return string a URL of the specified section of this settings page.
	 */
	public function get_section_url( string $section ): string {
        $url = $this->get_url();

        if ( ! is_wp_error( $this->sections( $section ) ) ) {
            $url = add_query_arg( 'section', $section, $url );
        }

        return apply_filters( 'admin_section:section_url', $url );
	}

    /**
     * Class utility function to return the settings page title.
     *
     * @since 2.5.0
     *
     * @return string page title.
     */
    public function get_admin_page_title(): string {
        /**
         * Filters whether the admin settings page title should be displayed or not.
         *
         * @param array $flag Whether to display page title or not.
         */
        if ( true === apply_filters( 'admin_page:title_should_display', false ) ) {
            /**
             * Filters admin settings page title HTML output.
             *
             * @param string $title_output The admin settings title page.
             */
            return apply_filters( 'admin_page:title', '<h1>' . esc_html( get_admin_page_title() ) . '</h1>' );
        } else {
            return '<h1 class="netdust-empty-page-title"></h1>';
        }
    }

    /**
     * Class utility function to return the form wrapper. Supports
     * the beginning <form> an ending </form>.
     *
     * @since 2.5.0
     *
     * @param boolean $start Flag to indicate if showing start or end of form.
     *
     * @return string form HTML.
     */
    public function get_admin_page_form( bool $start = true ): string {
        if ( true === $this->settings_form_wrap ) {
            if ( true === $start ) {
                /**
                 * Filters the HTML output for admin page form.
                 *
                 * @param string  $form_output The HTML output for admin page form.
                 * @param boolean $start       A flag to indicate whether it is start or end of the form.
                 */
                return apply_filters( 'admin_page:form_start', '<form id="netdust-settings-page-form" method="post" action="options.php">', $start );
            } else {
                /** This filter is documented in includes/settings/class-ld-settings-pages.php  */
                return apply_filters( 'admin_page:form_end', '</form>', $start );
            }
        }
        return '';
    }

	/**
	 * Fetches the template group name.
	 *
	 * @since 1.0.0
	 *
	 * @return string The template group name
	 */
	protected function get_template_group(): string {
		return 'layouts/' . $this->layout;
	}

    public function __get( string $key ): mixed {
        if ( isset( $this->$key ) ) {
            return $this->$key;
        } else {
            return app()->make( LoggerInterface::class )->error( 'The section key ' . $key . ' could not be found.', 'param_not_set' );
        }
    }

}
