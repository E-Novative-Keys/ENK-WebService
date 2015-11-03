<?php
/**
* Stocke l'url souhaitée par l'utilisateur ainsi que les données envoyées en POST si elles existent
*/
class Request
{
	public $url		= null;
	public $data	= false;
	public $files	= false;

	public function __construct()
	{
		$this->url 	= isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '/';

		if(!empty($_POST))
		{	
			if(isset($_POST['data']) && $this->isJSON($_POST['data']))
				$data = json_decode($_POST['data'], true);
			else
				$data = $_POST;

			$this->data = new stdClass();

			foreach($data as $value)
				$this->data = $value;
		}
		if(!empty($_FILES))
		{
			$this->files = new stdClass();

			foreach($_FILES as $value)
				$this->files = $value;
		}

		// Headers d'autorisation pour les requêtes Ajax
		header("Access-Control-Allow-Origin: 	http://enkcloud.com");
		header("Access-Control-Allow-Methods: 	POST");
	}

	protected function isJSON($string)
	{
		return is_string($string) && is_array(json_decode($string, true)) ? true : false;
	}
}

?>