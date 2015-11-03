<?php

class Html
{
	public static $meta_for_layout 	= array();
	public static $css_for_layout 	= array();
	public static $js_for_layout 	= array();

	protected $tags = array(
		'meta' 				=> '<meta %s/>',
		'metalink' 			=> '<link href="%s" %s/>',
		'link' 				=> '<a href="%s" %s>%s</a>',
		'charset' 			=> '<meta http-equiv="Content-Type" content="text/html; charset=%s" />',
		'image' 			=> '<img src="%s" %s/>',
		'css' 				=> '<link rel="%s" type="text/css" href="%s" />',
		'style' 			=> '<style type="text/css" %s>%s</style>',
		'javascriptblock' 	=> '<script type="text/javascript">%s</script>',
		'javascriptstart' 	=> '<script>',
		'javascriptlink' 	=> '<script type="text/javascript" src="%s"></script>',
		'javascriptend' 	=> '</script>'
	);

	protected $docTypes = array(
		'html4-strict' 		=> '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
		'html4-trans' 		=> '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
		'html4-frame' 		=> '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
		'html5' 			=> '<!DOCTYPE html>',
		'xhtml-strict' 		=> '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
		'xhtml-trans' 		=> '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
		'xhtml-frame' 		=> '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
		'xhtml11' 			=> '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">'
	);

	public function docType($type = 'html5')
	{
		if(isset($this->docTypes[$type]))
			return $this->docTypes[$type];

		return null;
	}

	public function charset($charset = null)
	{
		return sprintf($this->tags['charset'], (!empty($charset) ? $charset : 'utf-8'));
	}

	/**
	* Utilisation :
	* echo $this->Html->meta('type', 'content/url');
	* Apparait lors de l'affichage de $meta_for_layout
	*/
	public function meta($type, $content = null)
	{
		if(!is_array($type))
		{
			$meta = null;

			$types = array(
				'rss' 			=> array('type' => 'application/rss+xml', 'rel' => 'alternate', 'title' => $type, 'link' => $content),
				'atom' 			=> array('type' => 'application/atom+xml', 'title' => $type, 'link' => $content),
				'icon' 			=> array('type' => 'image/x-icon', 'rel' => 'icon', 'link' => $content),
				'keywords' 		=> array('name' => 'keywords', 'content' => $content),
				'description' 	=> array('name' => 'description', 'content' => $content),
			);

			if($type === 'icon' && $content === null)
				$types['icon']['link'] = 'favicon.ico';

			if(isset($types[$type]))
				$type = $types[$type];

			if(isset($type['link']))
			{
				$params = 'type="'.$type['type'].'"'.((isset($type['title'])) ? ' title="'.$type['title'].'"' : '').((isset($type['rel'])) ? ' rel="'.$type['rel'].'"' : '');
				array_push(self::$meta_for_layout, sprintf($this->tags['metalink'], '/'.$type['link'], $params));
			}
			else
			{
				$params = 'name="'.((isset($type['name'])) ? $type['name'] : $type).'" content="'.((isset($type['content'])) ? $type['content'] : $content).'"';
				array_push(self::$meta_for_layout, sprintf($this->tags['meta'], $params));
			}
		}
		else
			return null;
	}
	
	/**
	* Utilisation :
	* echo $this->Html->css('style'); Ajout dans le layout
	* echo $this->Html->css('style', true); Ajout dans la vue
	* echo $this->Html->css(array('style1', 'style2'));
	* echo $this->Html->css(array('style1', 'style2'), true);
	*/
	public function css($paths, $inline = null)
	{
		if($paths === null)
			return null;

		if(!is_array($paths))
		{
			$tmp = $paths;
			$paths = array();
			array_push($paths, $tmp);
		}

		$css = null;

		foreach($paths as $path)
		{
			if(!strstr($path, '.css'))
				$path = $path.'.css';

			if(file_exists(CSS_ROOT.$path))
			{
				$path = CSS.$path;

				if($inline)
					$css .= sprintf($this->tags['css'], 'stylesheet', $path);
				else
					array_push(self::$css_for_layout, sprintf($this->tags['css'], 'stylesheet', $path));
			}
		}

		if($inline)
			return $css;
	}

	/**
	* Utilisation :
	* $this->Html->scriptStart();
	* function js(){ ... }
	* echo $this->Html->scriptEnd(true); pour l'affichage en direct dans la vue ou
	* echo $this->Html->scriptEnd(); pour un ajout dans le stack $js_for_layout
	*/
	public function scriptStart()
	{
		ob_start();
		return null;
	}

