<?php

// Connexions WEB et JAVA
Router::connect('/connect',
	array('controller' => 'users', 'action' => 'login')
);

Router::connect('/disconnect',
	array('controller' => 'users', 'action' => 'logout')
);

// Service Cloud WEB et JAVA
Router::connect('/cloud/files/:user', 
	array('controller' => 'cloud','action' => 'listFiles'),
	array('user' => 'client|dev')
);

Router::connect('/cloud/files/add',
	array('controller' => 'cloud', 'action' => 'addFile')
);

Router::connect('/cloud/folder/add',
	array('controller' => 'cloud', 'action' => 'addFolder')
);

Router::connect('/cloud/files/rename',
	array('controller' => 'cloud', 'action' => 'renameFile')
);

Router::connect('/cloud/files/delete',
	array('controller' => 'cloud', 'action' => 'deleteFiles')
);

Router::connect('/cloud/comment',
	array('controller' => 'cloud', 'action' => 'commentFile')
);

Router::connect('/cloud/files/download:token',
	array('controller' => 'cloud', 'action' => 'download'),
	array('token' => '|/[a-z0-9]+')
);

// Notifications WEB
Router::connect('/cloud/notifications',
	array('controller' => 'notifications', 'action' => 'listNotifications')
);

Router::connect('/cloud/notifications/delete',
	array('controller' => 'notifications', 'action' => 'deleteNotification')
);

// Mailbox WEB et JAVA
Router::connect('/mailbox',
	array('controller' => 'mails', 'action' => 'listMails')
);

Router::connect('/mailbox/new',
	array('controller' => 'mails', 'action' => 'newMail')
);

Router::connect('/mailbox/sent',
	array('controller' => 'mails', 'action' => 'sentMail')
);

Router::connect('/mailbox/delete',
	array('controller' => 'mails', 'action' => 'deleteMail')
);

// Gestion des clients JAVA
Router::connect('/clients',
	array('controller' => 'clients', 'action' => 'listClients')
);

Router::connect('/clients/new',
	array('controller' => 'clients', 'action' => 'addClient')
);

Router::connect('/clients/edit',
	array('controller' => 'clients', 'action' => 'editClient')
);

Router::connect('/clients/update',
	array('controller' => 'clients', 'action' => 'updateClient')
);

Router::connect('/clients/delete',
	array('controller' => 'clients', 'action' => 'deleteClient')
);

// Gestion des users JAVA
Router::connect('/users',
	array('controller' => 'users', 'action' => 'listUsers')
);

Router::connect('/users/new',
	array('controller' => 'users', 'action' => 'addUser')
);

Router::connect('/users/edit',
	array('controller' => 'users', 'action' => 'editUser')
);

Router::connect('/users/update',
	array('controller' => 'users', 'action' => 'updateUser')
);

Router::connect('/users/delete',
	array('controller' => 'users', 'action' => 'deleteUser')
);

Router::connect('/users/token',
	array('controller' => 'users', 'action' => 'generateToken')
);

Router::connect('/users/validate',
	array('controller' => 'users', 'action' => 'validateToken')
);

// Vérification présence d'utilisateurs en BDD JAVA
Router::connect('/verify',
	array('controller' => 'users', 'action' => 'verify')
);

// Gestion des Projets JAVA
Router::connect('/projects',
	array('controller' => 'projects', 'action' => 'listProjects')
);

Router::connect('/projects/new',
	array('controller' => 'projects', 'action' => 'addProject')
);

Router::connect('/projects/edit',
	array('controller' => 'projects', 'action' => 'editProject')
);

Router::connect('/projects/delete',
	array('controller' => 'projects', 'action' => 'deleteProject')
);

Router::connect('/projects/current',
	array('controller' => 'projects', 'action' => 'currentProjects')
);

Router::connect('/projects/assign',
	array('controller' => 'projects', 'action' => 'assignmentDevelopper')
);

Router::connect('/projects/unassign',
	array('controller' => 'projects', 'action' => 'unassignmentDevelopper')
);

Router::connect('/projects/quotation',
	array('controller' => 'projects', 'action' => 'quotation')
);

// Gestion des tâches pour un projet défini JAVA
Router::connect('/macrotasks',
	array('controller' => 'tasks', 'action' => 'listMacrotasks')
);

Router::connect('/macrotasks/new',
	array('controller' => 'tasks', 'action' => 'addMacrotask')
);

Router::connect('/macrotasks/edit',
	array('controller' => 'tasks', 'action' => 'editMacrotask')
);

Router::connect('/macrotasks/delete',
	array('controller' => 'tasks', 'action' => 'deleteMacrotask')
);

Router::connect('/tasks/',
	array('controller' => 'tasks', 'action' => 'listTasks')
);

Router::connect('/tasks/new',
	array('controller' => 'tasks', 'action' => 'addTask')
);

Router::connect('/tasks/edit',
	array('controller' => 'tasks', 'action' => 'editTask')
);

Router::connect('/tasks/delete',
	array('controller' => 'tasks', 'action' => 'deleteTask')
);

Router::connect('/macrotasksusers/',
	array('controller' => 'tasks', 'action' => 'listUserMacrotask')
);

Router::connect('/macrotasksusers/new',
	array('controller' => 'tasks', 'action' => 'addUserMacrotask')
);

Router::connect('/macrotasksusers/edit',
	array('controller' => 'tasks', 'action' => 'editUserMacrotask')
);

?>