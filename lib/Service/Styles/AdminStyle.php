<?php

namespace Netdust\Service\Styles;


use Netdust\Logger\Logger;
use Netdust\Traits\Decorator;
use Netdust\Traits\Features;
use Netdust\Traits\Setters;

class AdminStyle implements StyleInterface
{
    use Setters;
    use Features;
    use Decorator;

	public $decorated;

    public function __construct(StyleInterface $style ) {
        $this->decorated = $style;
    }

    public function do_actions(): void {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        $this->decorated->do_actions();
    }

    public function enqueue(): void {
        $this->decorated->enqueue();
    }
}