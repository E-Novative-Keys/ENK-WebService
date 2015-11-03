<?php

class ClientsController extends Controller
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->Auth->authorized(array('admin', 'leaddev', 'developer', 'trainee', 'employee'));
	}

	public function listClients()
	{
		$clients = $this->Client->findIni(array(
			'order' => 'lastname ASC'
		));

		$this->set(compact('clients'));
	}

	public function addClient()
	{
		if($this->Client->validates($this->request->data))
		{
			$this->request->data['Client']['lastname'] 		= strtoupper($this->request->data['Client']['lastname']);
			$this->request->data['Client']['firstname'] 	= ucfirst($this->request->data['Client']['firstname']);
			$this->request->data['Client']['password'] 		= "";
			$this->request->data['Client']['validated'] 	= 1;
			$this->request->data['Client']['lastip'] 		= "";
			$this->request->data['Client']['lastlogin'] 	= "";
			$this->request->data['Client']['cloud_enabled'] = "0";
			$this->request->data['Client']['token'] 		= "";
			$this->request->data['Client']['token_email'] 	= "";
			$this->request->data['Client']['created'] 		= date('Y-m-d H:i:s');
			$this->request->data['Client']['updated'] 		= date('Y-m-d H:i:s');

			// Si bien ajouté, on renvoie toute la liste
			if(($state = $this->Client->saveIni($this->request->data)))
			{
				$clients = $this->Client->findIni(array(
					'order' => 'lastname ASC'
				));

				$newClient = $this->Client->findFirstIni(array(
					'conditions' => array(
						'firstname'		=> $this->request->data['Client']['firstname'],
						'lastname' 		=> $this->request->data['Client']['lastname'],
						'email'	 		=> $this->request->data['Client']['email']
					)
				));

				$this->set(compact('clients', 'newClient'));
			}
			// Sinon erreur
			else 			
				$this->set(compact('state'));
		}
		else
			$this->set('error', $this->Form->errors);
	}

	public function updateClient()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Client']['id']) 		|| empty($this->request->data['Client']['id'])
		|| !isset($this->request->data['Client']['oldpass']) 	|| empty($this->request->data['Client']['oldpass'])
		|| !isset($this->request->data['Client']['password']) 	|| empty($this->request->data['Client']['password']))
			die();

		$client = $this->Client->findFirstIni(array(
			'conditions' => array('id' => $this->request->data['Client']['id'])
		));

		$this->request->data['Client']['oldpass'] = $this->Auth->password(
			str_replace(md5(JAVA_SALT), '', str_replace(md5(JAVA_PEPPER), '',
			base64_decode($this->request->data['Client']['oldpass']))),
			false
		);

		$this->request->data['Client']['password'] = base64_encode(
			str_replace(md5(JAVA_SALT), '', str_replace(md5(JAVA_PEPPER), '',
			base64_decode($this->request->data['Client']['password'])))
		);

		// Après les vérifications de mots de passe on appelle la méthode editClient
		if($client != null && $client['password'] == $this->request->data['Client']['oldpass'])
		{
			unset($this->request->data['Client']['oldpass']);
			$this->editClient();
		}
		else
			$this->set('error', 'Invalid old password');
	}

	public function editClient()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Client']['id']) || empty($this->request->data['Client']['id']))
			die();

		if($this->Client->validates($this->request->data))
		{
			if(isset($this->request->data['Client']['password']))
				$this->request->data['Client']['password'] = $this->Auth->password(
					base64_decode($this->request->data['Client']['password']),
					false
				);

			if(isset($this->request->data['Client']['token_email']))
				$this->request->data['Client']['token_email'] = "";

			$this->request->data['Client']['updated'] = date('Y-m-d H:i:s');
		
			// Si bien edité, on renvoie toute la liste
			if(($state = $this->Client->saveIni($this->request->data)))
			{
				$clients = $this->Client->findIni(array(
					'order' => 'lastname ASC'
				));
				$this->set(compact('clients'));
			}
			// Sinon erreur
			else 			
				$this->set(compact('state'));
		}
		else 
			$this->set('error', $this->Form->errors);
	}

	public function deleteClient()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Client']['id']) || empty($this->request->data['Client']['id']))
			die();
		
		$this->loadModel('Project');

		$projects = $this->Project->find(array(
			'fields' 		=> 'Project.id',
			'conditions' 	=> array('Project.client_id' => $this->request->data['Client']['id'])
		));

		// Suppression des projets liés à ce client
		if($projects != null) 
			for($i = 0; $i < count($projects); $i++)
				$this->Project->delete($projects[$i]->id);

		// Si le client a bien été supprimé, on renvoie toute la liste de tous les clients
		if(($state = $this->Client->deleteIni($this->request->data['Client']['id'])) !== false)
		{
			$path = FILES_ROOT.'Cloud'.DS.$this->request->data['Client']['id'];

			// Suppression des dossiers (dossiers projets Cloud du client)
			if(file_exists($path)) 
			{
				if($this->Utils->recursiveRmdir($path) === false)
					$this->set('error', "Cannot remove user $id 's projects");
				else
					$this->set('userprojects', 'deleted');		
			}

			$clients = $this->Client->findIni(array(
				'order' => 'lastname ASC'
			));

			$this->set(compact('clients'));
		}
		// Sinon erreur
		else		
			$this->set(compact('state'));
	}
}

?>