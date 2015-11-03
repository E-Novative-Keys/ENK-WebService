<?php

class Session
{
	public $controller = null;

	public function __construct($controller)
	{
		if(!isset($_SESSION))
			session_start();

		$this->controller = $controller;
	}

	/**
	* Ajout d'un message en variable de Session
	*/
	public function setFlash($message, $type = 'success')
	{
		$_SESSION['flash'] = array(
			'message' 	=> $message,
			'type'		=> $type
		);
	}

	/**
	* Affichage du message sur la page à partir d'un layout
	*/
	public function flash()
	{
		if(isset($_SESSION['flash']['message']))
		{
			$content = $this->controller->Html->element($_SESSION['flash']['type']);
			
			$_SESSION['flash'] = array(); 
			
			return $content; 
		}
	}

	/**
	* Ecriture d'une variable de Session
	* A revoir pour accès à plusieurs niveaux
	*/
	public function write($key, $value)
	{
		$key = explode('.', $key);

		if(count($key) > 1)
			$_SESSION[$key[0]][$key[1]] = $value;
		else
			$_SESSION[$key[0]] = $value;
	}

	/**
	* Lecture d'une variable de Session
	* A revoir pour accès à plusieurs niveaux
	*/
	public function read($key = null)
	{
		if($key)
		{
			$key = explode('.', $key);

			if(count($key) > 1)
			{
				if(isset($_SESSION[$key[0]][$key[1]]))
					return $_SESSION[$key[0]][$key[1]];
				else
					return false;
			}
			else
			{
				if(isset($_SESSION[$key[0]]))
					return $_SESSION[$key[0]];
				else
					return false;
			}
		}
		else
			return $_SESSION; 
	}
}

?>