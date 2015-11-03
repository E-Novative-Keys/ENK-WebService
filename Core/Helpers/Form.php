<?php

class Form
{
	public $controller 	= null;
	public $errors 		= null;

	protected $tags = array(
		'form' 				=> '<form action="%s" %s>',
		'formend' 			=> '</form>',
		'text' 				=> '<input type="text" name="%s" %s/>',
		'password' 			=> '<input type="password" name="%s" %s/>',
		'number' 			=> '<input type="number" name="%s" %s/>',
		'email' 			=> '<input type="email" name="%s" %s/>',
		'url' 				=> '<input type="url" name="%s" %s/>',
		'tel' 				=> '<input type="tel" name="%s" %s/>',
		'date' 				=> '<input type="date" name="%s" %s/>',
		'color' 			=> '<input type="color" name="%s" %s/>',
		'range' 			=> '<input type="range" name="%s" %s/>',
		'search'			=> '<input type="search" name="%s" %s/>',
		'file' 				=> '<input type="file" name="%s" %s/>',
		'button' 			=> '<input type="button" name="%s" %s/>',
		'hidden'			=> '<input type="hidden" name="%s" %s/>',
		'checkbox' 			=> '<input type="checkbox" name="%s" %s/>',
		'checkboxmultiple' 	=> '<input type="checkbox" name="%s[]" %s />',
		'radio'				=> '<input type="radio" name="%s" %s />%s',
		'submit' 			=> '<input type="submit" %s/>',
		'submitimage' 		=> '<input type="image" src="%s" %s/>',
		'textarea' 			=> '<textarea name="%s" %s>%s</textarea>',		
		'selectstart' 		=> '<select name="%s" %s>',
		'selectmultiplestart' => '<select name="%s[]" %s>',
		'selectempty' 		=> '<option value="" %s>%s</option>',
		'selectoption' 		=> '<option value="%s" %s>%s</option>',
		'selectend' 		=> '</select>',
		'optiongroup' 		=> '<optgroup label="%s" %s>',
		'optiongroupend' 	=> '</optgroup>'
	);

	public function __construct($controller)
	{
		$this->controller = $controller;
	}

	/**
	* Utilisation :
	* echo $this->Form->create(array('id' => 'value', 'class' => 'value', 'type' => 'file'), 'POST/GET') <- Post par défaut
	*/
	public function create($options = array(), $method = 'POST')
	{
		$attributes = null;

		if(isset($options['type']) && !empty($options['type']))
		{
			$type = $options['type'];
			unset($options['type']);
		}
		
		$attributes .= 'method="'.$method.'" ';

		if(isset($type))
			$attributes .= ($type == 'file') ? 'enctype="multipart/form-data" ' : 'type="'.$type.'" ';

		foreach($options as $key => $value)
			$attributes .= $key.'="'.$value.'" ';
			
		return sprintf($this->tags['form'], BASE_URL.$this->controller->request->url, $attributes);
	}

	public function end()
	{
		return sprintf($this->tags['formend']);
	}

	/**
	* Utilisation :
	* echo $this->Html->input('User.email', array(
	*		'type' 	=> 'email', <- email, text, textarea, radio, tel, url, hidden, password, ...
	*		'label'	=> 'Label Text', <- affichage d'un label avant l'input
	*		'attr'	=> 'value' <- n'importe quel attribut
	* ));
	* 'label'			=> array('text' => 'Objet', 'class' => 'col-md-3 control-label')
	*/
	public function input($name, $options = array())
	{
		$exceptions = array('textarea', 'radio');

		$name = explode('.', $name);
		if(count($name) != 2)
			return null;

		$errorName = $name[1];
		
		$name = 'data['.$name[0].']['.$name[1].']';

		if(!isset($options['type']))
			return null;
		else
		{
			$type = $options['type'];
			unset($options['type']);
		}
		
		if(!isset($this->tags[$type]))
			return null;

		if(in_array($type, $exceptions))
		{
			$except = $options['value'];
			unset($options['value']);
		}

		$label = null;
		if(isset($options['label']))
		{
			if(!is_array($options['label']))
				$label = '<label for="input'.ucfirst($errorName).'">'.$options['label'].'</label>';
			else
			{
				$label = '<label for="input'.ucfirst($errorName).'" ';
				foreach($options['label'] as $key => $value)
				{
					if($key != "text")
						$label .= $key.'="'.$value.'" ';
				}
				$label .= '>'.$options['label'].'</label>';
			}
			unset($options['label']);

			if(isset($options['id']))
				$options['id'] .= ' input'.ucfirst($errorName);
			else
				$options['id'] = $name;
		}

		$attributes = null;
		foreach($options as $key => $value)
			$attributes .= $key.'="'.$value.'" ';

		$error = null;
		if(isset($this->errors[$errorName]))
			$error = '<span class="help-inline">'.$this->errors[$errorName].'</span>';

		if(isset($except))
			return $label.sprintf($this->tags[$type], $name, $attributes, $except).$error;
		else
			return $label.sprintf($this->tags[$type], $name, $attributes).$error;
		
		return $label;
	}

	/**
	* Utilisation :
	* echo $this->Form->select('User.firstname', $options_list,
	*		array(	'id' => 'value',
	*				'class' => 'value',
	*				'type' => 'multiple' <- pour faire des select multiple
	*				'empty' => '(choisissez)' <- activer une option vide avec le texte défini comme première option
	*				'options' => array('id' => 'value', 'class' => 'value', ...) <- attributs des options dans le select
	*			));
	* );
	* 
	* A faire : Options Group
	*	$options = array(
	*		'Group 1' => array(
	*  			'Value 1' => 'Label 1',
	*  			'Value 2' => 'Label 2'
	*		),
	*		'Group 2' => array(
	*  			'Value 3' => 'Label 3'
	*		)
	*	);
	*
	*/
	public function select($name, $options = array(), $attributes = array())
	{
		$type = null;
		if(isset($attributes['type']))
		{
			$type = $attributes['type'];
			unset($attributes['type']);
		}

		$optattribs = null;
		if(isset($attributes['options']))
		{
			foreach($attributes['options'] as $key => $value)
				$optattribs .= $key.'="'.$value.'" ';
			unset($attributes['options']);
		}

		$empty = null;
		if(isset($attributes['empty']))
		{
			$empty = $attributes['empty'];
			unset($attributes['empty']);
		}

		$attribs = null;
		foreach($attributes as $key => $value)
			$attribs .= $key.'="'.$value.'" ';
		
		if($type)
			$select = sprintf($this->tags['selectmultiplestart'], $name, $attribs);
		else
			$select = sprintf($this->tags['selectstart'], $name, $attribs);

		if($empty)
			$select .= sprintf($this->tags['selectempty'], $optattribs, $empty);

		foreach($options as $key => $value)
			$select .= sprintf($this->tags['selectoption'], $key, $optattribs, $value);

		$select .= sprintf($this->tags['selectend']);

		return $select;
	}

	public function submit($options = array())
	{
		if(!isset($options['value']))
			$options['value'] = 'Envoyer';

		$attributes = null;
		foreach($options as $key => $value)
			$attributes .= $key.'="'.$value.'" ';

		return sprintf($this->tags['submit'], $attributes);
	}
}

?>