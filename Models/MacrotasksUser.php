<?php

class MacrotasksUser extends Model
{
	public $table = 'macrotasks_users';

	public $validate = array(
		'macrotask_id' => array(
			'NumRule' => array(
				'rule' 		=> '([0-9]+)',
				'message' 	=> 'L\'id de la macrotâche mère n\'est pas valide'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un id de macrotâche mère'
			)
		),
		'user_id' => array(
			'NumRule' => array(
				'rule' 		=> '([0-9]+)',
				'message' 	=> 'L\'id de l\'utilisateur n\'est pas valide'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un id d\'utilisateur'
			)
		)
	);
}

?>