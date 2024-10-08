<?php

namespace Netdust\Service\Scripts;

use Netdust\Logger\Logger;
use Netdust\Traits\Decorator;
use Netdust\Traits\Features;
use Netdust\Traits\Setters;

class FrontScript implements ScriptInterface
{
    use Setters;
    use Features;
    use Decorator;

	public $decorated;

    public function __construct(ScriptInterface $script ) {
        $this->decorated = $script;
    }

    public function do_actions(): void {
	    add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
        $this->decorated->do_actions();
    }

    public function enqueue(): void {
        if( !is_admin() ) {
	        $this->decorated->enqueue();
        }
    }
}