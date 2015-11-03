<?php

class Router
{
	public static $routes = array();

	/**
	* Parsage de l'url
	*/
	public static function parse($url, $request)
	{
		$url = trim($url, '/');

		if(empty($url))
			$url = Router::$routes[0]['url'];
		else
		{
			$match = false;

			foreach(Router::$routes as $route)
			{
				if(!$match && preg_match($route['regex'], $url, $match))
				{
					$url = $route['url'];

					// match contient les résultats de la régex
					foreach($match as $key => $value)
					{
						// On remplace :slug, :id, ... par la valeur
						$url = str_replace(':'.$key, $value, $url);
						$url = trim($url, '/');
						$url = str_replace("//", "/", $url);
					}
					
					$match = true;
				}
			}
		}

		$params 				= explode('/', $url);

		$request->controller 	= $params[0];
		$request->action 		= isset($params[1]) ? $params[1] : 'index';
		$request->params		= array_slice($params, 2);

		return true;
	}

	/**
	* Connexion d'une url à une action particulière
	*
	* Utilisation :
	* Router::connect('/theme/:slug',								<- redirection souhaitée
	*	array('controller' => 'pages', 'action' => 'index'),		<- controller et action destinataires
	*	array('slug' => '[a-z0-9\-]+'));							<- paramètres à envoyer
	* 
	* Router::connect('/theme/*', 									<- redirection de tout vers pages/index
	*	array('controller' => 'pages', 'action' => 'index'));
	*
	* Router::connect('/theme/*', 									<- redirection vers les actions correspondantes
	*	array('controller' => 'pages', 'action' => '*'));
	*
	*	$route = array( 'controller' => '',
	*					'action'     => '',
	*					'url'		 => url controller/action/:param1/:param2
	*					'regex'		 => regex complète utilisée correspondant à url
	*					'origin 	 => url à rediriger /test/:param1/:param2
	*					'originregex'=> regex correspondant à origin);
	*/
	public static function connect($redirection, $route, $params = array())
	{
		if(!is_array($route))
			return null;

		$route['origin'] = $redirection;
		
		// On remplace par :args (args : convention regex) si controller/action égale à all
		$route['url'] = str_replace('*', ':args', $route['controller']).'/'.str_replace('*', ':args', $route['action']);

		foreach($params as $key => $value)
			$route['url'] .= '/:'.$key;

		$route['originregex'] = $route['url'];

		// Début création regex, on retire le / au début
		$route['regex'] = trim($redirection, '/');

		// On remplace /* par regex nommée args (args : convention regex) si redirection sur all 
		$route['regex'] = str_replace('*', '(?P<args>/?.*)', $route['regex']);

		foreach($params as $key => $value)
		{
			// Découpage en regex nommées ex : (?P<page>[a-z0-9\-]+)
			$route['regex'] 		= str_replace(":$key", "(?P<$key>$value)", $route['regex']);
			$route['originregex'] 	= str_replace(":$key", "($value)", $route['originregex']);
		}
		
		// Formation d'une regex complète ex : /^(?P<page>[a-z0-9\-]+)\/(?P<id>[0-9]+)$/
		$route['regex'] 			= '/^'.str_replace('/','\/', $route['regex']).'$/';
		$route['originregex'] 		= '/^'.str_replace('/','\/', $route['originregex']).'$/';

		self::$routes[] = $route;
	}

	/**
	* Génère une url redirigée à partir d'informations sur controller/action/params
	* Utilisation :
	* Router::url(array('controller' => '', 'action' => '', 'params' => array('id' => 'value', 'slug' => 'value')));
	*/
	public static function url($route)
	{
		if(!is_array($route))
			return null;

		// Forme une url de la forme controller/action/:param1/:param2
		$url = $route['controller'].'/'.$route['action'];

		if(isset($route['params']))
		{
			foreach($route['params'] as $key => $value)
				$url .= '/'.$value;
		}

		foreach(self::$routes as $route)
		{
			// Analyse de url par rapport aux regex des routes existantes
			if(preg_match($route['originregex'], $url, $match))
			{
				for($i = 1; $i < count($match); $i++)
					$route['origin'] = preg_replace('/(:[A-Za-z0-9]+)/', $match[$i], $route['origin'], 1);
				
				return BASE_URL.$route['origin'];
			}
		}
		
		return null;
	}
}

?>