	public function scriptEnd($inline = null)
	{
		$buffer = ob_get_clean();

		$script = sprintf($this->tags['javascriptblock'], $buffer);
		
		if($inline)
			return $script;
		else
		{
			array_push(self::$js_for_layout, $script);
			return null;
		}
	}

	/**
	* Utilisation :
	* echo $this->Html->script('script'); Ajout dans le layout
	* echo $this->Html->script('script', true); Ajout dans la vue 
	* echo $this->Html->script(array('script1', 'script2'), true);
	* echo $this->Html->script(array('script1', 'script2'));
	*/
	public function script($paths, $inline = null)
	{
		if($paths === null)
			return null;

		if(!is_array($paths))
		{
			$tmp = $paths;
			$paths = array();
			array_push($paths, $tmp);
		}

		$js = null;

		foreach($paths as $path)
		{
			
			if(!strstr($path, '.js'))
				$path = $path.'.js';

			if(file_exists(JS_ROOT.$path))
			{
				$path = JS.$path;

				if($inline)
					$js .= sprintf($this->tags['javascriptlink'], $path);
				else
					array_push(self::$js_for_layout, sprintf($this->tags['javascriptlink'], $path));
			}
		}

		if($inline)
			return $js;
	}

	/**
	* Utilisation :
	* echo $this->Html->image('path/image.png', array('alt' => 'value', 'url' => 'value', 'id' => 'value', ...));
	* echo $this->Html->image('path/image.png', array('alt' => 'value', 'url' => array('controller' => 'value', 'action' => 'value'), 'id' => 'value', ...));
	* Si url : affichage de l'image dans une balise <a href="url"></a>
	* Path de l'image à partir du dossier webroot/img
	*/
	public function image($path, $options = array())
	{
		if($path == null)
			return null;

		if(file_exists(IMG_ROOT.$path))
		{
			$path = IMG.$path;

			if(!isset($options['alt']))
				$options['alt'] = '';

			$url = false;
			if(!empty($options['url']))
			{
				$url = $options['url'];
				unset($options['url']);
			}
			
			$attributes = null;
			foreach($options as $key => $value)
				$attributes .= $key.'="'.$value.'" ';

			$image = sprintf($this->tags['image'], $path, $attributes);

			if($url)
				return sprintf($this->tags['link'], (!is_array($url) ? $url : $this->url($url)), null, $image);
			
			return $image;
		}
		else
			return null;
	}

	/**
	* Utilisation :
	* echo $this->Html->link("Titre", 'url', array('id' => 'value', ...), 'confirmMessage');
	* echo $this->Html->link("Titre", array('controller' => 'value', 'action' => 'value', 'slug' => 'value'), array('id' => 'value', ...));
	* Afficher Message Javascript : confirmMessage
	*/
	public function link($title, $url = null, $options = array(), $confirmMessage = false)
	{
		$url = ($url !== null) ? ((is_array($url)) ? $this->url($url) : $url) : $title;

		$attributes = null;
		foreach($options as $key => $value)
			$attributes .= $key.'="'.$value.'" ';
		
		if($confirmMessage)
			$attributes .= 'onclick="return confirm(\''.$confirmMessage.'\');"';

		return sprintf($this->tags['link'], $url, $attributes, $title);
	}

	/**
	* Affiche un élement contenu dans le dossier Elements (menu, topbar, footer, ...)
	* Utilisation :
	* echo $this->Html->element('filename');
	*/
	public function element($file)
	{
		if(!strstr($file, '.php'))
			$file = $file.'.php';

		if(!file_exists(ELEMENTS.$file))
			return null;

		ob_start();
		require_once(ELEMENTS.$file);
		$element = ob_get_clean();

		return $element;
	}

	public function url($url)
	{
		return Router::url($url);
	}

	/**
	* Affichage des liens meta, css, js pour le layout
	*/
	public function layout($element)
	{
		switch($element)
		{
			case 'meta':
				for($i = 0; $i < count(Html::$meta_for_layout); $i++)
					echo Html::$meta_for_layout[$i];
				break;

			case 'css':
				for($i = 0; $i < count(Html::$css_for_layout); $i++)
					echo Html::$css_for_layout[$i];
				break;

			case 'js':
				for($i = 0; $i < count(Html::$js_for_layout); $i++)
					echo Html::$js_for_layout[$i];
				break;
		}
	}
}

?>