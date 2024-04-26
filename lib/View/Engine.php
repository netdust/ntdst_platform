<?php

namespace Netdust\View;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Template API and environment settings storage.
 */
class Engine
{

    /**
     * Collection of preassigned template data.
     * @var array
     */
    protected array $data;

    /**
     * Collection of templates.
     * @var array
     */
    protected array $templates;


    /**
     * Create new Engine instance.
     * @param string $directory
     * @param string $fileExtension
     */
    public function __construct()
    {
        $this->data = [];
    }

    /**
     * Add preassigned template data.
     * @param  array             $data;
     * @param  null|string|array $templates;
     * @return Engine
     */
    public function addData(array $data, mixed $templates = null): Engine
    {
        if( empty($templates) )
            $templates = 'global';

        $templates = is_array($templates) ? $templates : [$templates];
        foreach ($templates as $template) {
            if (isset($this->data[$template])) {
                $this->data[$template] = array_merge($this->data[$template], $data);
            } else {
                $this->data[$template] = $data;
            }
        }

        return $this;
    }

    /**
     * Get all preassigned template data.
     * @param  string|null $name;
     * @return array
     */
    public function getData(string $name = null): array
    {
        $data = $this->data['global'];
        return array_key_exists($name, $this->data) ? array_merge($data,$this->data[$name]):$data;
    }


    /**
     * Get a template path.
     * @param  string $name
     * @param  string $layout
     * @return string
     */
    public function path(string $name, string $layout): string
    {
        $template = $this->templates[$name];

        return !empty($template) && $template->get_path( $layout );
    }

    /**
     * Check if a template exists.
     * @param  string  $name
     * @return boolean
     */
    public function exists(string $name): bool
    {
        $template = $this->templates[$name];

        return !empty($template) && $template->exists();
    }

    /**
     * Create a new template.
     * @param  string   $name
     * @param  array    $data
     * @return Template
     */
    public function make(string $name='', array $data = array()): Template
    {
        if( !empty( $name )) {
            $template_data = $this->getData($name);
            return $this->templates[$name] = new Template($name, array_merge($template_data, $data));
        }

        return new Template($name, $data);
    }

    /**
     * Create a new template and render it.
     * @param  string  $layout
     * @param  array  $data
     * @return string
     */
    public function render(string $layout, array $data = array()): string
    {
        return $this->make()->render( $layout, $data );
    }

}