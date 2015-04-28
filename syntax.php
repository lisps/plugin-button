<?php
/**
 * Plugin Button : Add button with image support syntax for links
 * 
 * To be run with Dokuwiki only

 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     RÃ©mi Peyronnet  <remi+xslt@via.ecp.fr>
 
 Full Syntax :
     [[{namespace:image|extra css}wiki page|Title of the link]]

All fields optional, minimal syntax:
	[[{}Simple button]]
 
 Configuration :
	[[{conf.styles}style|css]]
	[[{conf.target}style|target]]
 
 19/05/2013 : Initial release
 20/04/2014 : Added target support (feature request from Andrew St Hilaire)
 07/06/2014 : Added dokuwiki formatting support in title section (not working in wiki page section) (feature request from Willi Lethert)
 30/08/2014 : Added toolbar button (contribution from Xavier Decuyper) and fixed local anchor (bug reported by Andreas Kuzma)
 06/09/2014 : Reffactored to add backlinks support (feature request from Schümmer Hans-Jürgen)
 
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

// Copied and adapted from inc/parser/xhtml.php, function internallink (see RPHACK)
// Should use wl instead (from commons), but this won't do the trick for the name
function dokuwiki_get_link(&$xhtml, $id, $name = NULL, $search=NULL,$returnonly=false,$linktype='content')
{
	global $conf;
	global $ID;
	global $INFO;
	

	$params = '';
	$parts = explode('?', $id, 2);
	if (count($parts) === 2) {
		$id = $parts[0];
		$params = $parts[1];
	}

	// For empty $id we need to know the current $ID
	// We need this check because _simpleTitle needs
	// correct $id and resolve_pageid() use cleanID($id)
	// (some things could be lost)
	if ($id === '') {
		$id = $ID;
	}

	// RPHACK for get_link to work with local links '#id'
	if (substr($id, 0, 1) === '#') {
		$id = $ID . $id;
	}
	// -------
	
	// default name is based on $id as given
	$default = $xhtml->_simpleTitle($id);

	// now first resolve and clean up the $id
	resolve_pageid(getNS($ID),$id,$exists);

	$name = $xhtml->_getLinkTitle($name, $default, $isImage, $id, $linktype);
	if ( !$isImage ) {
		if ( $exists ) {
			$class='wikilink1';
		} else {
			$class='wikilink2';
			$link['rel']='nofollow';
		}
	} else {
		$class='media';
	}

	//keep hash anchor
	list($id,$hash) = explode('#',$id,2);
	if(!empty($hash)) $hash = $xhtml->_headerToLink($hash);

	//prepare for formating
	$link['target'] = $conf['target']['wiki'];
	$link['style']  = '';
	$link['pre']    = '';
	$link['suf']    = '';
	// highlight link to current page
	if ($id == $INFO['id']) {
		$link['pre']    = '<span class="curid">';
		$link['suf']    = '</span>';
	}
	$link['more']   = '';
	$link['class']  = $class;
	$link['url']    = wl($id, $params);
	$link['name']   = $name;
	$link['title']  = $id;
	//add search string
	if($search){
		($conf['userewrite']) ? $link['url'].='?' : $link['url'].='&amp;';
		if(is_array($search)){
			$search = array_map('rawurlencode',$search);
			$link['url'] .= 's[]='.join('&amp;s[]=',$search);
		}else{
			$link['url'] .= 's='.rawurlencode($search);
		}
	}

	//keep hash
	if($hash) $link['url'].='#'.$hash;

	return $link;
	//output formatted
	//if($returnonly){
	//	return $this->_formatLink($link);
	//}else{
	//	$this->doc .= $this->_formatLink($link);
	//}
}


class syntax_plugin_button extends DokuWiki_Syntax_Plugin {

    public $urls = array();
    public $styles = array();
 
    /* replaced bu plugin.info.txt */
    function getInfo(){
      return array(
        'author' => 'RÃ©mi Peyronnet',
        'email'  => 'remi+button@via.ecp.fr',
        'date'   => '2014-06-07',
        'name'   => 'Button Plugin',
        'desc'   => 'Add button links syntax',
        'url'    => 'http://people.via.ecp.fr/~remi/',
      );
    }
 
    function getType() { return 'substition'; }
    function getPType() { return 'normal'; }
    function getSort() { return 250; }  // Internal link is 300
