<?php

class MailsController extends Controller
{
	public function beforeFilter()
	{
		parent::beforeFilter();

		$this->loadModel('Project');
	}

	public function listMails()
	{	
		if($this->Auth->user('role') == "client")
		{
			$mails = $this->Mail->find(array(
				'fields' => array('Mail.id', 'Mail.project_id','Mail.object', 'Mail.content', 'Mail.created'),
				'conditions' => array(
					'Mail.to'			=> $this->Auth->user('id'),
					'Mail.id IN' 		=> '(SELECT mail_id FROM clients_mails WHERE client_id = '.$this->Auth->user('id').')'
				),
				'order' => 'created DESC'
			));

			for($i = 0; $i < count($mails); $i++)
			{
				$mails[$i]->from 	= "Equipe de dev - ".$this->Project->findFirst(
					array('conditions' => array('Project.id' => $mails[$i]->project_id))
				)->name;
				$mails[$i]->created = date('d-m-Y H:i:s', strtotime($mails[$i]->created));
				
				unset($mails[$i]->project_id);
			}
		}
		else if($this->Auth->user('role') != "client")
		{
			// Vérification de la présence des bonnes données
			if(!isset($this->request->data['Mail']['project']) || empty($this->request->data['Mail']['project']))
				die();

			$id = base64_decode($this->request->data['Mail']['project']);

			// Si le projet n'existe pas on die
			if(($project = $this->Project->findFirst(array('conditions' => array('Project.id' => $id)))) == null)
				die();

			$this->loadModel('Client');
			
			$client = $this->Client->findFirstIni(array('conditions' => array('id' => $project->client_id)));

			$mails = $this->Mail->find(array(
				'fields' => array('Mail.id', 'Mail.object', 'Mail.content', 'Mail.created'),
				'conditions' => array(
					'Mail.project_id' 	=> $project->id,
					'Mail.to'			=> null,
					'Mail.id IN'		=> '(SELECT mail_id FROM projects_mails WHERE project_id = '.$project->id.')'
				),
				'order' => 'Mail.created DESC'
			));

			for($i = 0; $i < count($mails); $i++)
			{
				$mails[$i]->from 	= $client['firstname']." ".$client['lastname'];
				$mails[$i]->created = date('d-m-Y H:i:s', strtotime($mails[$i]->created));
			}
		}

		$this->set(compact("mails"));
	}

	public function sentMail()
	{
		if($this->Auth->user('role') == "client")
		{
			$mails = $this->Mail->find(array(
				'fields' => array('Mail.id', 'Mail.project_id','Mail.object', 'Mail.content', 'Mail.created'),
				'conditions' => array(
					'Mail.to'			=> null,
					'Mail.id IN' 		=> '(SELECT mail_id FROM clients_mails WHERE client_id = '.$this->Auth->user('id').')'
				),
				'order' => 'Mail.created DESC'
			));

			for($i = 0; $i < count($mails); $i++)
			{
				$mails[$i]->from 	= "Equipe de dev - ".$this->Project->findFirst(
					array('conditions' => array('Project.id' => $mails[$i]->project_id))
				)->name;
				$mails[$i]->created = date('d-m-Y H:i:s', strtotime($mails[$i]->created));
				
				unset($mails[$i]->project_id);
			}
		}
		else if($this->Auth->user('role') != "client")
		{
			// Vérification de la présence des bonnes données
			if(!isset($this->request->data['Mail']['project']) || empty($this->request->data['Mail']['project']))
				die();

			$id = base64_decode($this->request->data['Mail']['project']);

			// Si le projet n'existe pas on die
			if(($project = $this->Project->findFirst(array('conditions' => array('Project.id' => $id)))) == null)
				die();

			$this->loadModel('Client');

			$client = $this->Client->findFirstIni(array('conditions' => array('id' => $project->client_id)));

			$mails = $this->Mail->find(array(
				'fields' => array('Mail.id', 'Mail.object', 'Mail.content', 'Mail.created'),
				'conditions' => array(
					'Mail.project_id' 	=> $project->id,
					'Mail.to'			=> $project->client_id,
					'Mail.id IN' 		=> '(SELECT mail_id FROM projects_mails WHERE project_id = '.$project->id.')'
				),
				'order' => 'Mail.created DESC'
			));

			for($i = 0; $i < count($mails); $i++)
			{
				$mails[$i]->to 		= $client['firstname']." ".$client['lastname'];
				$mails[$i]->created = date('d-m-Y H:i:s', strtotime($mails[$i]->created));
			}
		}

		$this->set(compact("mails"));
	}

