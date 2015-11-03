<?php

class TasksController extends Controller
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->Auth->authorized(array('admin', 'leaddev', 'developer', 'trainee', 'employee'));
	}

	public function listMacrotasks()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Project']['id']) || empty($this->request->data['Project']['id']))
			die();
		
		$this->loadModel('Project');
		$this->loadModel('Macrotask');
		$this->loadModel('Notification');

		$macrotasks = $this->Macrotask->find(array(
			'conditions' => array('Macrotask.project_id' => $this->request->data['Project']['id'])
		));

		unset($this->request->data['Project']);

		for($i = 0; $i < count($macrotasks); $i++)
		{
			// 'Points' pour les tâches terminées ET pour celles non terminées
			$donePoints = 0;
			$toDoPoints = 0;

			// Tâches déjà réalisées
			$doneTasks = $this->Task->find(array(
				'fields'		=> array('Task.priority', 'Task.progress'),
				'conditions'	=> array(
					'Task.macrotask_id' => $macrotasks[$i]->id,
					'Task.progress <>' 	=> 0
				)
			));

			// Calcul des points
			if($doneTasks)
				for($j = 0; $j < count($doneTasks); $j++)
					$donePoints += intval($doneTasks[$j]->priority);
			
			// Toutes les tâches
			$toDoTasks = $this->Task->find(array(
				'fields'		=> array('Task.priority', 'Task.progress'),
				'conditions'	=> array(
					'Task.macrotask_id' => $macrotasks[$i]->id,
					'Task.progress <>' 	=> 1
				)
			));

			// Calcul des points
			if($toDoTasks)
				for($j = 0; $j < count($toDoTasks); $j++)
					$toDoPoints += intval($toDoTasks[$j]->priority);
			
			// S'il y a des tâches réalisées -> calcul du progress
			if($doneTasks)
				$progress = ($donePoints / ($toDoPoints + $donePoints)) * 100;
			// Sinon 0
			else
				$progress = 0;

			$progress = round($progress, 2);
			
			// Gestion des notifications de macrotâche
			if($progress == 100 && $macrotasks[$i]->progress == "0")
			{
				$newProgress = "1";
				
				// Envoyer une notification de fin de macrotask
				$this->request->data['Notification']['project_id'] 	= $macrotasks[$i]->project_id;
				$this->request->data['Notification']['created'] 	= date('Y-m-d H:i:s');

				$this->request->data['Notification']['content'] 	= "La macrotâche '".$macrotasks[$i]->name."' vient de se terminer.";
				
				$this->Notification->save($this->request->data);
				
				unset($this->request->data['Notification']);
			}
			else if($progress != 100 && $macrotasks[$i]->progress == "1")
			{
				$newProgress = "0";
				// Envoyer une notification d'annulation de fin de macrotask
				$this->request->data['Notification']['project_id'] 	= $macrotasks[$i]->project_id;
				$this->request->data['Notification']['created'] 	= date('Y-m-d H:i:s');

				$this->request->data['Notification']['content'] 	= "La macrotâche '".$macrotasks[$i]->name."' vient d'être réouverte.";
			
				$this->Notification->save($this->request->data);
				
				unset($this->request->data['Notification']);
			}

			if(isset($newProgress))
			{
				$this->request->data['Macrotask']['progress'] 	= $newProgress;
				$this->request->data['Macrotask']['id'] 		= $macrotasks[$i]->id;

				if($macrotasks[$i]->progress != $newProgress)
					$this->Macrotask->save($this->request->data);

				unset($this->request->data['Macrotask']);
			}
			
			$macrotasks[$i]->progress = $progress;
		}

		$this->set(compact('macrotasks'));
	}

	public function addMacrotask() 
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Macrotask']['project_id'])	|| empty($this->request->data['Macrotask']['project_id'])
		|| !isset($this->request->data['Macrotask']['name']) 		|| empty($this->request->data['Macrotask']['name'])
		|| !isset($this->request->data['Macrotask']['priority']) 	|| empty($this->request->data['Macrotask']['priority'])
		|| !isset($this->request->data['Macrotask']['hour']) 		|| empty($this->request->data['Macrotask']['hour'])
		|| !isset($this->request->data['Macrotask']['minute']) 		|| empty($this->request->data['Macrotask']['minute']))
			die();

		// Passer h:m:s à 00:00:00
		$this->request->data['Macrotask']['deadline'] = current(explode(' ', $this->request->data['Macrotask']['deadline']));

		$this->loadModel('Macrotask');

		$this->request->data['Macrotask']['created'] = date('Y-m-d H:i:s');
		$this->request->data['Macrotask']['updated'] = date('Y-m-d H:i:s');

		// Validation des données reliées au model
		if($this->Macrotask->validates($this->request->data))
		{
			if(($state = $this->Macrotask->save($this->request->data)))
			{
				$macrotask_id = $this->Macrotask->find(array(
					'fields' => array('Macrotask.id'),
					'order' => 'created DESC',
					'limit' => 1
				));

				$this->set(compact("macrotask_id"));
			}
			else
				$this->set(compact("state"));
		}
		else
			$this->set("error", $this->Form->errors);
	}

	public function editMacrotask()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Macrotask']['project_id'])	|| empty($this->request->data['Macrotask']['project_id'])
		|| !isset($this->request->data['Macrotask']['name']) 		|| empty($this->request->data['Macrotask']['name'])
		|| !isset($this->request->data['Macrotask']['priority']) 	|| empty($this->request->data['Macrotask']['priority'])
		|| !isset($this->request->data['Macrotask']['hour']) 		|| empty($this->request->data['Macrotask']['hour'])
		|| !isset($this->request->data['Macrotask']['minute']) 		|| empty($this->request->data['Macrotask']['minute'])
		|| !isset($this->request->data['Macrotask']['id']) 			|| empty($this->request->data['Macrotask']['id']))
			die();

		$this->loadModel('Macrotask');

		// Passer h:m:s à 00:00:00
		$this->request->data['Macrotask']['deadline']	= current(explode(' ', $this->request->data['Macrotask']['deadline']));
		$this->request->data['Macrotask']['updated'] 	= date('Y-m-d H:i:s');

		// Validation des données reliées au model
		if($this->Macrotask->validates($this->request->data))
		{
			if(($state = $this->Macrotask->save($this->request->data)))
			{
				$macrotask_id = $this->request->data['Macrotask']['id'];

				$this->set(compact("macrotask_id"));
			}
			else
				$this->set(compact("state"));
		}
		else
			$this->set("error", $this->Form->errors);
	}

	public function deleteMacrotask()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Macrotask']['id']) || empty($this->request->data['Macrotask']['id']))
			die();
		
		$this->loadModel('Macrotask');
		$this->loadModel('MacrotasksUser');

		$tasks = $this->Task->find(array(
			'fields'		=> array('Task.id'),
			'conditions'	=> array('macrotask_id' => $this->request->data['Macrotask']['id'])
		));

		$macrotasksUsers = $this->MacrotasksUser->find(array(
			'fields'		=> 'id',
			'conditions'	=> array('macrotask_id' => $this->request->data['Macrotask']['id'])
		));

		// Destruction des utilisateurs associés à la macrotâche
		if($macrotasksUsers != null)
		{
			for($i = 0; $i < count($macrotasksUsers); $i++)
				$this->MacrotasksUser->delete($macrotasksUsers[$i]->id);
		}

		// Destruction des sous-tâches associées
		if($tasks != null)
		{
			for($i = 0; $i < count($tasks); $i++)
				$this->Task->delete($tasks[$i]->id);
		}

		// Si bien edité, on renvoie toute la liste
		if(($state = $this->Macrotask->delete($this->request->data['Macrotask']['id'])))
		{
			$listMacrotasks = $this->Macrotask->find(array(
				'order' => 'created ASC'
			));

			$this->set(compact('listMacrotasks'));
		}
		// Sinon erreur
		else 			
			$this->set(compact('state'));
	}

	public function listTasks()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Macrotask']['id']) || empty($this->request->data['Macrotask']['id']))
			die();
		
		$tasks = $this->Task->find(array(
			'conditions'	=> array(
				'Task.macrotask_id' => $this->request->data['Macrotask']['id']
			)
		));

		$this->set(compact('tasks'));
	}

	public function addTask()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Task']['macrotask_id'])	|| empty($this->request->data['Task']['macrotask_id'])
		|| !isset($this->request->data['Task']['name']) 		|| empty($this->request->data['Task']['name'])
		|| !isset($this->request->data['Task']['priority']) 	|| empty($this->request->data['Task']['priority']))
			die();

		$this->request->data['Task']['created'] 	= date('Y-m-d H:i:s');
		$this->request->data['Task']['progress'] 	= 0;

		// Validation des données reliées au model
		if($this->Task->validates($this->request->data))
		{
			if(($state = $this->Task->save($this->request->data)))
			{
				$task = $this->Task->findFirst(array(
					'conditions' => array(
						'Task.macrotask_id' => $this->request->data['Task']['macrotask_id'],
						'Task.name' 		=> $this->request->data['Task']['name']
					)
				));
			
				$this->set(compact("task"));
			}
			else
				$this->set(compact("state"));
		}
		else
			$this->set("error", $this->Form->errors);
	}

	public function editTask()
	{	
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Task']['id'])		|| empty($this->request->data['Task']['id'])
		|| !isset($this->request->data['Task']['priority']) || empty($this->request->data['Task']['priority'])
		|| !isset($this->request->data['Task']['name']) 	|| empty($this->request->data['Task']['name'])
		|| !isset($this->request->data['Task']['hours']) 	|| empty($this->request->data['Task']['hours'])
		|| !isset($this->request->data['Macrotask']['id']) 	|| empty($this->request->data['Macrotask']['id']))
			die();

		$this->request->data['Task']['macrotask_id'] = $this->request->data['Macrotask']['id'];

		// Validation des données reliées au model
		if($this->Task->validates($this->request->data))
		{
			if(($state = $this->Task->save($this->request->data)))
			{
				$tasks = $this->Task->find(array(
					'order' => 'priority DESC',
				));

				$this->set(compact("tasks"));
			}
			else
				$this->set(compact("state"));
		}
		else
			$this->set("error", $this->Form->errors);
	}

	public function deleteTask()
	{
		if(!isset($this->request->data['Task']['id']) || empty($this->request->data['Task']['id']))
			die();
		
		// Si bien supprimé, on renvoie toute la liste
		if(($state = $this->Task->delete($this->request->data['Task']['id'])))
		{
			$tasks = $this->Task->find(array(
				'order' => 'priority DESC'
			));

			$this->set(compact('tasks'));
		}
		// Sinon erreur
		else
			$this->set(compact('state'));
	}

	public function listUserMacrotask()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Macrotask']['id']) || empty($this->request->data['Macrotask']['id']))
			die();
		
		$this->loadModel('MacrotasksUser');
		$this->loadModel('User');

		$users = $this->User->find(array(
			'conditions' => array(
				'id IN' => '(SELECT user_id FROM macrotasks_users WHERE macrotask_id = '.$this->request->data['Macrotask']['id'].')'
			)
		));

		$this->set(compact('users'));
	}

	public function addUserMacrotask()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['MacrotasksUser']['macrotask_id']) 	|| empty($this->request->data['MacrotasksUser']['macrotask_id'])
		|| !isset($this->request->data['MacrotasksUser']['user_name']) 		|| empty($this->request->data['MacrotasksUser']['user_name']))
			die();

		$this->loadModel('MacrotasksUser');
		$this->loadModel('User');

		$userName = explode(' ', $this->request->data['MacrotasksUser']['user_name']);

		$this->request->data['MacrotasksUser']['user_id'] = $this->User->findFirst(array(
			'fields' => 'id',
			'conditions' => array(
				'User.firstname' 	=> $userName[0],
				'User.lastname' 	=> $userName[1]
			)
		))->id;

		unset($this->request->data['MacrotasksUser']['user_name']);

		// Validation des données reliées au Model
		if($this->MacrotasksUser->validates($this->request->data))
		{
			$this->request->data['macrotasks_user'] = $this->request->data['MacrotasksUser'];
			unset($this->request->data['MacrotasksUser']);
			
			if(($state = $this->MacrotasksUser->save($this->request->data)))
				$this->set("macrotasksUser", "Validate");
			else
				$this->set(compact("state"));
		}
		else
			$this->set("error", $this->Form->errors);
	}

	public function editUserMacrotask()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['MacrotasksUser']['macrotask_id']) 	|| empty($this->request->data['MacrotasksUser']['macrotask_id'])
		|| !isset($this->request->data['MacrotasksUser']['user_name']) 		|| empty($this->request->data['MacrotasksUser']['user_name']))
			die();

		$this->loadModel('MacrotasksUser');
		$this->loadModel('User');

		$userName = explode(' ', $this->request->data['MacrotasksUser']['user_name']);

		$this->request->data['MacrotasksUser']['user_id'] = $this->User->findFirst(array(
			'fields' => 'id',
			'conditions' => array(
				'User.firstname' 	=> $userName[0],
				'User.lastname' 	=> $userName[1]
			)
		))->id;

		unset($this->request->data['MacrotasksUser']['user_name']);

		if($this->request->data['MacrotasksUser']['purge'] == "true")
		{
			$macrotasks = $this->MacrotasksUser->find(array(
				'conditions' => array(
					'MacrotasksUser.macrotask_id' => $this->request->data['MacrotasksUser']['macrotask_id']
				)
			));

			for($i = 0; $i < count($macrotasks); $i++)
				$this->MacrotasksUser->delete($macrotasks[$i]->id);
		}

		// Validation des données reliées au Model
		if($this->MacrotasksUser->validates($this->request->data))
		{
			$this->request->data['macrotasks_user'] = $this->request->data['MacrotasksUser'];
			unset($this->request->data['MacrotasksUser']);
			unset($this->request->data['macrotasks_user']['purge']);
			
			if(($state = $this->MacrotasksUser->save($this->request->data)))
				$this->set("macrotasksUser", "Validate");
			else
				$this->set(compact("state"));
		}
		else
			$this->set("error", $this->Form->errors);
	}
}

?>