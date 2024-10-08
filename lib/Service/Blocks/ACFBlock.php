<?php
/**
 * Registers a shortcode
 *
 * @since   1.0.0
 * @package Underpin\Abstracts
 */


namespace Netdust\Service\Blocks;

use Netdust\Logger\Logger;
use Netdust\Traits\Features;
use Netdust\Traits\Setters;
use Netdust\Traits\Templates;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Block
 *
 */
class ACFBlock {

    use Templates;
    use Features;
    use Setters;


    protected array $block = [];

    protected array $fields = [];

    public string $title = 'block item';

    public string $category = 'design';

    public ?string $icon = 'excerpt-view';

    public ?array $keywords = array( 'acf' );

    public $render_callback;

    /**
     * ACFBlock constructor
     *
     * @param array $args Overrides to default args in the object
     */
    public function __construct( array $args = [] ) {
        $this->set_values( $args );
    }

    public function get_field( string $name, mixed $default='' ): mixed {
        if(  function_exists( 'get_field') && !is_wp_error($value = get_field( $name ))  &&  !empty( $value ) )
            return $value;

        if( !is_wp_error($value = $this->{$name}) && !empty( $value ) )
            return $value;

        return $default;
    }

    public function add_fields( array $fields=[] ): void {
        if( ! empty( $fields ))
            acf_add_local_field_group( $fields );
    }

    /**
     * @inheritDoc
     */
    public function do_actions(): void {

        $this->block = [
            'name'				=> !empty($this->name) ? $this->name : 'block_item',
            'title'				=> $this->title,
            'description'		=> $this->description,
            'render_callback'   => $this->render_callback ?? [$this,'block_actions'],
            'category'			=> $this->category,
            'icon'				=> $this->icon,
            'keywords'			=> $this->keywords,
            'mode'              => 'auto'
        ];

        add_action('acf/init', [ $this, 'acf_portfolio_item_block' ] );

        Logger::info(
            'A acf block '. $this->block['name'] .' has been added',
            'block_added',
        );
    }

    public function block_actions( ): void {
        $this->echo_template( );
    }

    public function as_shortcode( array $atts ): string {

        $this->set_values( $atts );

        ob_start();
        $this->echo_template( );
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }

    public function echo_template( ): void {
        echo $this->get_template( $this->name );
    }



    public function get_template_group(): string {
        return 'blocks';
    }

    /**
     * The actual shortcode callback. Sets shortcode atts to the class so other methods can access the arguments.
     *
     * @since 1.0.0
     *
     * @param array $atts The shortcode attributes
     * @return mixed The shortcode action result.
     */
    public function acf_portfolio_item_block(  ): void {

        if( function_exists('acf_register_block') ) {

            acf_register_block( $this->block );

        }

    }

    public function __get( string $key ): mixed {
        if ( isset( $this->$key ) ) {
            return $this->$key;
        } else {
            return new \WP_Error( 'post_template_param_not_set', 'The batch task key ' . $key . ' could not be found.' );
        }
    }

}