//    function connectTo($mode) { $this->Lexer->addSpecialPattern('\[\[{[^}]*}[^\]]*\]\]',$mode,'plugin_button'); }
    function connectTo($mode) { 
		$this->Lexer->addSpecialPattern('\[\[{conf[^}]*}[^\]]*\]\]',$mode,'plugin_button'); 
		$this->Lexer->addEntryPattern('\[\[{[^}]*}[^\]\|]*\|?',$mode,'plugin_button');
        $this->Lexer->addExitPattern(']]','plugin_button'); 
	}
    function postConnect() { }
	function getAllowedTypes() { return array('formatting','substition'); }

    function handle($match, $state, $pos, &$handler)
    { 
		global $plugin_button_styles;
		global $plugin_button_target;
		
        switch ($state) {
          case DOKU_LEXER_SPECIAL :
          case DOKU_LEXER_ENTER :
                $data = '';
                // Button
                if (preg_match('/\[\[{(?<image>[^}\|]*)\|?(?<css>[^}]*)}(?<link>[^\]\|]*)\|?(?<title>[^\]]*)/', $match, $matches))
                {
					$data = $matches;
                }
				if (is_array($data))
				{
					if ($data['image'] == 'conf.styles')
					{
						$plugin_button_styles[$data['link']] = $data['title'];
					}
					else if ($data['image'] == 'conf.target')
					{
						$plugin_button_target[$data['link']] = $data['title'];
					}
					else
					{
						$data['target'] = "";
						if (is_array($plugin_button_target) && array_key_exists('default',$plugin_button_target))
						{
							$data['target'] = " target='" . $plugin_button_target['default'] . "'";
						}
						if (is_array($plugin_button_target) && array_key_exists($data['css'],$plugin_button_target))
						{
							$data['target'] = " target='" . $plugin_button_target[$data['css']] . "'";
						}
						if ($data['css'] != "")
						{
							if (is_array($plugin_button_styles) && array_key_exists($data['css'],$plugin_button_styles))
							{
								$data['css'] = $plugin_button_styles[$data['css']];
							}
						}
						if (is_array($plugin_button_styles) && array_key_exists('default',$plugin_button_styles) && ($data['css'] != 'default'))
						{
							$data['css'] = $plugin_button_styles['default'] .' ; '. $data['css'];
						}
					}
				}
                return array($state, $data);
 
          case DOKU_LEXER_UNMATCHED :  return array($state, $match); 
          case DOKU_LEXER_ENTRY :          return array($state, '');
          case DOKU_LEXER_EXIT :            return array($state, '');
        }
        return array();
    }
    
    function render($mode, &$renderer, $data) 
    {
		global $plugin_button_styles;
		global $plugin_button_target;

		if($mode == 'xhtml'){
            list($state, $match) = $data;
            switch ($state) {
              case DOKU_LEXER_SPECIAL:
              case DOKU_LEXER_ENTER:
				if (is_array($match))
				{
					$image = $match['image'];
					if (($image != "conf.target") && ($image != "conf.styles"))
					{
						// Test if internal or external link (from handler.php / internallink)
						if (preg_match('#^([a-z0-9\-\.+]+?)://#i',$match['link']))
						{
							// External
							$link['url'] = $match['link'];
							$link['name'] = $match['title'];
							if ($link['name'] == "") $link['name'] = $match['link'];
							$link['class'] = 'urlextern';
						}
						else
						{
							// Internal
							$link = dokuwiki_get_link($renderer, $match['link'], $match['title']);
						}
						$target = $match['target'];
						$link['name'] = str_replace('\\\\','<br />', $link['name']);
						if ($image != '')
						{
							$image =  "<span class='plugin_button_image'><img src='" . ml($image) . "' /></span>";
						}
						$text = "<a $target href='${link['url']}'><span class='plugin_button' style='${match['css']}'>$image<span class='plugin_button_text ${link['class']}'>";
						if (substr($match[0],-1) != "|") $text .= "${link['name']}";
						$renderer->doc .= $text; 
					}
				}
                break;
 
              case DOKU_LEXER_UNMATCHED :  $renderer->doc .= $renderer->_xmlEntities($match); break;
              case DOKU_LEXER_EXIT :       $renderer->doc .= "</span></span></a>"; break;
            }
            return true;
		}
        elseif ($mode=='metadata')
		{
            list($state, $match) = $data;
            switch ($state) {
              case DOKU_LEXER_SPECIAL:
              case DOKU_LEXER_ENTER:
				if (is_array($match))
				{
					/** @var Doku_Renderer_metadata $renderer */
					$renderer->internallink($match['link']);
					// I am assuming that when processing in handle(), you have stored
					// the link destination in $data[0]
					return true;
				}
                break;
              case DOKU_LEXER_UNMATCHED :  break;
              case DOKU_LEXER_EXIT :  break;
            }
            return true;
		}
        return false;
    }
}

?>