<?php
/**
 * Action Component for the Button Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Xavier Decuyper <xavier.decuyper@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_button extends DokuWiki_Action_Plugin {

    function register(Doku_Event_Handler $controller){
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar', array ());
    }

    function handle_toolbar(&$event, $param) {
		$syntaxDiv = $this->getConf('syntaxDiv');
        $syntaxSpan = $this->getConf('syntaxSpan');

        $event->data[] = array (
            'type' => 'format',
            'title' => 'Insert button',
            'icon' => '../../plugins/button/images/add-button.png',
            'open' => '[[{}',
            'close' => ']]',
            'sample' => 'Wiki link|Button title'
        );
    }
}

