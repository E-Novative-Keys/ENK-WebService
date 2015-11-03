<?php

class Project extends Model
{
	public $validate = array(
		'name' => array(
			'AlphaRule' => array(
				'rule' 		=> '([\.a-zA-Z0-_9&àáâãäåçèéêëìíîïðòóôõöùúûüýÿ -]+)',
				'message' 	=> 'Le nom de projet n\'est pas valide'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un nom de projet'
			),
			'BetweenRule' => array(
				'rule' 		=> array('between', 3, 30),
				'message' 	=> 'Entre 3 et 30 caractères'
			)
		),
		'estimation' => array(
			'AlphaRule' => array(
				'rule' 		=> '([0-9]*[\.]?[0-9]{0,3})',
				'message' 	=> 'L\'estimation n\'est pas valide'
			)
		),
		'budget' => array(
			'AlphaRule' => array(
				'rule' 		=> '([0-9]*[\.]?[0-9]{0,3})',
				'message' 	=> 'Le budget n\'est pas valide'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser un budget'
			)
		),
		'discount' => array(
			'AlphaRule' => array(
				'rule' 		=> '([0-9]?[0-9][\.]?[0-9]{0,2})',
				'message' 	=> 'La remise n\'est pas valide'
			),
			'EmptyRule' => array(
				'rule' 		=> 'notEmpty',
				'message' 	=> 'Vous devez préciser une remise'
			)
		)
	);
}

?>