<?php

class NotificationsController extends Controller
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->Auth->authorized(array('client'));
	}

	public function listNotifications()
	{
		$notifications = $this->Notification->find(array(
			'fields' 		=> array('Notification.id', 'Notification.content', 'Notification.created'),
			'conditions' 	=> array('Notification.project_id IN' => '(SELECT id FROM projects WHERE client_id = '.$this->Auth->user('id').')'),
			'order'			=> 'Notification.created DESC'
		));

		for($i = 0; $i < count($notifications); $i++)
			$notifications[$i]->created = date('d-m-Y H:i:s', strtotime($notifications[$i]->created));
		
		$this->set(compact('notifications'));
	}

	public function deleteNotification()
	{
		if(!isset($this->request->data['Notification']['id']) || empty($this->request->data['Notification']['id']))
			die();

		$this->loadModel('Project');

		$id = base64_decode($this->request->data['Notification']['id']);

		// Si la notification existe et quelle est issue d'un projet appartenant à l'utilisateur connecté alors on la supprime
		if(($notification = $this->Notification->findFirst(array('conditions' => array('Notification.id' => $id)))) == null)
			die();
		
		if(($project = $this->Project->findFirst(array(
			'conditions' => array(
				'Project.id' 		=> $notification->project_id,
				'Project.client_id' => $this->Auth->user('id')
		)))) == null)
			die();
		else
			$this->Notification->delete($notification->id);
	}
}

?>