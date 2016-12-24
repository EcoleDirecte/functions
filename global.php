<?php

session_start();

// Variable qui commence par "f_" = variable contenant le chemin exact vers un fichier
// Variable qui commence par "p_" = variable contenant le chemin exact vers un dossier

/**
 * 
 * Fonctions définies dans ce fichier :
 *
 *	- dbCleaner : lui passer en paramètre le mot / expression à nettoyer, ainsi que le type de BDD (à priori, 'mysql') et la fonction retournera
 *		la même expression, avec un encodage des caractères spéciaux
 *
 *  - db_connect : permet se connecter à la BDD directement via db_connect(). Normalement elle n'est utilisée que dans la foncion db_query().
 *
 *  - db_query : on lui passe en paramètre la requête à effectuer (db_query('requete' ou $requete)) et elle se connecte via db_connect puis
 *		effectue la requête
 *
 *  - dbInsert() : insère la valeur retournée par dbCleaner dans la BDD.
 *		Lui passer un array avec 'colonne=>valeur' en 2e paramètre
 *
 *	- GetFilenameFromHeader : à utiliser comme valeur de 'CURLOPT_HEADERFUNCTION'; retourne le "filename" contenu dans le header
 *		dans la variable $GetFilenameFromHeaderResult
 *
 *	- doAction : Exécute l'action spécifiée
 *
 *	- GetFileIDs : Récupère les données des éventuels fichiers présents sur EcoleDirecte au jour spécifié en paramètre
 *
 *	- Mp3_Meta_Data : Retourne les métadonnées contenues dans un fichier mp3
 *
**/

setlocale(LC_TIME, 'fr-FR', 'french', 'fra');
DEFINE('HOMEDIR', '/home/u769009388/');
DEFINE('ROOT', HOMEDIR . 'public_html/');
DEFINE('ROOT_FILES', ROOT . 'files/');
DEFINE('EC_FILES', ROOT_FILES . 'EC_files/');
DEFINE('FILES', HOMEDIR . 'files/');
DEFINE('INC', HOMEDIR . 'inc/');
DEFINE('PROD', ROOT . 'prod/');
DEFINE('PRODEC', PROD . 'ec/');
DEFINE('PCHARTDIR', INC . 'pChart/');
DEFINE('f_ED_DISPLAY_ALL', PRODEC . 'DisplayAll.php');

DEFINE('DATE_d', date('d'));
DEFINE('DATE_n', date('n'));
DEFINE('DATE_Y', date('Y'));
DEFINE('DATE_TODAY', date('Y-m-d'));

require_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/global_variables.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/analyticstracking.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/GestionDates.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/GestionComm.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/GestionLogs.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/global_ED.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/global_CM.php');


/* function curlUse ($cuUrl, $cuType='GET', $cuCookieReset='', ) {
	
						$eclOptions=array(
						  CURLOPT_URL            => "https://vm-api.ecoledirecte.com/v3/login.awp", //
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_FOLLOWLOCATION => false,
						  CURLOPT_HEADER         => false,
						  CURLOPT_FAILONERROR    => false,
						  CURLOPT_POST           => true, //
						  CURLOPT_COOKIESESSION  => 1, //
						  CURLOPT_COOKIEJAR      => $cookie,
						  CURLOPT_COOKIEFILE     => $cookie,
						  CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0)', 
						  CURLOPT_POSTFIELDS     => $postLoginFields //
					);
	
} */

function GetUrl($path) {
	return str_replace(ROOT, 'http://' . $_SERVER['HTTP_HOST'] . '/', $path);
}

function GetFilenameFromHeader($curl, $str) {
	global $GetFilenameFromHeaderResult;
	if(strstr($str, 'filename=')) {
		
		$GetFilenameFromHeaderResult = strstr($str, 'filename=');
		$GetFilenameFromHeaderResult = strstr($GetFilenameFromHeaderResult, '"');
		$nbToShrink = strripos($GetFilenameFromHeaderResult, '"');
		$GetFilenameFromHeaderResult = substr($GetFilenameFromHeaderResult, 0, $nbToShrink); $GetFilenameFromHeaderResult = substr($GetFilenameFromHeaderResult, 1);
		
	}
	return strlen($str);
}

function makeFtp($dir) {
	
	return str_replace(HOMEDIR, '/', $dir);
	
}

function doAction($action, $options='') {
	
	switch($action) {
		
		case 'GetFileIDs':
			// include(PRODEC . 'GetFileIDs.php');
			GetFileIDs($options);
			break;
		
		case 'DisplayAgenda':
			// var_dump(EcoleDirecteGetAgenda($options['fdate']));
			EcoleDirecteGetAgenda($options['fdate']);
		
	}
	
}

function token() {
	return md5(rand() . uniqid() . rand());
}

function Mp3_Meta_Data($file){
	//Verifie que le fichier existe
	if (! file_exists($file)) return -1;
	
	$metatags_size = array('title'=>30,'artiste'=>30,'album'=>30,
						'year'=>4,'comment'=>30,'genre'=>1);
	$metatags_value=array();
	
	//Positionne sur la partie ou les Tags devraient être
	$id_start=filesize($file)-128;
	$fp=fopen($file,"r");
	fseek($fp,$id_start);
	
	//Verifie qu'il y a un emplacement pour les tags
	if (! fread($fp,3) == "TAG")return -1;
	//Récupère les tags
	foreach($metatags_size as $title => $size)
		echo $metatags_value[$title]=@fread($fp,$size);
	fclose($fp);
	return $metatags_value;
}

?>
