<?php

class Macrotask extends Model
{
	public $validate = array(
		'project_id' => array(
			'NumRule' 	=> array(
				'rule' 		=> '([0-9]+)',
				'message' 	=> 'L\'id de la macrotâche n\'est pas valide'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un id de macrotâche'
			)
		),
		'name' => array(
			'AlphaRule' => array(
				'rule' 		=> '([a-zA-Z0-9àáâãäåçèéêëìíîïðòóôõöùúûüýÿ -]+)',
				'message' 	=> 'Le nom n\'est pas valide'
			),
			'BetweenRule' => array(
				'rule' 		=> array('between', 3, 30),
				'message' 	=> 'Entre 3 et 30 caractères'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un nom pour la macrotâche'
			)
		),
		'priority' => array(
			'MinRule' => array(
				'rule' 		=> array('min', 1),
				'message' 	=> 'Priorité 1 minimum'
			),
			'MaxRule' => array(
				'rule' 		=> array('max', 100),
				'message' 	=> 'Priorité 100 maximum'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser une priorité'
			)
		)
	);	
}

?>