<?php

class User extends Model
{
	public $validate = array(
		'firstname' => array(
			'AlphaRule' => array(
				'rule' 		=> '([a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ -]+)',
				'message' 	=> 'Le prénom n\'est pas valide'
			),
			'BetweenRule' => array(
				'rule' 		=> array('between', 3, 30),
				'message' 	=> 'Entre 3 et 30 caractères'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un prénom'
			)
		),
		'lastname' => array(
			'AlphaRule' => array(
				'rule' 		=> '([a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ -]+)',
				'message' 	=> 'Le nom n\'est pas valide'
			),
			'BetweenRule' => array(
				'rule' 		=> array('between', 2, 30),
				'message' 	=> 'Entre 2 et 30 caractères'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un nom'
			)
		),
		'email' => array(
			'EmailRule' => array(
				'rule' 		=> 'email',
				'message' 	=> 'Vous devez rentrer un email valide'
			)
		),
		'role' => array(
			'RoleRule' => array(
				'rule' 		=> array('inArray', array('employee', 'trainee', 'developer', 'leaddev', 'admin')),
				'message' 	=> 'Ce rôle n\'existe pas'
			)
		)
	);
}

?>