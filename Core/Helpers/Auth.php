<?php

class Auth
{
	private $controller = null;
	public $deny 		= false;
	public $user 		= null;

	public function __construct($controller)
	{
		$this->controller = $controller;
	}

	/**
	* Récupération des informations de Session de l'utilisateur
	* Utilisation :
	* $this->Auth->user();
	* $this->Auth->user('role');
	*/
	public function user($attribute = null)
	{
		if(!$attribute)
			return ($this->user) ? $this->user : false;
		else
			return (isset($this->user[$attribute])) ? $this->user[$attribute] : false;
	}

	/**
	* Autorisation d'accès aux actions sélectionnées ou à toutes les actions d'un controller donné si l'utilisateur n'est pas connecté
	* $this->Auth->allow(); <- autorise toutes les actions du controller
	* $this->Auth->allow('action1');
	* $this->Auth->allow(array('action1', 'action2'));
	*/
	public function allow($actions = array())
	{
		if(AUTH_ENABLE)
		{
			if(!is_array($actions))
			{
				$tmp 		= $actions;
				$actions 	= array();
				array_push($actions, $tmp);
			}

			if(empty($actions))
				$this->deny = false;
			else
			{
				if(in_array($this->controller->request->action, $actions))
					$this->deny = false;
			}
		}
	}

	/**
	* Interdiction d'accès aux actions sélectionnées ou à toutes les actions d'un controller donné si l'utilisateur n'est pas connecté
	* Utilisation :
	* $this->Auth->deny(); <- interdit toutes les actions du controller
	* $this->Auth->deny('action1');
	* $this->Auth->deny(array('action1', 'action2'));
	*/
	public function deny($actions = array())
	{
		if(AUTH_ENABLE)
		{
			if(!is_array($actions))
			{
				$tmp 		= $actions;
				$actions 	= array();
				array_push($actions, $tmp);
			}

			if(empty($actions))
			{
				if(!$this->isLogged())
					$this->deny = true;
			}
			else
			{
				if(in_array($this->controller->request->action, $actions) && !$this->isLogged())
					$this->deny = true;
			}
		}
	}

	/**
	* Vérification de l'état de connexion
	*/
	public function isLogged()
	{
		return (!empty($this->user)) ? true : false;
	}

	/**
	* Vérification de l'autorisation d'accès à un controller par le rôle de l'utilisateur connecté
	* Utilisation :
	* $this->Auth->authorized('admin');
	* $this->Auth->authorized(array('admin', 'member'))
	*/
	public function authorized($roles = array())
	{
		if(AUTH_ENABLE && $this->isLogged())
		{
			for($i = 0; $i < count($roles); $i++)
			{
				if($this->user('role') == $roles[$i])
					$this->deny = false;
			}
		}
	}

	/**
	* Vérification des informations de connexion saisies en POST
	* Champs analysés : loginField (par défaut email) et password, l'utilisateur, s'il existe en BDD doit être validé
	* Mise en Session d'informations si les informations de connexion sont valides
	*/
	public function login($loginField = 'email')
	{
		$client 	= false;

		$login 		= (isset($this->controller->request->data['User'][$loginField]))
						? $this->controller->request->data['User'][$loginField]
						: null;
		$password 	= (isset($this->controller->request->data['User']['password']))
						? $this->password(str_replace(md5(JAVA_SALT), '',
										   str_replace(md5(JAVA_PEPPER), '', base64_decode($this->controller->request->data['User']['password']))), 
						  false)
						: null;

		

		if(!isset($this->controller->User))
			$this->controller->loadModel('User');

		if(!isset($this->controller->Client))
			$this->controller->loadModel('Client');

		$user = $this->controller->User->findFirst(array(
			'conditions' 	=> array(
				'User.'.$loginField => $login,
				'User.password' 	=> $password,
				'User.validated' 	=> 1
			)),
			PDO::FETCH_ASSOC
		);

		if(!$user)
		{
			$user = $this->controller->Client->findFirstIni(array(
				'conditions'	=> array(
					$loginField => $login,
					'password'	=> $password,
					'validated'	=> 1
				))
			);
			$client = true;
		}
		
		if(!$user || ($client && !$user["cloud_enabled"]))
			return false;
		else
		{
			$user['token']		= $this->password($_SERVER['REMOTE_ADDR'].date('Y-m-d H:i:s'));
			$lastlogin 			= date('Y-m-d H:i:s');
			$lastip 			= $_SERVER['REMOTE_ADDR'];

			$data = array(
				(!$client) ? 'User' : 'Client' => array(
					'id' 		=> $user['id'],
					'token' 	=> $user['token'],
					'lastlogin' => $lastlogin,
					'lastip' 	=> $lastip
				)
			);

			if(!$client)
				$this->controller->User->save($data);
			else
			{
				$user['role'] = 'client';
				$this->controller->Client->saveIni($data);
			}

			return $user;
		}
	}

	/**
	* Vérification du token envoyé en POST
	* Remplace le système de Session pour garder une connexion
	* entre les applis JAVA/WEB et le WebService
	*/
	public function authorizedByToken($field = null, $token = null, $loginField = 'email')
	{
		if(!isset($this->controller->User))
			$this->controller->loadModel('User');

		if(!isset($this->controller->Client))
			$this->controller->loadModel('Client');

		$client = false;

		$user = $this->controller->User->findFirst(array(
			'conditions' 	=> array(
				'User.'.$loginField => $field,
				'User.validated' 	=> 1
			)),
			PDO::FETCH_ASSOC
		);

		if(!$user)
		{
			$user = $this->controller->Client->findFirstIni(array(
				'conditions'	=> array(
					$loginField => $field,
					'validated'	=> 1
				))
			);
			$client = true;
		}

		if(!$user)
			return null;
		else
		{
			if($user['token'] == $token)
			{
				unset($this->controller->request->data['Token']);

				if($client)
					$user['role'] = 'client';
				
				return $user;
			}
			else
				return null;
		}

	}

	/**
	* Déconnexion de l'utilisateur
	* Suppression du token en base de données
	*/
	public function logout()
	{
		if($this->isLogged())
		{
			if(!isset($this->controller->User))
				$this->controller->loadModel('User');

			if(!isset($this->controller->Client))
				$this->controller->loadModel('Client');

			
			if($this->user('role') != 'client')
			{
				$data = array(
					'User' => array(
						'id' 	=> $this->user('id'),
						'token' => null
					)
				);
				$this->controller->User->save($data);
			}
			else
			{
				$data = array(
					'Client' => array(
						'id' 	=> $this->user('id'),
						'token' => ""
					)
				);
				$this->controller->Client->saveIni($data);
			}
		}
	}

	/**
	* Génération d'un password
	*/
	public function password($value, $flag = true)
	{
		if($flag)
			return sha1(md5(SALT).md5($value).md5(PEPPER));
		else
			return sha1(md5(SALT).$value.md5(PEPPER));
	}
}

?>