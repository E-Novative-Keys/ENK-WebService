<?php 

class UsersProject extends Model 
{
	public $table = 'users_projects';

	public $validate = array(
		'project_id' => array(
			'NumRule' => array(
				'rule' 		=> '([0-9]+)',
				'message' 	=> 'L\'id du projet n\'est pas valide'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un id de projet'
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