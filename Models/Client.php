<?php

class Client extends Model
{
	public $table = array('ini', 'Clients');

	public $validate = array(
		'firstname'	=> array(
			'AlphaRule' 	=> array(
				'rule' 			=> '([a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ -]+)',
				'message' 		=> 'Le prénom n\'est pas valide'
			),
			'BetweenRule' 	=> array(
				'rule' 			=> array('between', 3, 30),
				'message' 		=> 'Entre 3 et 30 caractères'
			),
			'EmptyRule' 	=> array(
				'rule' 			=> 'notEmpty',
				'message' 		=> 'Vous devez préciser un prénom'
			)
		),
		'lastname' 	=> array(
			'AlphaRule' 	=> array(
				'rule' 			=> '([a-zA-Zàáâãäåçèéêëìíîïðòóôõöùúûüýÿ -]+)',
				'message' 		=> 'Le nom n\'est pas valide'
			),
			'BetweenRule' 	=> array(
				'rule' 			=> array('between', 2, 30),
				'message' 		=> 'Entre 2 et 30 caractères'
			),
			'EmptyRule'		=> array(
				'rule' 			=> 'notEmpty',
				'message' 		=> 'Vous devez préciser un nom'
			)
		),
		'email' 	=> array(
			'EmailRule' 	=> array(
				'rule' 			=> 'email',
				'message'		=> 'Vous devez rentrer un email valide'
			)
		),
		'address' 	=> array(
			'EmptyRule' 	=> array(
				'rule' 			=> 'notEmpty',
				'message' 		=> 'Vous devez entrer une adresse'
			)
		),
		'enterprise' => array(
			'EmptyRule'		=> array(
				'rule'			=> 'notEmpty',
				'message'		=> 'Vous devez entrer le nom d\'une entreprise'
			),
			'MaxRule'		=> array(
				'rule'			=> array('max', 100),
				'message'		=> '100 caractères maximum'
			),
			'AlphaRule'		=> array(
				'rule'			=> '([a-zA-Z0-9&àáâãäåçèéêëìíîïðòóôõöùúûüýÿ\. -]+)',
				'message'		=> 'Le nom de l\'entreprise n\'est pas valide'
			)
		),
		'siret' => array(
			'EmptyRule'		=> array(
				'rule'			=> 'notEmpty',
				'message'		=> 'Vous devez entrer un numéro siret'
			),
			'SiretRule' 	=> array(
				'rule'			=> '([0-9]{3}?[0-9]{3}?[0-9]{3}?[0-9]{5})',
				'message'		=> 'Votre siret n\'est pas valide'
			)
		),
		'phonenumber' => array(
			'EmptyRule'		=> array(
				'rule'			=> 'notEmpty',
				'message'		=> 'Vous devez entrer un numéro de téléphone'
			),
			'PhoneRule'		=> array(
				'rule'			=> '(?:(?:\\(?(?:00|\\+)([1-4]\\d\\d|[1-9]\\d?)\\)?)?[\\-\\.\\ \\\\\\/]?)?((?:\\(?\\d{1,}\\)?[\\-\\.\\ \\\\\\/]?){0,})(?:[\\-\\.\\ \\\\\\/]?(?:#|ext\\.?|extension|x)[\\-\\.\\ \\\\\\/]?(\\d+))?',
				'message'		=> 'Votre numéro de téléphone n\'est pas valide'
			)
		)
	);
}

?>