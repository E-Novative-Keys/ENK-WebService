<?php

class ProjectsController extends Controller
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->Auth->authorized(array('admin', 'leaddev', 'developer', 'trainee', 'employee'));
	}

	public function listProjects()
	{
		$this->loadModel('Client');

		// On liste les projets appartenant à un client précis
		if($this->Auth->user('role') == 'client')
		{
			$projects = $this->Project->findList(array(
				'conditions' 	=> array('Project.client_id' => $this->Auth->user('id')),
				'order' 		=> 'created ASC'
			));
		}
		// On liste tous les projets en affichant les clients correspondant pour chacun des projets
		else
		{
			$projects = $this->Project->find(array(
				'order' 		=> 'name ASC'
			));

			for($i = 0; $i < count($projects); $i++)
			{
				$nameQuery = $this->Client->findFirstIni(array(
					'fields' 		=> array('lastname', 'firstname'),
					'conditions' 	=> array('id' => $projects[$i]->client_id)
				));

				$projects[$i]->client_name 	= $nameQuery["lastname"]." ".$nameQuery["firstname"];
				$projects[$i]->deadline 	= date('d-m-Y H:i:s', strtotime($projects[$i]->deadline));
				$projects[$i]->created 		= date('d-m-Y H:i:s', strtotime($projects[$i]->created));
			}
		}

		$this->set(compact('projects'));
	}

	public function addProject()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Project']['client_name']) 	|| empty($this->request->data['Project']['client_name'])
		|| !isset($this->request->data['Project']['lead_name']) 	|| empty($this->request->data['Project']['lead_name']))
			die();

		// Validation des données directement liées au model
		if($this->Project->validates($this->request->data))
		{
			$this->loadModel('Client');
			$this->loadModel('User');
			$this->loadModel('UsersProject');

			$clientName = explode(' ', $this->request->data['Project']['client_name']);
			$leadName 	= explode(' ', $this->request->data['Project']['lead_name']);
			unset($this->request->data['Project']['client_name']);
			unset($this->request->data['Project']['lead_name']);

			$client = $this->Client->findFirstIni(array(
				'conditions' => array(
					'firstname' => $clientName[0],
					'lastname' 	=> $clientName[1]
				)
			));

			$this->request->data['Project']['created'] 		= date('Y-m-d H:i:s');
			$this->request->data['Project']['updated'] 		= date('Y-m-d H:i:s');
			$this->request->data['Project']['client_id'] 	= $client['id'];
			$this->request->data['Project']['lead_id'] 		= $this->User->findFirst(array(
				'fields' 		=> 'id',
				'conditions' 	=> array(
					'User.firstname' 	=> $leadName[0],
					'User.lastname' 	=> $leadName[1]
				)	
			))->id;

			// Sauvegarde du nouveau projet
			if(($state = $this->Project->save($this->request->data)))
			{
				$currentProject = $this->Project->findFirst(array(
					'conditions' 	=> array(
						'Project.client_id' => $this->request->data['Project']['client_id'],
						'Project.lead_id' 	=> $this->request->data['Project']['lead_id'],
						'Project.name' 		=> $this->request->data['Project']['name']
					)
				));

				// Création des répertoires pour le cloud
				$path = FILES_ROOT.'Cloud'.DS.$this->request->data['Project']['client_id'].DS.$currentProject->id.DS;
				
				mkdir($path."client", 	0777, true);
				mkdir($path."dev", 		0777, true);

				// Sauvegarde Liaison Lead dev - nouveau projet
				$this->UsersProject->save(array(
					'users_project' => array(
						'user_id' 		=> $this->request->data['Project']['lead_id'],
						'project_id' 	=> $currentProject->id
				)));

				// Récupération de la nouvelle liste de projets
				$projects = $this->Project->find(array('order' => 'name ASC'));

				unset($this->request->data['Project']);
				$this->request->data['Client']['id'] = $client['id'];

				// Envoi d'un email d'activation de compte Cloud si le client n'a pas déjà un accès
				if(empty($client['password']))
				{
					
					$this->request->data['Client']['token_email'] = $this->Auth->password($_SERVER['REMOTE_ADDR'].date('Y-m-d H:i:s'));
					
					$this->Client->saveIni($this->request->data);

					$body 	 = 'Vous venez de débuter votre premier projet chez E-Novative Keys et nous vous en remercions !<br /><br />';
					$body	.= 'De manière à pouvoir participer au mieux à l\'évolution de votre projet, nous vous invitons à activer aujourd\'hui votre compte ENK-Cloud.<br />';
					$body 	.= 'Rendez-vous <a href="http://enkcloud.com/config/'.$this->request->data['Client']['token_email'] .'">sur ce lien</a> pour créer votre mot de passe.';
					$body	.= 'Vous pourrez ensuite vous connecter à votre espace professionnel ENK-Cloud disponible à l\'adresse <a href="http://enkcloud.com/">www.enkcloud.com</a>.<br /><br />';
					$body 	.= 'Vous remerciant pour votre confiance,<br />L\'équipe E-Novative Keys.';
					
					$this->Mailer->send(array($client['firstname'].' '.$client['lastname'] => $client['email']), "Compte ENK-Cloud", $body);
				}

				// Mise à jour du cloud_enabled
				if($client['cloud_enabled'] == '0')
				{
					$this->request->data['Client']['cloud_enabled'] = '1';
					$this->Client->saveIni($this->request->data);
				}

				$this->set(compact('projects'));
			}
			else 			
				$this->set(compact('state'));
		}
		else
			$this->set('error', $this->Form->errors);
	}

	public function editProject()
	{	
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Project']['client_name']) 	|| empty($this->request->data['Project']['client_name'])
		|| !isset($this->request->data['Project']['lead_name']) 	|| empty($this->request->data['Project']['lead_name'])
		|| !isset($this->request->data['Project']['id']) 			|| empty($this->request->data['Project']['id']))
			die();

		// Validation des données directement liées au model
		if($this->Project->validates($this->request->data))
		{	
			$this->loadModel('Client');
			$this->loadModel('User');
			$this->loadModel('UsersProject');

			$clientName = explode(' ', $this->request->data['Project']['client_name']);
			$leadName 	= explode(' ', $this->request->data['Project']['lead_name']);
			unset($this->request->data['Project']['client_name']);
			unset($this->request->data['Project']['lead_name']);

			$this->request->data['Project']['client_id'] = $this->Client->findFirstIni(array(
				'fields'     => array('id'),
				'conditions' => array(
					'firstname' => $clientName[0],
					'lastname' 	=> $clientName[1]
				)
			))["id"];

			$this->request->data['Project']['lead_id'] = $this->User->findFirst(array(
				'fields' 		=> 'id',
				'conditions' 	=> array(
					'User.firstname' 	=> $leadName[0],
					'User.lastname' 	=> $leadName[1]
				)	
			))->id;
			
			$this->request->data['Project']['updated'] = date('Y-m-d H:i:s');

			$leadAssoc = $this->UsersProject->findFirst(array(
				'conditions'	=> array(
					'user_id' 	 => $this->request->data['Project']['lead_id'],
					'project_id' => $this->request->data['Project']['id']
				)
			));

			if(!$leadAssoc)
			{
				$this->UsersProject->save(array(
					'users_project' => array(
						'user_id' 	 => $this->request->data['Project']['lead_id'],
						'project_id' => $this->request->data['Project']['id']
					)
				));
			}
		
			// Sauvegarde de l'édition du projet
			if(($state = $this->Project->save($this->request->data)))
				$this->set('projects', $this->Project->find());
			else		
				$this->set(compact('state'));
		}
		else 
			$this->set('error', $this->Form->errors);
	}

	public function deleteProject()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Project']['id']) || empty($this->request->data['Project']['id']))
			die();

		$clientId = $this->Project->findFirst(array(
			'fields' 		=> 'client_id',
			'conditions' 	=> array('Project.id' => $this->request->data['Project']['id'])
		))->client_id;
			
		// Si bien supprimé, on renvoie toute la liste
		if(($state = $this->Project->delete($this->request->data['Project']['id'])))
		{
			// Suppression des répertoire du projet sur le Cloud
			$path = FILES_ROOT.'Cloud'.DS.$clientId.DS.$this->request->data['Project']['id'];

			if(file_exists($path)) 
			{
				if($this->Utils->recursiveRmdir($path) === false)
					$this->set('error', "Cannot remove project $path");
				else
					$this->set('project', 'deleted');		
			}
			else
				$this->set('error', 'Unable to find project ' . $path . ' - it doesn\'t exists.');

			// Une fois le projet supprimé, on regarde s'il existe un autre projet pour ce client.
			$projects = $this->Project->find(array(
				'conditions' => array('Project.client_id' => $clientId)
			));

			// Si ce client n'a plus de projet, on passe son cloud_enabled à 0
			if(!$projects)
			{
				unset($this->request->data['Project']);
				$this->request->data['Client']['id'] = $clientId;
				$this->request->data['Client']['cloud_enabled'] = '0';
				$this->Client->saveIni($this->request->data);
			}

			// On renvoie tous les projets restants
			$projects = $this->Project->find(array(
				'order' => 'name ASC'
			));

			$this->set(compact('projects'));
		}
		// Sinon erreur
		else 			
			$this->set(compact('state'));
	}

	public function currentProjects()
	{
		$projects = $this->Project->find(array(
			'conditions' => array(
				'Project.id IN' => '(SELECT project_id FROM users_projects WHERE user_id = '.$this->Auth->user('id').')'
			)
		));

		for($i = 0; $i < count($projects); $i++)
		{
			$projects[$i]->deadline 	= date('d-m-Y H:i:s', strtotime($projects[$i]->deadline));
			$projects[$i]->created 		= date('d-m-Y H:i:s', strtotime($projects[$i]->created));
		}

		$this->set(compact('projects'));
	}

	public function assignmentDevelopper()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['UsersProject']['project_id']) 	|| empty($this->request->data['UsersProject']['project_id'])
		|| !isset($this->request->data['UsersProject']['user_id']) 		|| empty($this->request->data['UsersProject']['user_id']))
			die();

		$this->loadModel('UsersProject');

		// Validation des données directement liées au model
		if($this->UsersProject->validates($this->request->data))
		{
			$this->request->data['users_project'] = $this->request->data['UsersProject'];
			unset($this->request->data['UsersProject']);

			$this->UsersProject->save($this->request->data);
			
			$this->set("affect", true);
		}
		else
			$this->set("error", $this->Form->errors);
	}

	public function unassignmentDevelopper()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['UsersProject']['project_id']) 	|| empty($this->request->data['UsersProject']['project_id'])
		|| !isset($this->request->data['UsersProject']['user_id']) 		|| empty($this->request->data['UsersProject']['user_id']))
			die();

		$this->loadModel('UsersProject');
		$this->loadModel('Project');

		// Validation des données directement liées au model
		if($this->UsersProject->validates($this->request->data))
		{
			$finding = $this->Project->findFirst(array(
				'conditions' => array(
					'Project.id' 				=> $this->request->data['UsersProject']['project_id'],
					'Project.lead_id' 			=> $this->request->data['UsersProject']['user_id']
				)
			));

			if($finding)
				return $this->set("disaffect", false);

			$affectation = $this->UsersProject->findFirst(array(
				'conditions' => array(
					'UsersProject.project_id'	=> $this->request->data['UsersProject']['project_id'],
					'UsersProject.user_id' 		=> $this->request->data['UsersProject']['user_id']
				)
			));
			
			$this->UsersProject->delete($affectation->id);
			
			$this->set("disaffect", true);
		}
		else
			$this->set("error", $this->Form->errors);
	}

	public function quotation()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Project']['id']) 				|| empty($this->request->data['Project']['id'])
		|| !isset($this->request->data['Project']['dev_salary']) 		|| empty($this->request->data['Project']['dev_salary'])
		|| !isset($this->request->data['Project']['leaddev_salary'])	|| empty($this->request->data['Project']['leaddev_salary'])
		|| !isset($this->request->data['Project']['tva'])				|| empty($this->request->data['Project']['tva']))
			die();

		$id = base64_decode($this->request->data['Project']['id']);

		$this->loadModel('Macrotask');
		$this->loadModel('Task');
		$this->loadModel('User');

		if(($project = $this->Project->findFirst(array('conditions' => array('Project.id' => $id)))) != null)
		{
			// On crée un nouveau document PDF
			$pdf = new FPDF();
			$pdf->SetTitle("Devis - ".$project->name);
			$pdf->SetAuthor("E-Novative Keys");

			$pdf->AddPage();
			$pdf->SetAutoPageBreak(true);
			$pdf->SetFont('Helvetica','B', 12);

			$pdf->Image('http://enkwebservice.com/img/logo.png', 55, 6, 100);
			$pdf->Ln(30);

			$pdf->Cell(40, 20, "Devis Projet - ".iconv('UTF-8', 'windows-1252', $project->name));
			$pdf->Ln(30);

			// On charge les macrotaches liées au projet
			$macrotasks = $this->Macrotask->find(array(
				'conditions' => array('Macrotask.project_id' => $project->id)
			));

			$totalCost = 0;

			if($macrotasks != null)
			{
				$pdf->Cell(40, 20, iconv('UTF-8', 'windows-1252', "Tâches effectuées sur ce projet :"));
				$pdf->Ln(15);	
				$pdf->SetFont('Helvetica', '', 12);

				// Pour chaque macrotache...
				for($i = 0 ; $i < count($macrotasks) ; $i++)
				{
					$task = $this->Task->find(array(
						'conditions' => array("macrotask_id" => $macrotasks[$i]->id)
					));

					$hours = 0;

					// On charge les tâches et on additionne le temps de travail
					if($task != null)
					{
						for($j = 0 ; $j < count($task) ; $j++)
							$hours += $task[$j]->hours;
					}

					$users = $this->User->find(array(
						'condition' => array('User.id IN' => '(SELECT user_id FROM macrotasks_users WHERE macrotask_id = '.$macrotasks[$i]->id.')')
					));

					$roles = array(
						'dev' 		=> 0,
						'leaddev' 	=> 0
					);

					// On récupère le statut des utilisateurs ayant travaillé sur la macrotache
					if($users != null)
					{
						for($j = 0 ; $j < count($users) ; $j++)
						{
							if(in_array($users[$j]->role, array('trainee', 'employee', 'dev')))
								$roles['dev']++;
							else if(in_array($users[$j]->role, array('leaddev', 'admin')))
								$roles['leaddev']++;
						}
					}

					$cost = (($roles['dev'] * $this->request->data['Project']['dev_salary']
							+ $roles['leaddev'] * $this->request->data['Project']['leaddev_salary'])
							/ ($roles['dev'] + $roles['leaddev'])
							) * $hours;

					$totalCost += $cost;

					$pdf->Cell(10);
					$pdf->Cell(60, 20, iconv('UTF-8', 'windows-1252', ($i+1).". ".$macrotasks[$i]->name." : "));
					$pdf->Cell(40, 20, iconv('UTF-8', 'windows-1252', $cost." € (HT)"));
					$pdf->Cell(40, 20, iconv('UTF-8', 'windows-1252', ($cost + ($cost * ($this->request->data['Project']['tva']/100)))." € (TTC)"));
					$pdf->Ln(10);
				}
			}

			$pdf->SetFont('Helvetica','B', 12);
			$pdf->Ln(20);

			$pdf->Cell(5);
			$pdf->Cell(65, 20, iconv('UTF-8', 'windows-1252', "Coût total HT : "));
			$pdf->Cell(40, 20, iconv('UTF-8', 'windows-1252', $totalCost." €"));
			$pdf->Ln(7);

			$pdf->Cell(5);
			$pdf->Cell(65, 20, iconv('UTF-8', 'windows-1252', "TVA appliquée : "));
			$pdf->Cell(40, 20, iconv('UTF-8', 'windows-1252', $this->request->data['Project']['tva']." %"));
			$pdf->Ln(7);

			$pdf->Cell(5);
			$pdf->Cell(65, 20, iconv('UTF-8', 'windows-1252', "Remise appliquée : "));
			$pdf->Cell(40, 20, iconv('UTF-8', 'windows-1252', $project->discount." %"));
			$pdf->Ln(7);

			$pdf->Cell(5);
			$pdf->Cell(65, 20, iconv('UTF-8', 'windows-1252', "Coût total TTC : "));
			$pdf->Cell(40, 20, iconv('UTF-8', 'windows-1252', ($totalCost + ($totalCost * ($this->request->data['Project']['tva']/100)) - ($totalCost * ($project->discount/100)))." €"));

			$pdf->Output("Devis - ".$project->name.".pdf ", 'I');
			die();
		}
	}
}

?>