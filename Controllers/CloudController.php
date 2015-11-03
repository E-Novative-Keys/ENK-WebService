<?php

class CloudController extends Controller
{
	public function beforeFilter()
	{
		parent::beforeFilter();
		
		$this->Auth->allow('download');

		$this->loadModel('Project');
	}

	public function listFiles($user = null)
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Cloud']['project']) 	|| empty($this->request->data['Cloud']['project'])
		|| !isset($this->request->data['Cloud']['directory']) 	|| empty($this->request->data['Cloud']['directory'])
		|| !in_array($user, array('client', 'dev')))
			die();

		// Chargement de Models
		$this->loadModel('File');

		$id 		= base64_decode($this->request->data['Cloud']['project']);
		$directory 	= base64_decode($this->request->data['Cloud']['directory']);
		$search  	= false;

		// Fichier à rechercher dans la liste
		if(isset($this->request->data['Cloud']['search']))
			$search = $this->request->data['Cloud']['search'];

		// Ajout du / final au dossier s'il est absent
		if(($tmp = strlen($directory) - strlen('/')) >= 0 && strpos($directory, '/', $tmp) === false)
			$directory .= '/';

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('id' => $id)))) == null)
			die();

		$dir = $directory;

		// On récupère le directory adapté au statut de l'authentifié
		if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
			$directory 	= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.DS.$user.$directory;
		else if($this->Auth->user('role') != "client")
			$directory 	= FILES_ROOT.'Cloud'.DS.$project->client_id.DS.$project->id.DS.$user.$directory;
		else
			die();

		$content = array();

		if(!$search)
		{
			$iterator = new DirectoryIterator($directory);

			foreach($iterator as $file)
			{
				if($file->isDot())
					continue;

				// Recherche d'un commentaire attaché au fichier courant
				$comment = $this->File->findFirst(array(
					'fields'		=> array('File.comment'),
					'conditions'	=> array(
						'File.project_id'	=> $id,
						'File.file' 		=> DS.$user.$dir.$file->getFilename()
					)
				));

				$content[] = array(
					"filename" 		=> $file->getFilename(),
					"extension" 	=> $file->getExtension(),
					"mtime" 		=> date("d/m/Y H:i:s", $file->getMTime()),
					"size" 			=> $file->getSize(),
					"isDir" 		=> $file->isDir(),
					"comment"		=> ($comment) ? $comment->comment : ""
				);
			}
		}
		else
		{
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator(realpath($directory), FilesystemIterator::SKIP_DOTS)
			);

			foreach($iterator as $file)
			{
				// Calcul du pourcentage de ressemblance avec le fichier recherché
				similar_text(strtolower($search), strtolower($file->getFilename()), $percent);
				if($percent < 30)
					continue;

				// Recherche d'un commentaire attaché au fichier courant
				$comment = $this->File->findFirst(array(
					'fields'		=> array('File.comment'),
					'conditions'	=> array(
						'File.project_id' 	=> $id,
						'File.file' 		=> DS.$user.$dir.$file->getFilename()
					)					
				));

				$content[] = array(
					"filename" 	=> $file->getFilename(),
					"extension" => $file->getExtension(),
					"mtime" 	=> date("d/m/Y H:i:s", $file->getMTime()),
					"size" 		=> $file->getSize(),
					"isDir" 	=> $file->isDir(),
					"comment"	=> ($comment) ? $comment->comment : ""
				);
			}
		}

		// Tri de la liste : Dossiers ASC, Fichiers ASC
		$fileContent 	= array();
		$dirContent 	= array();

		foreach($content as $key => $row) 
		{
			if($row['isDir'])
				$dirContent[] 	= $row;
			else
				$fileContent[] 	= $row;
		}

		asort($dirContent);
		asort($fileContent);

		$content = array_merge($dirContent, $fileContent);

		$this->set(compact('content'));
	}

	public function addFile()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Cloud']['project']) 	|| empty($this->request->data['Cloud']['project'])
		|| !isset($this->request->data['Cloud']['directory']) 	|| empty($this->request->data['Cloud']['directory'])
		|| empty($this->request->files)
		|| ($this->Auth->user('role') != 'client' && (!isset($this->request->data['Cloud']['user'])
		|| !in_array($this->request->data['Cloud']['user'], array('client', 'dev')))))
			die();

		$id 	= base64_decode($this->request->data['Cloud']['project']);
		$path 	= base64_decode($this->request->data['Cloud']['directory']);

		// Ajout du / final au dossier s'il est absent
		if(($tmp = strlen($path) - strlen('/')) >= 0 && strpos($path, '/', $tmp) === false)
			$path .= '/';

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('id' => $id)))) == null)
			die();

		// On récupère le path adapté au statut de l'authentifié
		// Un client ne peut uploader que dans le dossier client, un développeur peut sur client et dev
		if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
			$path 	= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.DS."client".$path.basename($this->request->files['name']);
		else if($this->Auth->user('role') != "client")
			$path 	= FILES_ROOT.'Cloud'.DS.$project->client_id.DS.$project->id.DS.$this->request->data['Cloud']['user'].$path.basename($this->request->files['name']);
		else
			die();

		if(!move_uploaded_file($this->request->files['tmp_name'], $path))
			$this->set('error', 'File Upload Error');
		else
			$this->set('upload', 'success');
	}

	public function addFolder()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Cloud']['project']) 	|| empty($this->request->data['Cloud']['project'])
		|| !isset($this->request->data['Cloud']['directory']) 	|| empty($this->request->data['Cloud']['directory'])
		|| !isset($this->request->data['Cloud']['name']) 		|| empty($this->request->data['Cloud']['name'])
		|| ($this->Auth->user('role') != 'client' && (!isset($this->request->data['Cloud']['user'])
		|| !in_array($this->request->data['Cloud']['user'], array('client', 'dev')))))
			die();

		$id 		= base64_decode($this->request->data['Cloud']['project']);
		$directory 	= base64_decode($this->request->data['Cloud']['directory']);
		$name 		= $this->request->data['Cloud']['name'];

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('id' => $id)))) == null)
			die();

		// On récupère le directory adapté au statut de l'authentifié
		if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
			$name 	= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.DS."client".DS.$directory.DS.$name;
		else if($this->Auth->user('role') != "client")
			$name 	= FILES_ROOT.'Cloud'.DS.$project->client_id.DS.$project->id.DS.$this->request->data['Cloud']['user'].DS.$directory.DS.$name;
		else
			die();

		if(mkdir($name, 0777))
			$this->set('addFolder', 'success');
		else
			$this->set('error', 'Cannot create a new folder');
	}

	public function renameFile()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Cloud']['project']) 	|| empty($this->request->data['Cloud']['project'])
		|| !isset($this->request->data['Cloud']['directory']) 	|| empty($this->request->data['Cloud']['directory'])
		|| !isset($this->request->data['Cloud']['name'])
		|| !isset($this->request->data['Cloud']['rename']) 		|| empty($this->request->data['Cloud']['rename'])
		|| ($this->Auth->user('role') != 'client' && (!isset($this->request->data['Cloud']['user'])
		|| !in_array($this->request->data['Cloud']['user'], array('client', 'dev')))))
			die();

		$this->loadModel('File');

		$id 		= base64_decode($this->request->data['Cloud']['project']);
		$directory 	= base64_decode($this->request->data['Cloud']['directory']);
		$name 		= $this->request->data['Cloud']['name'];
		$rename 	= $this->request->data['Cloud']['rename'];
		$isDir 		= empty($name); // Si $name vide: rename sur dossier, sinon rename sur fichier
		$relative 	= null;
		$relativeRn	= null;

		if($directory != '/')
			$rename = str_replace($directory, '', $rename);

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('id' => $id)))) == null)
			die();

		// On récupère le directory adapté au statut de l'authentifié
		if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
		{
			if($isDir)
			{
				$args = array_filter(explode(DS, $directory), 'strlen');
				array_pop($args);

				$relative 	= DS."client".$directory;
				$relativeRn = DS."client".DS.implode(DS, $args).DS.$rename;
				$name 		= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relative;
				$rename 	= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relativeRn;
			}
			else
			{
				// Ajout du / final au dossier s'il est absent
				if(($tmp = strlen($directory) - strlen('/')) >= 0 && strpos($directory, '/', $tmp) === false)
					$directory .= '/';

				$relative 	= DS."client".$directory.$name;
				$relativeRn = DS."client".$directory.$rename;
				$name 		= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relative;
				$rename 	= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relativeRn;
			}
		}
		else if($this->Auth->user('role') != "client")
		{
			if($isDir)
			{
				$args = array_filter(explode(DS, $directory), 'strlen');
				array_pop($args);

				$relative 	= DS.$this->request->data['Cloud']['user'].$directory;
				$relativeRn = DS.$this->request->data['Cloud']['user'].DS.implode(DS, $args).DS.$rename;
				$name 		= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relative;
				$rename 	= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relativeRn;
			}
			else
			{
				// Ajout du / final au dossier s'il est absent
				if(($tmp = strlen($directory) - strlen('/')) >= 0 && strpos($directory, '/', $tmp) === false)
					$directory .= '/';

				$relative 	= DS.$this->request->data['Cloud']['user'].$directory.$name;
				$relativeRn = DS.$this->request->data['Cloud']['user'].$directory.$rename;
				$name 		= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relative;
				$rename 	= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relativeRn;
			}
		}
		else
			die();

		if(file_exists($name))
		{
			// Le chemin entier est nécessaire à la fonction rename sinon il y a déplacement
			if(rename($name, $rename) === false)
				$this->set('error', "Cannot rename file $name in $rename");
			else 
			{
				// Changement du nom en bdd si fichier renommé
				$file = $this->File->findFirst(array(
					'conditions' => array('file' => $relative, 'project_id' => $project->id)
				));

				if($file != null && $this->File->save(array("File" => array('id' => $file->id, 'file' => $relativeRn))))
					$file->name = $relativeRn;

				$this->set(compact('file'));
			}
		}
		else
			$this->set('error', 'Unable to find file ' . $name . ' - it doesn\'t exists.');
	}

	public function moveFiles()
	{
		if(!isset($this->request->data['Cloud']['project']) 	|| empty($this->request->data['Cloud']['project'])
		|| !isset($this->request->data['Cloud']['directory']) 	|| empty($this->request->data['Cloud']['directory'])
		|| !isset($this->request->data['Cloud']['file']))
			die();

		$this->loadModel('File');

		$id 		= base64_decode($this->request->data['Cloud']['project']);
		$directory 	= base64_decode($this->request->data['Cloud']['directory']);
		$file 		= $this->request->data['Cloud']['file'];
		$isDir 		= empty($name);

		// Ajout du / final au dossier s'il est absent
		if(($tmp = strlen($directory) - strlen('/')) >= 0 && strpos($directory, '/', $tmp) === false)
			$directory .= '/';

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('id' => $id)))) == null)
			die();
	}

	public function deleteFiles()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Cloud']['project']) 	|| empty($this->request->data['Cloud']['project'])
		|| !isset($this->request->data['Cloud']['directory']) 	|| empty($this->request->data['Cloud']['directory'])
		|| !isset($this->request->data['Cloud']['name'])
		|| ($this->Auth->user('role') != 'client' && (!isset($this->request->data['Cloud']['user'])
		|| !in_array($this->request->data['Cloud']['user'], array('client', 'dev')))))
			die();

		$this->loadModel('File');		
		
		$id 		= base64_decode($this->request->data['Cloud']['project']);
		$directory 	= base64_decode($this->request->data['Cloud']['directory']);
		$name 		= $this->request->data['Cloud']['name'];
		$isDir 		= empty($name); // Si $name vide, on supprime le dossier

		// Ajout du / final au dossier s'il est absent
		if(($tmp = strlen($directory) - strlen('/')) >= 0 && strpos($directory, '/', $tmp) === false)
			$directory .= '/';

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('id' => $id)))) == null)
			die();

		// On récupère le directory adapté au statut de l'authentifié
		if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
		{
			$relative 	= DS."client".$directory.$name;
			$name 		= FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.$relative;
		}
		else if($this->Auth->user('role') != "client")
		{
			$relative 	= DS.$this->request->data['Cloud']['user'].$directory.$name;
			$name 		= FILES_ROOT.'Cloud'.DS.$project->client_id.DS.$project->id.$relative;
		}
		else
			die();

		if(file_exists($name))
		{
			// Suppression du fichier dans le FS
			if(($isDir && ($this->Utils->recursiveRmdir($name) === false)) || (!$isDir && (unlink($name) === false)))
				$this->set('error', 'Cannot remove file $name');
			else 
			{
				// Suppression dans la BDD
				if($isDir)
				{
					if(($files = $this->File->find(array(
						'conditions' 	=> array('File.project_id' => $project->id),
						'like'			=> array('File.file', $relative."%")
					))) != null)
					{
						foreach($files as $file)
							$this->File->delete($file->id);
					}
				}
				else
				{
					if(($file = $this->File->findFirst(array(
						'conditions' => array('File.project_id' => $project->id, 'File.file' => $relative)
					))) != null)
						$this->File->delete($file->id);
				}

				$this->set('file', 'deleted');
			}
		}
		else
			$this->set('error', 'Unable to find file ' . $name . ' - it doesn\'t exists.');
	}

	public function commentFile()
	{
		// Vérification de la présence des bonnes données
		if(!isset($this->request->data['Cloud']['project']) || empty($this->request->data['Cloud']['project'])
		|| !isset($this->request->data['Cloud']['file']) 	|| empty($this->request->data['Cloud']['file'])
		|| !isset($this->request->data['Cloud']['comment']) || empty($this->request->data['Cloud']['comment'])
		|| ($this->Auth->user('role') != 'client' && (!isset($this->request->data['Cloud']['user'])
		|| !in_array($this->request->data['Cloud']['user'], array('client', 'dev')))))
			die();

		$this->loadModel('File');

		$id 		= base64_decode($this->request->data['Cloud']['project']);
		$filename 	= base64_decode($this->request->data['Cloud']['file']);
		$comment 	= htmlspecialchars($this->request->data['Cloud']['comment'], ENT_QUOTES);

		// Si le projet n'existe pas on die
		if(($project = $this->Project->findFirst(array('conditions' => array('Project.id' => $id)))) == null)
			die();

		// On récupère le filename adapté au statut de l'authentifié
		if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
			$filename = DS."client".$filename;
		else if($this->Auth->user('role') != "client")
			$filename = DS.$this->request->data['Cloud']['user'].$filename;
		else
			die();

		if(($file = $this->File->findFirst(array('conditions' => array('File.file' => $filename)))) != null)
			$this->File->save(array('File' => array('id' => $file->id, 'comment' => $comment)));
		else
		{
			$data = array(
				'File' => array(
					'project_id'	=> $project->id,
					'file'			=> $filename,
					'comment' 		=> $comment,
					'created'		=> date('Y-m-d H:i:s')
				)
			);

			$this->File->save($data);
		}

		$this->set('comment', true);
	}

	public function download($token = null)
	{
		$this->loadModel('Download');
			
		if(!$token)
		{
			// Vérification de la présence des bonnes données
			if(!isset($this->request->data['Cloud']['project']) || empty($this->request->data['Cloud']['project'])
			|| !isset($this->request->data['Cloud']['path']) 	|| empty($this->request->data['Cloud']['path'])
			|| !isset($this->request->data['Cloud']['user']) 	|| empty($this->request->data['Cloud']['user']))
				die();

			$id 	= base64_decode($this->request->data['Cloud']['project']);
			$file 	= base64_decode($this->request->data['Cloud']['path']);
			$user 	= $this->request->data['Cloud']['user'];

			// Si le projet n'existe pas on die
			if(($project = $this->Project->findFirst(array('conditions' => array('Project.id' => $id)))) == null)
				die();

			// On récupère le path adapté au statut de l'authentifié
			if($this->Auth->user('role') == "client" && $project->client_id == $this->Auth->user('id'))
				$path = FILES_ROOT.'Cloud'.DS.$this->Auth->user('id').DS.$project->id.DS.$user.$file;
			else if($this->Auth->user('role') != "client")
				$path = FILES_ROOT.'Cloud'.DS.$project->client_id.DS.$project->id.DS.$user.$file;
			else
				die();

			if(!file_exists($path))
				die();
			
			$token = $this->Auth->password($_SERVER['REMOTE_ADDR'].date('Y-m-d H:i:s'));

			$data = array(
				"Download" 	=> array(
					"file" 	=> DS.$this->Auth->user('id').DS.$project->id.DS.$user.$file,
					"token"	=> $token
				)
			);

			$this->Download->save($data);

			$this->set('token', $token);
		}
		else
		{
			if(($download = $this->Download->findFirst(array('conditions' => array('Download.token' => $token)))) != null)
			{
				$this->Download->delete($download->id);

				$path = FILES_ROOT.'Cloud'.$download->file;

				$ext = strtolower(substr(strrchr($download->file,"."), 1));

				switch($ext)
				{
					case "pdf": $ctype 	= "application/pdf"; break;
					case "exe": $ctype 	= "application/octet-stream"; break;
					case "zip": $ctype 	= "application/zip"; break;
					case "doc": $ctype 	= "application/msword"; break;
					case "xls": $ctype 	= "application/vnd.ms-excel"; break;
					case "ppt": $ctype 	= "application/vnd.ms-powerpoint"; break;
					case "gif": $ctype 	= "image/gif"; break;
					case "png": $ctype 	= "image/png"; break;
					case "jpeg":
					case "jpg": $ctype 	= "image/jpg"; break;
					default: $ctype 	= "application/force-download";
				}
			
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
				header("Cache-Control: private", false);
				header("Content-Description: File Transfer");
				header("Content-Type: $ctype");
				header("Content-Disposition: attachment; filename=\"".basename($path)."\";");
				header("Content-Transfer-Encoding: binary");
				header("Content-Length: ".filesize($path));

				ob_clean();
		    	flush();
				
				readfile($path);

				$this->set('escapeJson', true);
			}
		}
	}
}

?>