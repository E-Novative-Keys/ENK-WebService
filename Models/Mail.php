<?php

class Mail extends Model
{
	public $validate = array(
		'object' => array(
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un objet de message'
			),
			'BetweenRule' => array(
				'rule' 		=> array('between', 1, 100),
				'message' 	=> 'Entre 1 et 100 caractères'
			)
		),
		'content' => array(
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez entrer un message'
			)
		),
		'project_id' => array(
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Un projet doit être précisé'
			)
		)
	);
}

?>