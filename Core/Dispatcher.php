<?php
/**
* Dispatcher
*/
class Dispatcher
{
	public $request = null;

	public function __construct()
	{
		// Obtention des informations url, data envoyées en POST, controller, action, params
		$this->request = new Request();
		Router::parse($this->request->url, $this->request);

		// Chargement du controlleur concerné
		$controller = $this->loadController();
		
		$action 	= $this->request->action;


		// Recherche de la méthode $action dans l'ensemble des méthodes présentes dans les classes $controller (ex: PagesController) et Controller
		if(!in_array($action, array_diff(get_class_methods($controller), get_class_methods('Controller'))))
		{
			$error = new Controller($this->request);
			$error->notFound('Action : '.$action.' non trouvée dans le controlleur '.$this->request->controller);
		}

		// Appel de la méthode $action se trouvant dans la classe $controller
		call_user_func_array(array($controller, $action), $this->request->params);
		
		// Affichage de la vue
		$controller->render($action);
	}

	/**
	* Charge le controller en fonction de la requête utilisateur
	*/
	public function loadController()
	{
		$name = ucfirst($this->request->controller).'Controller'; 
		$file = CONTROLLERS.DS.$name.'.php';
		
		if(!file_exists($file))
		{
			$error = new Controller($this->request);
			$error->notFound('Controlleur '.$this->request->controller.' introuvable !');
		}
		
		require_once($file);

		// ex : $controller = new PagesController($this->request);
		$controller = new $name($this->request); 
		
		return $controller;  
	}
}

?>