	public function newMail()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Mail']['project']) 	|| empty($this->request->data['Mail']['project'])
		|| !isset($this->request->data['Mail']['object']) 	|| empty($this->request->data['Mail']['object'])
		|| !isset($this->request->data['Mail']['content']) 	|| empty($this->request->data['Mail']['content']))
			die();

		$id = base64_decode($this->request->data['Mail']['project']);
		unset($this->request->data['Mail']['project']);

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('Project.id' => $id)))) == null)
			die();

		$created = date('Y-m-d H:i:s');

		$this->request->data['Mail']['project_id']	= $project->id;
		$this->request->data['Mail']['created'] 	= $created;

		if($this->Mail->validates($this->request->data))
		{
			$this->loadModel('ClientsMail');
			$this->loadModel('ProjectsMail');

			if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
			{
				if($this->Mail->save($this->request->data))
				{
					if(($mail = $this->Mail->findFirst(array(
						'conditions' => array(
							'Mail.project_id' 	=> $project->id,
							'Mail.to'			=> null,
							'Mail.created'		=> $created
						)))))
					{
						unset($this->request->data['Mail']);

						$this->request->data['clients_mail']['client_id'] 		= $this->Auth->user('id');
						$this->request->data['clients_mail']['mail_id']			= $mail->id;

						$this->ClientsMail->save($this->request->data);

						unset($this->request->data['clients_mail']);
						$this->request->data['projects_mail']['project_id'] 	= $project->id;
						$this->request->data['projects_mail']['mail_id']		= $mail->id;

						$this->ProjectsMail->save($this->request->data);

						$this->set('email', 'send');
					}
					else
						$this->set('error', 'Mail introuvable');
				}
				else
					$this->set('error', 'Mail non sauvegardé');
			}
			else if($this->Auth->user('role') != "client")
			{
				$this->request->data['Mail']['to'] 	= $project->client_id;

				if($this->Mail->save($this->request->data))
				{
					if(($mail = $this->Mail->findFirst(array(
						'conditions' => array(
							'Mail.project_id' 	=> $project->id,
							'Mail.to'			=> $project->client_id,
							'Mail.created'		=> $created
						)))))
					{
						unset($this->request->data['Mail']);

						$this->request->data['clients_mail']['client_id'] 		= $project->client_id;
						$this->request->data['clients_mail']['mail_id']			= $mail->id;

						$this->ClientsMail->save($this->request->data);

						unset($this->request->data['clients_mail']);
						$this->request->data['projects_mail']['project_id'] 	= $project->id;
						$this->request->data['projects_mail']['mail_id']		= $mail->id;

						$this->ProjectsMail->save($this->request->data);

						$this->set('email', 'send');
					}
					else
						$this->set('error', 'Mail introuvable');
				}
				else
					$this->set('error', 'Mail non sauvegardé');
			}
		}
		else
			$this->set('error', $this->Form->errors);
	}

	public function deleteMail()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Mail']['id']) || empty($this->request->data['Mail']['id']))
			die();

		$this->loadModel('ClientsMail');
		$this->loadModel('ProjectsMail');

		$mail_id 	= base64_decode($this->request->data['Mail']['id']);
				
		if($this->Auth->user('role') == "client")
		{
			// Si le mail n'existe pas on die
			if(($mail = $this->Mail->findFirst(array('conditions' => array('Mail.id' => $mail_id)))) == null
			|| ($this->Project->findFirst(array('conditions' => array('Project.client_id' => $this->Auth->user('id'), 'Project.id' => $mail->project_id))) == null))
				die();
		}
		else if($this->Auth->user('role') != "client")
		{
			// Si le mail n'existe pas on die
			if(($mail = $this->Mail->findFirst(array('conditions' => array('Mail.id' => $mail_id)))) == null)
				die();
		}

		// Recherche du mail dans les tables de liaisons clients_mails et projects_mails
		$mailClient 	= $this->ClientsMail->findFirst(array('conditions' => array('ClientsMail.mail_id' => $mail->id)));
		$mailProject 	= $this->ProjectsMail->findFirst(array('conditions' => array('ProjectsMail.mail_id' => $mail->id)));

		if($this->Auth->user('role') == "client")
		{
			// Si les 2 liaisons existent, on supprime la liaison avec le Client
			if($mailClient && $mailProject)
				$this->ClientsMail->delete($mailClient->id);
			// Si la liaison Projet n'existe pas, on supprime le Mail directement
			// Avec le ON DELETE CASCADE la liaison Client sera aussi supprimée
			else if($mailProject == null)
				$this->Mail->delete($mail->id);
			else
				return $this->set('error', 'error');

			$this->set('email', 'deleted');
		}
		else if($this->Auth->user('role') != "client")
		{
			if(!isset($this->request->data['Mail']['project']) 	|| empty($this->request->data['Mail']['project']))
				die();

			$id = base64_decode($this->request->data['Mail']['project']);

			// Si le projet n'existe pas on die
			if(($project = $this->Project->findFirst(array('conditions' => array('Project.id' => $id)))) == null)
				die();

			// Si les 2 liaisons existent, on supprime la liaison avec le Projet
			if($mailClient && $mailProject)
				$this->ProjectsMail->delete($mailProject->id);
			// Si la liaison Client n'existe pas, on supprime le Mail directement
			// Avec le ON DELETE CASCADE la liaison Project sera aussi supprimée
			else if($mailClient == null)
				$this->Mail->delete($mail->id);
			else
				return $this->set('error', 'error');

			$this->set('email', 'deleted');
		}
	}
}

?>