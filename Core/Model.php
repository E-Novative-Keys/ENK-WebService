<?php
/**
* Classe Model parente
*/
class Model
{
	public static $connections 	= array();
	public $config;
	public $table = false;
	public $db;
	public $primaryKey = 'id';
	public $id;
	public $form;
	public $validate = array();

	public function __construct()
	{
		$this->config = (PROD) ? 'prod' : 'dev';

		// Si le nom de la table a utilisé n'est pas renseigné alors on utilise une convention
		// Convention : nom du model en minuscules au pluriel (ex pour class User extends Model : $this->table = 'users')
		if($this->table === false)
			$this->table = strtolower(get_class($this)).'s';

		// Vérification pour ne pas se connecter lors de l'accès a un fichier .ini
		if(!is_array($this->table))
		{
			// Récupération des informations de connexion à la bdd
			$config = DBConfig::$databases[$this->config];

			// Si une connexion a déjà été effectuée, on la récupère
			if(isset(Model::$connections[$this->config]))
			{
				$this->db = Model::$connections[$this->config];
				return true;
			}

			// Sinon on essai de ce connecter à la bdd
			try
			{
				$pdo = new PDO(
					$config['dbtype'].':host='.$config['host'].';dbname='.$config['database'].';',
					$config['login'],
					$config['password'],
					array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$config['encoding'])
				);

				//Gestion d'erreurs par émission d'Exceptions
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

				Model::$connections[$this->config] = $pdo;
				$this->db = $pdo;
			}
			// En cas d'échec de la connexion...
			catch(PDOException $e)
			{
				if(DEBUG)
					die($e);
				else
					die('Erreur lors de la connexion à la base de données.');
			}
		}
		else
		{
			if(count($this->table) == 2)
			{
				if(($dir = constant(strtoupper($this->table[0]."_FILES"))) != null)
				{
					if(!file_exists($dir.$this->table[1].".".$this->table[0]))
					{
						$file = fopen($dir.$this->table[1].".".$this->table[0], "w+");
						fclose($file);
					}
				}			
			}	
		}
	}

	/**
	* Validation des données
	*
	* Rules possibles :
	* 'notEmpty', 'email', regex, array('between', min, max), array('min', min), array('max', max)
	*/
	public function validates($data)
	{
		$data = $data[get_class($this)];
		$errors = array();

		foreach($this->validate as $key => $value)
		{
			foreach($value as $k => $v)
			{
				if(!isset($data[$key]))
					$errors[$key] = $v['message'];
				else
				{
					if($v['rule'] == 'notEmpty')
					{
						if(empty($data[$key]))
						{
							$errors[$key] = $v['message'];
							break;
						}
					}
					else if($v['rule'] == 'email')
					{
						if(!preg_match(
						'/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/',
						$data[$key]))
							$errors[$key] = $v['message'];
					}
					else if(is_array($v['rule']))
					{
						if($v['rule'][0] == 'between')
						{
							if(strlen($data[$key]) < $v['rule'][1]
							|| strlen($data[$key]) > $v['rule'][2])
								$errors[$key] = $v['message'];
						}
						else if($v['rule'][0] == 'min')
						{
							if(strlen($data[$key]) < $v['rule'][1])
								$errors[$key] = $v['message'];
						}
						else if($v['rule'][0] == 'max')
						{
							if(strlen($data[$key]) > $v['rule'][1])
								$errors[$key] = $v['message'];
						}
						else if($v['rule'][0] == 'inArray')
						{
							if(!in_array($data[$key], $v['rule'][1]))
								$errors[$key] = $v['message'];
						}
					}
					else if(!preg_match('/^'.$v['rule'].'$/', $data[$key]))
						$errors[$key] = $v['message'];
				}
			}
		}

		if(isset($this->Form))
			$this->Form->errors = $errors;
		
		if(empty($errors))
			return true;

		return false;
	}

	/**
	* Utilisation de la méthode find :
	*
	* $this->ModelName->find(array(
	* 	fields 		=> array('ModelName.id', 'ModelName.name'),
	*	conditions 	=> array('ModelName.id <>' => 0, 'ModelName.name' => 'test'),
	*	order 		=> 'ModelName.id DESC',
	*	limit 		=> 5,
	*	join 		=> array(array('type' => 'left', 'categories as Category'=>'Category.id=Post.category_id'),
	*						 array('type' => 'right outer', 'categories as Category'=>'Category.id=Post.category_id'),
	*						 array('categories as Category'=>'Category.id=Post.category_id'))
	* ));
	*/
	public function find($request = array(), $fetch = PDO::FETCH_OBJ)
	{
		$sql = 'SELECT ';

		// Sélection de champs spécifiques ou de tous les champs
		if(isset($request['fields']))
		{
			if(is_array($request['fields']))
			{
				$i = 0;
				
				foreach ($request['fields'] as $value)
				{
					$sql .= $value;
					$sql .= (++$i == count($request['fields'])) ? '' : ',';
				}
			}
			else
				$sql .= $request['fields']; 
		}
		else
			$sql .= '*';

		$sql .= ' FROM '.$this->table.' AS '.get_class($this).' ';

		// Jointure
		if(isset($request['join']) && is_array($request['join']))
		{
			$joins = array();

			for($i = 0 ; $i < count($request['join']) ; $i++)
			{
				$joinType = 'LEFT JOIN ';
				$joinStr = '';

				foreach($request['join'][$i] as $key => $value)
				{
					if($key == 'type')
						$joinType = strtoupper($value).' JOIN ';
					else
					{
						$keys 		= explode('.', $key);
						$keyTable 	= strtolower($keys[0]).'s';

						/*/Si la chaîne $sql ne se termine pas par l'alias de notre table, on l'ajoute au FROM
						if(strpos($sql, $keys[0], strlen($sql) - strlen($keys[0])) === false)
							$sql .= ", $keyTable AS $keys[0] ";*/

						$joinStr 	.= $keyTable.' AS '.$keys[0].' ON '.$value;
					}
				}

				$joins[] = $joinType.$joinStr;
			}

			$sql .= implode(' ', $joins).' ';
		}

		// Construction de la condition
		if(isset($request['conditions']))
		{
			$sql .= 'WHERE ';

			// Si une seule condition
			if(!is_array($request['conditions']))
				$sql .= $request['conditions']; 
			// Si tableau de conditions
			else
			{
				$cond = array();

				foreach($request['conditions'] as $key => $value)
				{
					// Ajout des "" si la valeur n'est pas numérique et echape les '' de la chaine
					if(!is_numeric($value) && !strstr($key, 'IN') && $value != null)
						// $value = '"'.mysqli_real_escape_string($this->db, $value).'"';
						$value = $this->db->quote($value);

					if(strstr($key, '='))
					{
						$key = str_replace(" =", "", $key);
						$cond[] = "$key=$value";
					}
					else if(strstr($key, '<>'))
					{
						$key = str_replace(" <>", "", $key);
						$cond[] = "$key<>$value";
					}
					else if(strstr($key, 'IN'))
						$cond[] = "$key $value";
					else if($value === null)
						$cond[] = "$key IS NULL";
					else
						$cond[] = "$key=$value";
				}
				
				$sql .= implode(' AND ', $cond);
			}

		}

		if(isset($request['order']))
			$sql .= ' ORDER BY '.$request['order'];


		if(isset($request['limit']))
			$sql .= ' LIMIT '.$request['limit'];

		if(isset($request['like']))
			$sql .= ' AND '.$request['like'][0].' LIKE "'.$request['like'][1].'"';

		try
		{
			$pre = $this->db->prepare($sql); 
			$pre->execute(); 
			
			return $pre->fetchAll($fetch);
		}
		catch(PDOException $e)
		{
			if(DEBUG)
				die($e);
			else
				die('Erreur lors de la récupération d\'informations dans la base de données.');
		}
	}

	/**
	* Trouve le premier enregistrement
	*/
	public function findFirst($request = null, $fetch = PDO::FETCH_OBJ)
	{
		return current($this->find($request, $fetch));
	}

	/**
	* Nombre d'enregistrements
	*/
	public function findCount($conditions = null, $fetch = PDO::FETCH_OBJ)
	{
		$result = $this->findFirst(array(
			'fields' => 'COUNT('.$this->primaryKey.') as count',
			'conditions' => $conditions
		), $fetch);

		return $result->count;  
	}

	/**
	* Permet de récupérer un tableau indexé par primaryKey et avec name pour valeur
	**/
	public function findList($request = array(), $fetch = PDO::FETCH_OBJ)
	{
		if(!isset($request['fields']))
			$request['fields'] = $this->primaryKey.',name';

		$data = $this->find($request, $fetch);
		$list = array();

		foreach($data as $key => $value)
			$list[current($value)] = next($value);

		return $list; 
	}

	/**
	* Sauvegarde de données par INSERT et UPDATE
	*
	* Utilisation de la méthode save :
	* 
	* $this->ModelName->save(array('name' => 'test', ...));
	* $this->ModelName->save($this->request->data); data envoyées en POST
	*/
	public function save($data)
	{
		// Préparation de la requête dans le tableau fields : nom = :nom
		// Stockage des données dans le tableau values
		foreach($data as $k => $v)
		{
			$fields = array();
			$values = array();
			$primaryFound = false;

			foreach($v as $key => $value)
			{
				if($key != $this->primaryKey)
				{
					$fields[] 			= "`$key`=:$key";
					$values[":$key"] 	= $value; 
				}
				elseif(!empty($value))
				{
					$values[":$key"] 	= $value;
					$primary 			= $key;
					$primaryFound 		= true;
				}
			}

			// Update ou Insert
			if($primaryFound)
				$sql = 'UPDATE '.strtolower($k).'s'.' SET '.implode(',', $fields).' WHERE '.$primary.'=:'.$primary;
			else
				$sql = 'INSERT INTO '.strtolower($k).'s'.' SET '.implode(',', $fields);

			try
			{
				$pre = $this->db->prepare($sql); 
				return $pre->execute($values);
			}
			catch(PDOException $e)
			{
				if(DEBUG)
					die($e);
				else
					die('Erreur lors de l\'écriture d\'informations dans la base de données.');
			}
		}
	}

	/**
	* Suppression d'un enregistrement
	*/
	public function delete($id)
	{
		$sql = "DELETE FROM ".$this->table." WHERE ".$this->primaryKey." = ".$id;
		
		try
		{
			return $this->db->query($sql); 
		}
		catch(PDOException $e)
		{
			if(DEBUG)
				die($e);
			else 
				die('Erreur lors de la suppression d\'informations dans la base de données.');
		}
	}

	/**
	* Recherche d'enregistrements dans un fichier .ini
	* Utilisation :
	* Création d'un model, $this->table = array('ini', 'path ini file');
	*
	* Utilisation de la méthode findIni :
	*
	* $this->ModelName->findIni(array(
	* 	fields 		=> array('id', 'name'),
	*	conditions 	=> array('id <>' => 0, 'name' => 'test'),
	*	order 		=> 'id DESC',
	*	limit 		=> 5
	* ));
	*/
	public function findIni($request = array())
	{
		if(is_array($this->table)   && count($this->table) == 2
		&& $this->table[0] == 'ini' && file_exists(INI_FILES.$this->table[1].'.ini'))
		{
			$ini 	= parse_ini_file(INI_FILES.$this->table[1].'.ini', true);
			$fields = $ini;

			if(isset($request['conditions']))
			{
				$different = false;

				foreach($request['conditions'] as $key => $value)
				{
					if(strstr($key, '<>'))
					{
						$different = true;
						$key = trim(str_replace('<>', '', $key));
					}
					if($key == 'id')
					{
						foreach ($fields as $id => $values)
						{
							if($different)
							{
								if($id == $value)
									unset($fields[$id]);
							}
							else
							{
								if($id != $value)
									unset($fields[$id]);
							}
						}
					}
					else
					{
						foreach($fields as $id => $values)
						{
							if(array_key_exists($key, $values))
							{
								foreach($values as $k => $v)
								{
									if($k == $key)
									{
										if($different)
										{
											if($v == $value)
												unset($fields[$id]);
										}
										else
										{
											if($v != $value)
												unset($fields[$id]);
										}
									}
								}
							}
							else
								return null;
						}
					}
				}
			}
			if(empty($fields))
				return null;
			else
			{
				$found = array();

				foreach($fields as $key => $value)
				{
					$found[] = array();

					if(isset($request['fields']))
					{
						for($i = 0 ; $i < count($request['fields']) ; $i++)
						{
							if($request['fields'][$i] == 'id')
								$found[count($found)-1]['id'] = $key;
							else if(array_key_exists($request['fields'][$i], $value))
							{
								foreach($value as $k => $v)
								{
									if($request['fields'][$i] == $k)
										$found[count($found)-1][$k] = $v;
								}
							}
							else
								return null;
						}
					}
					else
					{
						$found[count($found)-1]['id'] = $key;

						foreach($value as $k => $v)
							$found[count($found)-1][$k] = $v;
					}
				}
				if(!empty($found))
				{
					if(isset($request['order']))
					{
						$orderNeeds = explode(" ", $request['order']);
						if(count($orderNeeds) == 2)
							$found = $this->array_sort($found, $orderNeeds[0], $orderNeeds[1]);
					}
					if(isset($request['limit']))
						$found = array_slice($found, 0, $request['limit']);

					return $found;
				}
				else
					return null;
			}
		}
		return null;
	}

	/**
	* Trouve le premier enregistrement du fichier Ini
	*/
	public function findFirstIni($request = null)
	{
		return current((object)$this->findIni($request));
	}

	/**
	* Sauvegarde de données par UPDATE ou INSERT dans un fichier .ini
	* Utilisation :
	* $this->ModelName->saveIni($this->request->data);
	*/
	public function saveIni($data)
	{
		if(is_array($this->table)   && count($this->table) == 2
		&& $this->table[0] == 'ini' && file_exists(INI_FILES.$this->table[1].'.ini'))
		{
			$ini 		= parse_ini_file(INI_FILES.$this->table[1].'.ini', true);
			$data 		= $data[get_class($this)];
			$infos 		= array();
			$primary 	= -1;

			foreach($data as $key => $value)
			{
				if($key == 'id')
					$primary = $value;
				$infos[$key] = $value;
			}

			if($primary != -1)
			{
				foreach($ini as $id => $values)
				{
					if($id == $primary)
					{
						foreach($values as $k => $v)
						{
							if(isset($infos[$k]))
								$ini[$id][$k] = $infos[$k];
						}
					}
				}
			}
			else
			{
				if($ini == array())
					$ini[1] = $infos;
				else
					array_push($ini, $infos);
			}

			return $this->writeIni(INI_FILES.$this->table[1].'.ini', $ini);
		}
		return null;
	}

	private function writeIni($path, $ini = array())
	{
		$content = "";

		if(!$file = fopen($path, 'w'))
			return false;  

		foreach($ini as $key => $values)
		{
			$content .= "\r\n[$key]\r\n"; 
			foreach($values as $k => $v)
			{
				if(is_array($v))
				{
					for($i = 0; $i < count($v); $i++)
						$content .= $k."[] = \"$v[$i]\"\r\n";
				}
				else if($v == "")
					$content .= "$k = \r\n"; 
				else
					$content .= "$k = \"$v\"\r\n"; 
			}
		}

		$success = fwrite($file, $content);
		fclose($file); 

		return $success; 
	}

	/**
	* Suppression d'un enregistrement dans un fichier .ini
	*/
	public function deleteIni($id)
	{
		if(is_array($this->table) && count($this->table) == 2
		&& $this->table[0] == 'ini' && file_exists(INI_FILES.$this->table[1].'.ini'))
		{
			$ini = parse_ini_file(INI_FILES.$this->table[1].'.ini', true);
			
			foreach($ini as $key => $values)
			{
				if($key == strval($id))
					unset($ini[$key]);
			}
			
			return $this->writeIni(INI_FILES.$this->table[1].'.ini', $ini);
		}
		return null;
	}

	private function array_sort($array, $orderby, $order = "ASC")
	{
		$sortArray = array();

		foreach($array as $element)
		{
			foreach($element as $key => $value)
			{
				if(!isset($sortArray[$key]))
					$sortArray[$key] = array();
				$sortArray[$key][] = $value;
			}
		}

		if($order == "ASC")
			array_multisort($sortArray[$orderby], SORT_ASC, $array);
		else
			array_multisort($sortArray[$orderby], SORT_DESC, $array);

		return $array;
	}
}

?>