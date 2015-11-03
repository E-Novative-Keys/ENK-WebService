<?php

class UsersController extends Controller
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->Auth->allow(array('login', 'verify', 'generateToken', 'validateToken'));
	}

	public function login()
	{
		if(!$this->Auth->isLogged())
			$this->set('user', $this->Auth->login());
	}

	public function logout()
	{
		if($this->Auth->isLogged())
			$this->Auth->logout();
	}

	public function verify()
	{
		if(($users = $this->User->find()) == null)
		{
			$data = array(
				"User" => array(
					"email"		=> "admin@enkeys.com",
					"firstname"	=> "Admin",
					"lastname"	=> "ADMIN",
					"password"	=> $this->Auth->password('admin'),
					"role"		=> "admin",
					"validated"	=> 1,
					"created"	=> date('Y-m-d H:i:s'),
					"updated"	=> date('Y-m-d H:i:s')
				)
			);

			$this->User->save($data);
			$this->set("error", "Aucun utilisateur présent en BDD.\nUn nouvel utilisateur a été créé (admin@enkeys.com, pass: admin).");
		}
		else
			die();
	}

	public function listUsers() 
	{
		if($this->Auth->user('role') == "client")
			die();

		if(isset($this->request->data["User"]["role"]))
		{
			$users = $this->User->find(array(
				'conditions' 	=> array('User.role' => $this->request->data["User"]["role"]),
				'order' 		=> 'User.lastname ASC'
			));
		}
		else if(isset($this->request->data['User']['getdev']) && isset($this->request->data['User']['project_id']))
		{
			if(!$this->request->data['User']['getdev'])
				$users = $this->User->find(array(
					'conditions' => array('User.id NOT IN' => '(SELECT user_id FROM users_projects WHERE project_id = '.$this->request->data['User']['project_id'].')')
				));
			else
				$users = $this->User->find(array(
					'conditions' => array('User.id IN' => '(SELECT user_id FROM users_projects WHERE project_id = '.$this->request->data['User']['project_id'].')')
				));
		}
		else 
		{
			$users = $this->User->find(array(
				'order' => 'User.lastname ASC'
			));

			for($i = 0; $i < count($users); $i++)
				$users[$i]->lastlogin = ($users[$i]->lastlogin) ? date('d-m-Y H:i:s', strtotime($users[$i]->lastlogin)) : "";
		}	
		
		$this->set(compact('users'));
	}

	public function listUsersProject()
	{
		if($this->Auth->user('role') == "client")
			die();

		if(isset($this->request->data["UsersProject"]["project_id"]))
		{
			$usersProject = $this->UsersProject->find(array(
				'fields' => 'user_id',
				'conditions' => array(
					'project_id' => $this->request->data["UsersProject"]["project_id"]
				)
			));
		}
	}

	public function addUser()
	{	
		if($this->Auth->user('role') == "client")
			die();

		if(isset($this->request->data['User']['password']))
			$this->request->data['User']['password'] = $this->Auth->password(
				str_replace(md5(JAVA_SALT), '', str_replace(md5(JAVA_PEPPER), '',
				base64_decode($this->request->data['User']['password']))),
				false
			);
		
		if($this->User->validates($this->request->data))
		{
			// Si bien ajouté, on renvoie la liste
			if(($state = $this->User->save($this->request->data)))
			{
				$users = $this->User->find(array(
					'order' => 'lastname ASC'
				));

				$this->set(compact('users'));
			}
			// Sinon erreur
			else
				$this->set(compact('state'));
		}
		else
			$this->set('error', $this->Form->errors);
	}

	public function editUser()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['User']['id']) || empty($this->request->data['User']['id'])
		|| $this->Auth->user('role') == "client")
			die();

		if($this->User->validates($this->request->data))
		{	
			if(isset($this->request->data['User']['password']))
				$this->request->data['User']['password'] = $this->Auth->password(
					str_replace(md5(JAVA_SALT), '', str_replace(md5(JAVA_PEPPER), '',
					base64_decode($this->request->data['User']['password']))),
					false
				);

			if(isset($this->request->data['User']['token_email']))
				$this->request->data['User']['token_email'] = "";
		
			// Si bien edité, on renvoie toute la liste
			if(($state = $this->User->save($this->request->data)))
			{
				$users = $this->User->find(array(
					'order' => 'lastname ASC'
				));

				$this->set(compact('users'));
			}
			// Sinon erreur
			else
				$this->set(compact('state'));
		}
		else
			$this->set('error', $this->Form->errors);
	}

	public function updateUser()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['User']['id']) 		|| empty($this->request->data['User']['id'])
		|| !isset($this->request->data['User']['oldpass']) 	|| empty($this->request->data['User']['oldpass'])
		|| !isset($this->request->data['User']['password']) || empty($this->request->data['User']['password']))
			die();

		$user = $this->User->findFirst(array(
			'conditions' => array('id' => $this->request->data['User']['id'])
		));

		$this->request->data['User']['oldpass'] = $this->Auth->password(
			str_replace(md5(JAVA_SALT), '', str_replace(md5(JAVA_PEPPER), '',
			base64_decode($this->request->data['User']['oldpass']))),
			false
		);

		$this->request->data['User']['password'] = base64_encode(
			str_replace(md5(JAVA_SALT), '', str_replace(md5(JAVA_PEPPER), '',
			base64_decode($this->request->data['User']['password'])))
		);

		if($user != null && $user->password == $this->request->data['User']['oldpass'])
		{
			unset($this->request->data['User']['oldpass']);
			$this->request->data['User']['firstname'] 	= $user->firstname;
			$this->request->data['User']['lastname'] 	= $user->lastname;
			$this->request->data['User']['email'] 		= $user->email;
			$this->request->data['User']['role'] 		= $user->role;

			$this->editUser();
		}
		else
			$this->set('error', 'Invalid old password');
	}

	public function deleteUser()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['User']['id']) || empty($this->request->data['User']['id'])
		|| $this->Auth->user('role') == "client")
			die();
		
		// Si bien supprimé, on renvoie toute la liste
		if(($state = $this->User->delete($this->request->data['User']['id'])))
		{
			$users = $this->User->find(array(
				'order' => 'lastname ASC'
			));

			$this->set(compact('users'));
		}
		// Sinon erreur
		else
			$this->set(compact('state'));
	}

	public function generateToken()
	{
		// Si l'utilisateur est un client 
		if(isset($this->request->data['Client']['email']))
		{
			$this->loadModel('Client');

			$user = $this->Client->findFirstIni(array(
				'conditions' => array('email' => $this->request->data['Client']['email'])
			));

			if($user)
			{
				$this->request->data['Client']['id'] 			= $user['id'];
				$this->request->data['Client']['token_email'] 	= $this->Auth->password($_SERVER['REMOTE_ADDR'].date('Y-m-d H:i:s'));

				$this->Client->saveIni($this->request->data);

				$token = $this->request->data['Client']['token_email'];

				if(empty($user['password']))
				{
					$body 	 = 'Vous venez de débuter votre premier projet chez E-Novative Keys et nous vous en remercions !<br /><br />';
					$body 	.= 'De manière à pouvoir participer au mieux à l\'évolution de votre projet, nous vous invitons à activer aujourd\'hui votre compte ENK-Cloud.<br />';
					$body 	.= 'Rendez-vous <a href="http://enkcloud.com/config/'.$token.'">sur ce lien</a> pour créer votre mot de passe.';
					$body	.= 'Vous pourrez ensuite vous connecter à votre espace professionnel ENK-Cloud disponible à l\'adresse <a href="http://enkcloud.com/">www.enkcloud.com</a>.<br /><br />';
					$body 	.= 'Vous remerciant pour votre confiance,<br />L\'équipe E-Novative Keys.';
				}
				else
				{
					$body  	 = 'Une requête de modification de mot de passe a été effectuée pour votre compte ENK-Cloud.<br />';
					$body 	.= 'Rendez-vous <a href="http://enkcloud.com/config/'.$token.'">sur ce lien</a> pour modifier votre mot de passe.<br /><br/>';
					$body 	.= 'Si vous n\'êtes pas à l\'origine de cette requête, vous pouvez ignorer ce message.<br />L\'équipe E-Novative Keys.';
				}

				$this->Mailer->send(
					array($this->request->data['Client']['email'] => $this->request->data['Client']['email']),
					"Compte ENK-Cloud",
					$body
				);

				$this->set(compact('token'));
			}
			else
				die();
		}
		// Si l'utilisateur est un employé
		else if(isset($this->request->data['User']['email']))
		{
			$user = $this->User->findFirst(array(
				'conditions' => array('email' => $this->request->data['User']['email'])
			));

			if($user)
			{
				$this->request->data['User']['id'] 			= $user->id;
				$this->request->data['User']['firstname'] 	= $user->firstname;
				$this->request->data['User']['lastname'] 	= $user->lastname;
				$this->request->data['User']['role'] 		= $user->role;
				$this->request->data['User']['token_email'] = strtoupper(substr($this->Auth->password($_SERVER['REMOTE_ADDR'].date('Y-m-d H:i:s')), 0, 5));

				$this->User->save($this->request->data);

				$token = $this->request->data['User']['token_email'];

				$body  = 'Une requête de modification de mot de passe a été effectuée pour votre compte ENK-Projects.<br />';
				$body .= 'Veuillez saisir la clé de sécurité suivante dans l\'application de manière à pouvoir modifier votre mot de passe : <strong>'.$token.'</strong><br /><br />';
				$body .= 'Si vous n\'êtes pas à l\'origine de cette requête, vous pouvez ignorer ce message.<br />L\'équipe E-Novative Keys.';

				$this->Mailer->send(
					array($user->firstname.' '.$user->lastname => $this->request->data['User']['email']),
					"Compte ENK-Projects",
					$body
				);
				$this->set(compact('token'));
			}
			else
				die();
		}
		else
			die();
	}

	public function validateToken()
	{
		// Si l'utilisateur est un client
		if(isset($this->request->data['Client']['token']))
		{
			$this->loadModel('Client');

			if(($user = $this->Client->findFirstIni(array(
				'conditions' => array('token_email' => $this->request->data['Client']['token'])
			))) != null)
			{
				$token = $this->Auth->password($_SERVER['REMOTE_ADDR'].date('Y-m-d H:i:s'));
				
				$this->Client->saveIni(array('Client' => array('id' => $user['id'], 'token' => $token)));
				
				$user['token'] = $token;
				$this->set(compact("user"));
			}
			else
				die();
		}
		// Si l'utilisateur est un employé
		else if(isset($this->request->data['User']['token']))
		{
			if(!isset($this->request->data['User']['token'])
			|| !isset($this->request->data['User']['email'])
			|| !isset($this->request->data['User']['password']))
				die();

			if(($user = $this->User->findFirst(array(
				'conditions' => array(
					'User.email' 		=> $this->request->data['User']['email'],
					'User.token_email' 	=> $this->request->data['User']['token']
				)
			))) != null)
			{
				$token = $this->Auth->password($_SERVER['REMOTE_ADDR'].date('Y-m-d H:i:s'));
				
				$this->User->save(array('User' => array('id' => $user->id, 'token' => $token)));
				
				$user->token = $token;
				$this->set(compact("user"));
			}
			else
				die();
		}
	}
}

?>