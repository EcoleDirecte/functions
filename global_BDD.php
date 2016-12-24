<?php

/**
 * 
 * Fonctions d�finies dans ce fichier :
 *
 *	- dbCleaner : lui passer en param�tre le mot / expression � nettoyer, ainsi que le type de BDD (� priori, 'mysql') et la fonction retournera
 *		la m�me expression, avec un encodage des caract�res sp�ciaux
 *
 *  - db_connect : permet se connecter � la BDD directement via db_connect(). Normalement elle n'est utilis�e que dans la foncion db_query().
 *
 *  - db_query : on lui passe en param�tre la requ�te � effectuer (db_query('requete' ou $requete)) et elle se connecte via db_connect puis
 *		effectue la requ�te
 *
 *  - dbInsert() : ins�re la valeur retourn�e par dbCleaner dans la BDD.
 *		Lui passer un array avec 'colonne=>valeur' en 2e param�tre
 *
**/

function dbCleaner($arg, $type ='mysql') {
    $arg = trim($arg);

    switch ($type) {
        case 'mysql':
            $arg = htmlspecialchars($arg, ENT_QUOTES);
            break;
        case 'sqlite':
            $arg = SQLite3::escapeString($arg);
            break;
        case 'pg':
            $arg = pg_escape_string($arg);
            break;
        case 'xml':
            $arg = strtr($arg, array('\\' => '\\\\', "'" => "\'", '"' => '\"', "{" => '\{', "}" => '\}', "<" => '\<', ">" => '\>'));
            break;
        case 'json':
            $arg = strtr($arg, array('\\' => '\\\\', '"' => '\"'));
            break;
        default:
            exit('DIE : LittleSecureLib --> dbCleaner | Bad type.');
            break;
    }

    return ($arg);
}

function db_connect($dbUser=DB_USER, $dbPass=DB_PASS, $dbBdd=DB_BDD, $dbHost=DB_HOST) {
	return mysqli_connect($dbHost, $dbUser, $dbPass , $dbBdd);
}

function db_query($global_db_query_query, $mysqlLink='') {
	if(empty($mysqlLink)) $mysqlLink = db_connect();
	$global_db_query_return = mysqli_query($mysqlLink , $global_db_query_query)
		or die ('Erreur '.$global_db_query_query);
	mysqli_close($mysqlLink);
	return $global_db_query_return;
}

function dbInsert($dbInsert_tbl, $dbInsert_arg, $mysqlLink='') {
	if(empty($mysqlLink)) $mysqlLink = db_connect();
	$dbInsert_tbl = trim($dbInsert_tbl); $dbInsert_arg_cln = ''; $dbInsert_arg_val = '';
	
	foreach($dbInsert_arg as $k => &$v) {
		$v = dbCleaner($v, 'mysql');
		$dbInsert_arg_cln = $dbInsert_arg_cln . '' . $k . ', ';
		$dbInsert_arg_val = $dbInsert_arg_val . '\'' . $v . '\', ';
	}
	$dbInsert_arg_cln = substr($dbInsert_arg_cln , '0' , '-2'); $dbInsert_arg_val = substr($dbInsert_arg_val , '0' , '-2');
	
	$dbInsert_query = "INSERT INTO " . $dbInsert_tbl . " (" . $dbInsert_arg_cln . ") VALUES (" . $dbInsert_arg_val . ")";
	return db_query($dbInsert_query, $mysqlLink);
}

?>