<?php

namespace Netdust\Service\Scripts;


use Netdust\Traits\Decorator;
use Netdust\Traits\Features;
use Netdust\Traits\Setters;

class AdminScript implements ScriptInterface
{
    use Setters;
    use Features;
    use Decorator;

    public function __construct(ScriptInterface $script ) {
        $this->decorated = $script;
    }

    public function do_actions() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
        $this->decorated->do_actions();
    }

    public function enqueue() {
        $this->decorated->enqueue();
    }
}