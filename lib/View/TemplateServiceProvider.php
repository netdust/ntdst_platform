<?php

namespace Netdust\View;


use LogicException;
use lucatume\DI52\ServiceProvider;
use Netdust\ApplicationInterface;
use Netdust\Core\File;
use Netdust\Traits\Mixins;
use Netdust\View\UI\UI;
use Netdust\View\UI\UIHelper;
use Netdust\View\UI\UIInterface;


class TemplateServiceProvider extends ServiceProvider {

    public function register( ): void {

        $this->container->singleton(
            TemplateInterface::class,
            new \Netdust\View\Template( [
                $this->container->get( File::class )->dir_path(),
                $this->container->get( File::class )->template_path(),
                $this->container->get( File::class )->dir_path('services')]
            )
        );

        $this->container->singleton( UIInterface::class, UIHelper::class );
        UI::setUI( $this->container->get(UIInterface::class) );

        $this->template_mixin( $this->container->get( ApplicationInterface::class ) );

    }

    public function add( string $layout, array $data = [] ): string {
        return $this->container->get( TemplateInterface::class )->add( $layout, $data );
    }
    public function render( string $layout, array $data = [] ): string {
        return $this->container->get( TemplateInterface::class )->render( $layout, $data );
    }

    public function print( string $layout, array $data = [] ): void {
        $this->container->get( TemplateInterface::class )->print( $layout, $data );
    }

    public function template_mixin( ServiceProvider $service, string $path = '' ): void {

        if ( !in_array(Mixins::class, class_uses($service), true) ) {
            throw new LogicException('The ServiceProvider is not using the mixins Trait.');
        }

        $service->mixin( 'render', function(string $layout, array $data = array() ) use ( $path ): string {
            return $this->container->get( TemplateInterface::class )->render( $path.$layout, $data );
        });
        $service->mixin( 'print', function(string $layout, array $data = array() ) use ( $path ): void {
            $this->container->get( TemplateInterface::class )->print( $path.$layout, $data );
        });
    }
}