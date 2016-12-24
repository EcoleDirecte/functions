<?php

/**
 * 
 * Fonctions définies dans ce fichier : (\logs\xxxx())
 *
 *	- \logs\save : prend en $type le type de logs à sauver (connexion ecole directe, demande de l'agenda...) et les données ($data);
 *
 *	- 
 *
 *	- 
 *
 *	- 
 *
 *	- 
 *
 *
**/

namespace logs;

	function EDLogin($data, $tbl) {
		
		dbInsert($tbl, $data);
		
	}

	function save($type='', $data='') {
		
/* 		switch($type) {
			
			case 'EDLogin':
				EDLogin($data);
				break;
			
		} */
		
		$tbl = 'logs_' . $type;
		$type = '\logs\\' . $type;
		
		$type($data, $tbl);
		
	}

?>	