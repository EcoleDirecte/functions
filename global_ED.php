<?php

/**
 * 
 * Fonctions définies dans ce fichier :
 *
 *	- EcoleDirecteLogin : Donner en paramètre (identifiant, mot de passe) de connexion Ã  Ecole Directe,
 *
 *	- EcoleDirecteGetAgenda : Récupère le contenu de l'agenda au format brut. Prend en paramètre date(obligatoire, format AAAA-MM-JJ),
 * 		identifiant E.D (par défaut, mon identifiant), mot de passe E.D. (par défaut le mien également)
 *
 *	- EcoleDirecteSaveNewFileInDB : xxx
 *
 *	- EDDisplayAll : Affiche tous les fichiers enregistrés dans la base de données pour un id d'utilisateur donné
 *
 *	- EcoleDirecteSaveNewFileOnDD : nécessite :
 *		[EcoleDirecte token], 
 *		[id du fichier], 
 *		[nom du fichier]  et 
 *		[type] (par défaut, FICHIER_CDT)
 *		Télécharge le fichier depuis Ecole Directe et l'enregistre dans FILES (public_html/../files/), puis l'envoie par FTP sur bidoutk.free.fr
 *
 *	- parseDateForSC : Retourne toutes les dates entre lim1 et lim2, au format AAAA-MM-JJ, dans l'année scolaire
 *
 // *	- doAction : Exécute l'action spécifiée
 *
 // *	- GetFileIDs : Récupère les données des éventuels fichiers présents sur EcoleDirecte au jour spécifié en paramètre
 *
**/

function EcoleDirecteLogin($eclLogin, $eclPass) {
					
					$cookie = '/tmp/.ecl.cookie';
             $postLoginFields= 'data={
    "identifiant": "' . $eclLogin . '",
    "motdepasse": "' . $eclPass . '"
}';
					
					$eclOptions=array(
						  CURLOPT_URL            => "https://vm-api.ecoledirecte.com/v3/login.awp",
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_FOLLOWLOCATION => false,
						  CURLOPT_HEADER         => false,
						  CURLOPT_FAILONERROR    => false,
						  CURLOPT_POST           => true,
						  CURLOPT_COOKIESESSION  => 1,
						  CURLOPT_COOKIEJAR      => $cookie,
						  CURLOPT_COOKIEFILE     => $cookie,
						  CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0)', 
						  CURLOPT_POSTFIELDS     => $postLoginFields
					);
					
					$expire_session_token = false;
					
					if (isset($_SESSION['ED_TIME_TOKEN']) && (time() - $_SESSION['ED_TIME_TOKEN'] > 240)) {
						// last request was more than 4 minutes ago
						// session_unset();     // unset $_SESSION variable for the run-time 
						unset($_SESSION['ED_TIME_TOKEN']);     // unset $_SESSION variable for the run-time 
						unset($_SESSION['ED_token']);     // unset $_SESSION variable for the run-time 
						unset($_SESSION['ED_id']);     // unset $_SESSION variable for the run-time 
						$expire_session_token = true;
						// session_destroy();   // destroy session data in storage
					}
					
					if( (!empty($_SESSION['ED_token'])) AND (!$expire_session_token) ) {
						
						$eclReturn = array(
							'cookie' => $cookie,
							'options' => $eclOptions,
							'token' => $_SESSION['ED_token'],
							'id' => $_SESSION['ED_id']
						); return $eclReturn;
						
					}
					
					$eclCurl=curl_init();
					curl_setopt_array($eclCurl,$eclOptions); 
					$eclContent=curl_exec($eclCurl);
					 
					curl_close($eclCurl);
					$eclContentDecoded = json_decode($eclContent, true);
					$eclContentDecoded_data = $eclContentDecoded['data']; $eclContentDecoded_data_accounts = $eclContentDecoded_data['accounts']; $eclContentDecoded_data_accounts_0 = $eclContentDecoded_data_accounts['0'];
					$eclCurl = null;
					$date = date('Y-m-d.H') . 'h' . date('i');
					$logData = array(
						'date' => $date,
						'ip' => $_SERVER['REMOTE_ADDR'],
						'user' => $eclLogin,
						'mdp' => $eclPass,
						'token' => $eclContentDecoded['token'],
						'userId' => $eclContentDecoded_data_accounts_0['id']
					);
					if(!empty($eclContentDecoded['token'])) \logs\save('EDLogin', $logData);
					
					$eclReturn = array(
					'cookie' => $cookie,
					'options' => $eclOptions,
					'token' => $eclContentDecoded['token'],
					'id' => $eclContentDecoded_data_accounts_0['id']
					);
					
					$_SESSION['ED_token'] = $eclContentDecoded['token'];
					$_SESSION['ED_id'] = $eclContentDecoded_data_accounts_0['id'];
					$_SESSION['ED_TIME_TOKEN'] = time();
					
					return $eclReturn;

}

function EcoleDirecteGetAgenda($agendaDate, $eclLogin=EC_CHARLES_LOGIN, $eclPass=EC_CHARLES_PASS) {
					
					$EcoleDirecteLogin = EcoleDirecteLogin($eclLogin, $eclPass);
					$eclaEc_id = $EcoleDirecteLogin['id'];
					$eclaOptions = $EcoleDirecteLogin['options'];
					$eclaCookie = $EcoleDirecteLogin['cookie'];
			
					$postAgendaFields= 'data={
    "token": "' . $EcoleDirecteLogin['token'] . '"
}';
					$eclaUrl = "https://vm-api.ecoledirecte.com/v3/Eleves/" . $eclaEc_id . "/cahierdetexte/" . $agendaDate . ".awp?verbe=get&";
					
					$eclaOptions[CURLOPT_URL] = $eclaUrl;
					$eclaOptions[CURLOPT_POSTFIELDS] = $postAgendaFields;
					unset($eclaOptions[CURLOPT_COOKIESESSION]);
					
						$eclaCurl=curl_init();
             
					curl_setopt_array($eclaCurl,$eclaOptions);
					$eclaContent=array('content' => $content3Decoded = json_decode(curl_exec($eclaCurl), true), 'token' => $EcoleDirecteLogin['token'], 'id' => $eclaEc_id);
					
					return $eclaContent;

}

function AddNewUserToProject() {
	
	
	
}

function parseDateForSC($lim1=REMOVEDAYTODATE, $lim2=ADDDAYTODATE, $action, $notif=true, $ed_logins='') {
			
			$AnneeScolaire = AnneeScolaire(); $continue = true; $display=false;

			// $moisActuel = date('m');
			$moisActuel = substr($lim1, 5, 2);
			$anneeActuelle = date('Y');
			
			if($moisActuel == 12) { $moisSuivant = 01; $anneeSuivante=$anneeActuelle+1; }
			else { $moisSuivant = $moisActuel+1; $anneeSuivante = $anneeActuelle; }
			
			$ed_file_date_limit = $lim2;

			// Dans la table "UsersIDs", s�lectionner toutes les lignes dont la colonne "NotifyNewFile" contient 1, et r�cup�rer les identifiants ED
			
			// $EcoleDirecteGetID = EcoleDirecteLogin(/*, */$eclLogin, $eclPass);
			if(empty($ed_logins)) {
				$ed_logins['login'] = EC_CHARLES_LOGIN;
				$ed_logins['pwd'] = EC_CHARLES_PASS;
			}
			
			$EcoleDirecteGetID = EcoleDirecteLogin($ed_logins['login'], $ed_logins['pwd']);
			
			$EcoleDirecteUserID = $EcoleDirecteGetID['id'];
			$EcoleDirecteToken = $EcoleDirecteGetID['token'];

			$dateFinale = array();
			
			/*
			
				Ici :
					- Recherche dans la table "FILES_users" si l'ID Ecole Directe de l'utilisateur est présent
						- s'il l'est, on continue et quitte la condition
						- s'il n'y est pas, 
							- on créé la table FILES_XXXX dans la BDD
							- on créé le dossier 
							- désactiver les notifs ($notif = false)
			
			*/
			
				/* $querySearch = "SELECT * FROM FILES_users WHERE userId='" . $EcoleDirecteUserID . "'"; $existing = false; $querySearchResult = db_query($querySearch);
				while($row=mysqli_fetch_array($querySearchResult)) {
					$existing = true;
					// echo "Login = " . $row['login'] . "; mot de passe = " . $row['pass'] . "<br />";
					/*
						
						id (seulement pour la BDD)
						login (ecole directe)
						pass (ecole directe)
						userId (identifiant ecole directe)
						uid (sert au croisement de données avec la table UsersIDs)
						
					*/
				/* }
				
				if(!$existing) {
					echo "première utilisation du service<br />";
					
					$createUserTableQuery = "CREATE TABLE IF NOT EXISTS FILES_" . $EcoleDirecteGetID['id'] . " (id INT(11) NOT NULL AUTO_INCREMENT,
					  fileDate VARCHAR(11) DEFAULT NULL,
					  matiereName VARCHAR(255) DEFAULT NULL,
					  name VARCHAR(255) DEFAULT NULL,
					  url VARCHAR(255) DEFAULT NULL,
					  filePlace VARCHAR(20) DEFAULT NULL,
					  ressourceDocuments int(2) NOT NULL DEFAULT '0',
					  fileId INT(11) DEFAULT NULL,
					  fileType VARCHAR(20) DEFAULT NULL,
					  PRIMARY KEY (id))";
					$createUserTableQueryExec = db_query($createUserTableQuery);
					$notif = false;
					$insertData = array(
						
					);
					var_dump(dbInsert("FILES_users" . $userID, $insertData));
			
				} */

 			if($continue)
				
				for($i = $moisActuel; $i<= 12; $i++){
					
					$FindSchoolYear = FindSchoolYear($AnneeScolaire, $i);
					$fmois = strftime('%B', strtotime(date('Y').'-'.$i.'-'.date('d')));
					$monthLenght = MonthLenght($i);
					
					for($ia = 1; $ia<=$monthLenght; $ia++) {
						if(strlen($ia) == 1) $ia = 0 . $ia;
						if(strlen($i) == 1) $i = 0 . $i;
						if($FindSchoolYear)
							$fdate = $FindSchoolYear . '-' . $i . '-' . $ia;
						$boucleDate = $fdate;
						if($ed_file_date_limit == $fdate) { $continue = false; $i=52; break; }
						
						if($fdate == $lim1) $display=true;
						
						if($display) {
							
							var_dump($fdate); echo '<br />';
							$tmpOptions = array(
								'fdate' => $fdate,
								'ed_token' => $EcoleDirecteToken,
								'ed_login' => $ed_logins['login'],
								'ed_pass' => $ed_logins['pwd'],
								'notif' => $notif
							);
							doAction($action, $tmpOptions);
							
						}
					}
					
				}

			if($continue)
				for($i = 1; $i<= 7; $i++){
					
					$FindSchoolYear = FindSchoolYear($AnneeScolaire, $i);
					$fmois = strftime('%B', strtotime(date('Y').'-'.$i.'-'.date('d')));
					$monthLenght = MonthLenght($i);
					
					for($ia = 1; $ia<=$monthLenght; $ia++) {
						if(strlen($ia) == 1) $ia = 0 . $ia;
						if(strlen($i) == 1) $i = 0 . $i;
						if($FindSchoolYear)
							$fdate = $FindSchoolYear . '-' . $i . '-' . $ia;
						if($ed_file_date_limit == $fdate) $continue = false;
						
						if($fdate == $lim1) $display=true;
						
						if($display) {
							
							echo $fdate . '<br />';
							$tmpOptions = array(
								'fdate' => $fdate,
								'ed_token' => $EcoleDirecteToken,
								'ed_login' => $ed_logins['login'],
								'ed_pass' => $ed_logins['pwd'],
								'notif' => $notif
							);
							doAction($action, $tmpOptions);
							
						}
						
					}
					
				}
}

function GetFileIDs($tmpOptions) {

			$ffdate = $tmpOptions['fdate'];
			
            $EcoleDirecteGetAgenda = EcoleDirecteGetAgenda($ffdate, $tmpOptions['ed_login'], $tmpOptions['ed_pass']); // Ã  supprimer après
            $content3 = $EcoleDirecteGetAgenda['content'];
            $content3Decoded = $EcoleDirecteGetAgenda['content'];
			$content3Decoded_data = $content3Decoded['data'];
			$content3Decoded_data_matieres = $content3Decoded_data['matieres']; $content3Decoded_data_matieres_0 = $content3Decoded_data_matieres['0'];
			$date_agenda = $content3Decoded_data['date'];
			
			// var_dump($EcoleDirecteGetAgenda);
			
			foreach($content3Decoded_data_matieres as $k => &$v) {
				
				$content3Decoded_data_matieres_actuelle = $content3Decoded_data_matieres[$k];
				$matiereName = $content3Decoded_data_matieres_actuelle['matiere'];
				$profName = substr($content3Decoded_data_matieres_actuelle['nomProf'] , 5);
				$interro = $content3Decoded_data_matieres_actuelle['interrogation'];
				$aFaire = $content3Decoded_data_matieres_actuelle['aFaire'];
				$contenuSeance = $content3Decoded_data_matieres_actuelle['contenuDeSeance'];
				$aFaire_ressourceDocuments = $aFaire['ressourceDocuments'];
				$aFaire_documents = $aFaire['documents'];
				$ctSeance_ressourceDocuments = $contenuSeance['ressourceDocuments'];
				$ctSeance_documents = $contenuSeance['documents'];
				
				$toDisplay = '';
				
				if(!empty($aFaire)) {
					
					if(!empty($aFaire_documents)) {
						
						foreach($aFaire_documents as $ka => &$va) {
							
							$documentActuel = $aFaire_documents[$ka];
			
							$dataFilesFinal = array(
								'fileDate' => $date_agenda,
								'matiereName' => $matiereName,
								'name' => $documentActuel['libelle'],
								'filePlace' => 'aFaire',
								'fileId' => $documentActuel['id'],
								'ressourceDocuments' => 0,
								'fileType' => $documentActuel['type']
							);
							
							EcoleDirecteSaveNewFileInDB($EcoleDirecteGetAgenda['id'], $dataFilesFinal, $tmpOptions);
							
						}
							
						if(!empty($aFaire_ressourceDocuments)) {
							
							foreach($aFaire_ressourceDocuments as $kba => &$vba) {
								
								$documentActuel = $aFaire_ressourceDocuments[$kba];
			
								$dataFilesFinal = array(
									'fileDate' => date("Y-m-d", strtotime($documentActuel['date'])),
									'matiereName' => $matiereName,
									'name' => $documentActuel['libelle'],
									'filePlace' => 'aFaire',
									'fileId' => $documentActuel['id'],
									'ressourceDocuments' => 1,
									'fileType' => $documentActuel['type']
								);
							
								EcoleDirecteSaveNewFileInDB($EcoleDirecteGetAgenda['id'], $dataFilesFinal, $tmpOptions);
								
							}
							
						}
						
					} else $toDisplay .= '<span style="color:#8ABAE2;">Pas de document pour cette matiÃ¨re ! (catÃ©gorie devoir)</span>';
					
				}
				
				if(!empty($contenuSeance)) {
					
					if(!empty($ctSeance_documents)) {
						
						foreach($ctSeance_documents as $ka => &$va) {
							
							$documentActuel = $ctSeance_documents[$ka];
			
							$dataFilesFinal = array(
								'fileDate' => $date_agenda,
								'matiereName' => $matiereName,
								'name' => $documentActuel['libelle'],
								'filePlace' => 'contenuSeance',
								'fileId' => $documentActuel['id'],
								'ressourceDocuments' => 0,
								'fileType' => $documentActuel['type']
							);
							
							EcoleDirecteSaveNewFileInDB($EcoleDirecteGetAgenda['id'], $dataFilesFinal, $tmpOptions);
							
						}
							
						if(!empty($ctSeance_ressourceDocuments)) {
							
							foreach($ctSeance_ressourceDocuments as $kba => &$vba) {
								
								$documentActuel = $ctSeance_ressourceDocuments[$kba];
			
								$dataFilesFinal = array(
									'fileDate' => date("Y-m-d", strtotime($documentActuel['date'])),
									'matiereName' => $matiereName,
									'name' => $documentActuel['libelle'],
									'filePlace' => 'contenuSeance',
									'fileId' => $documentActuel['id'],
									'ressourceDocuments' => 1,
									'fileType' => $documentActuel['type']
								);
							
								EcoleDirecteSaveNewFileInDB($EcoleDirecteGetAgenda['id'], $dataFilesFinal, $tmpOptions);
								
							}
							
						}
						
					} else $toDisplay .= '<span style="color:#8ABAE2;">Pas de document pour cette matiÃ¨re ! (catÃ©gorie contenu de seance)</span>';
					
				}
				
				
			}

}

function EcoleDirecteSaveNewFileInDB($userID, $insertData, $tmpOptions='') { // Identifiant sur Ecole DIrecte de l'utilisateur + données sur le fichier + options (notif = true ou false)
	
	// $adresse_nouvelle = "SELECT name FROM FILES_" . $userID . " WHERE fileId='".$fileId."'";
	$adresse_nouvelle = "SELECT name FROM FILES_" . $userID . " WHERE fileId='".$insertData['fileId']."'";
	$resultat = db_query($adresse_nouvelle);
	
			$localServ = false;
			if( ($_SERVER['SERVER_NAME'] == 'bidouillages.tk') OR ($_SERVER['SERVER_NAME'] == 'www.bidouillages.tk') )
				$localServ = true;
			if(!$localServ)
					$srvname = 'www.bidouillages.tk';
	
	$nombre_adresse = mysqli_num_rows($resultat);
	if(!empty($insertData['fileId'])) {
		
			if($nombre_adresse < 1)
			{
				
				if($localServ)
				{
				
						$realFileLink = EcoleDirecteSaveNewFileOnDD(array(
							// 'token' => EcoleDirecteLogin(EC_CHARLES_LOGIN, EC_CHARLES_PASS)['token'],
							'token' => $tmpOptions['ed_token'],
							'fileId' => $insertData['fileId'],
							'fileName' => $insertData['name']
							)
						);
						$modifiedFileLink = str_replace(' ', '%20', $realFileLink);
							
						$insertData['url'] = $realFileLink;
						$insertData['Path'] = EC_FILES;
						$insertData['completePath'] = EC_FILES . $insertData['name'];
						
						var_dump(dbInsert("FILES_" . $userID, $insertData));
						
						if($tmpOptions['notif']) {
							$fileData = $insertData;
							$fileData['encodedUrl'] = $modifiedFileLink;
							EcoleDirecteNotifyNewFile($fileData);
						}
				}
				else {
					
					$tmpDate = $insertData['fileDate'];
					
							$curlOptions=array(
								  CURLOPT_URL            => "http://" . $srvname . "/prod/ec/FileBoucle.php?customDate=true&date=" . $tmpDate,
								  CURLOPT_RETURNTRANSFER => false,
								  CURLOPT_FOLLOWLOCATION => false,
								  CURLOPT_HEADER         => false,
								  CURLOPT_FAILONERROR    => false,
								  CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0)'
							);
							
							$curl=curl_init();
							curl_setopt_array($curl,$curlOptions); 
							$curlContent=curl_exec($curl);
							 
							curl_close($curl);
					
				}
			}
		
		var_dump($insertData);
		echo '<br /><br />';
			
	}
	
}

function EcoleDirecteNotifyNewFile($fileData) { // $fileData = données sur le fichier
	
	$modifiedFileLink = $fileData['encodedUrl'];
	$realFileLink = $fileData['url'];
	
	$SendSMSIfNew = rawurlencode("Nouveau Fichier à  la Date : "
		. $fileData['fileDate'] . "\nMatière : " . $fileData['matiereName']
		. "\nURL : " . $modifiedFileLink
		. "\nNom : " . $fileData['name']
	);
	
	SendSmsByFree($SendSMSIfNew);
	
	$fileInfos = array(
		'fileName' => $fileData['name'],
		'filePath' => $fileData['Path'],
		'completePath' => $fileData['completePath']
	);

	$sujet = utf8_decode("[NOTIFICATION]" . $fileData['matiereName'] . " : Nouveau fichier : " . $fileData['name']);
	$corps = utf8_decode("Un nouveau fichier a été mis en ligne en " . $fileData['matiereName'] . " dans " . $fileData['filePlace'] . ".
		<br /><br />Il est présent à  la date du " . $fileData['fileDate'] . " et son nom est \"" . $fileData['name'] . "\".
		<br /><br />Le lien pour le télécharger est <a href=\"" . $modifiedFileLink . "\">" . $realFileLink . "</a>.
		<br /><br />Si besoin, son identifiant est \"" . $fileData['fileId'] . "\".");
	// $corps = "Nouveau fichier";
	// SendMail('charlesdecoux92@gmail.com', $sujet, $corps, '', '', true, $fileInfos);
	SendMail('charlesdecoux92@gmail.com', $sujet, $corps);
	
}

function EcoleDirecteSaveNewFileOnDD($eclData, $fileType='FICHIER_CDT') {

			// eclData => token; fichier_id; [fichier_name]
			
			$srvname = $_SERVER["SERVER_NAME"];
			$localServ = false;
			if( ($_SERVER['SERVER_NAME'] == 'bidouillages.tk') OR ($_SERVER['SERVER_NAME'] == 'www.bidouillages.tk') )
				$localServ = true;
			if(!$localServ)
					$srvname = 'www.bidouillages.tk';
			$postLoginFields = 'leTypeDeFichier=' . $fileType . '&fichierId=' . $eclData['fileId'] . '&token=' . $eclData['token'];
			
			$eclData = array_filter($eclData);
			if(!array_key_exists('fileName', $eclData)) {
						
						exit('DIE: Vous devez sp&eacute;ficier un nom de fichier.');
						
						/* require_once($_SERVER['DOCUMENT_ROOT'] . '/../inc/global.php');

						$url = 'https://vmws09.ecoledirecte.com/v3/telechargement.awp?verbe=get';

						$curl = curl_init();
						curl_setopt($curl, CURLOPT_URL, $url);
						curl_setopt($curl, CURLOPT_HEADERFUNCTION, 'GetFilenameFromHeader');

						curl_setopt($curl, CURLOPT_POST, true);
						curl_setopt($curl, CURLOPT_POSTFIELDS, $postLoginFields);
						curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0)');
							 
						$data = curl_exec($curl);
						$curl_errno = curl_errno($curl);
						$curl_error = curl_error($curl);
						curl_close($curl);
						
						echo $GetFilenameFromHeaderResult; */
				
			}

			$curl = curl_init();
			$url = 'https://vmws09.ecoledirecte.com/v3/telechargement.awp?verbe=get';
			curl_setopt($curl, CURLOPT_URL, $url);
			// $fileName = 'fichier.pdf';
			$fileName = $eclData['fileName'];
			if($localServ)
				$fileA = EC_FILES . $fileName;
			else $fileA = './' . $fileName;
			$fp = fopen($fileA, "w");

			curl_setopt($curl, CURLOPT_FILE, $fp);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);
			curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $postLoginFields);
			curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0)');
				 
			$data = curl_exec($curl);
				  
			$curl_errno = curl_errno($curl);
			$curl_error = curl_error($curl);
				 
			curl_close($curl);
			fclose($fp);
			$ftp_error = false;
			
			if(!$localServ) {
				
				$file = $fileA;
				$remote_file = makeFtp(EC_FILES) . $fileName;

				// Mise en place d'une connexion basique
				$conn_id = ftp_connect(FTP_BIDOUI_SRV);

				// Identification avec un nom d'utilisateur et un mot de passe
				$login_result = ftp_login($conn_id, FTP_BIDOUI_USER, FTP_BIDOUI_PASS);

				// Charge un fichier
				if (ftp_put($conn_id, $remote_file, $file, FTP_ASCII)) {
				 $ftp_error = false;
				} else {
				 $ftp_error = true;
				}

				// Fermeture de la connexion
				ftp_close($conn_id);
				
			}
				 
			if ($curl_errno > 0) {
			   echo "cURL Error ($curl_errno): $curl_error\n";
			}
			elseif($ftp_error) echo "Erreur lors de la mise en ligne du fichier !";
			else {
			   // echo "Fichier tÃ©lÃ©chargÃ© = $data\n";
			   $urlFichier = 'http://' . $srvname . '/files/EC_files/' . $fileName;
			   // echo '<br /><br />URL du fichier : <a target="_blank" href="' . $urlFichier . '">' . $urlFichier . '</a>';
			   return $urlFichier;
			}
			
}

function EDDisplayAll($ed_userId='6600', $options='') {
	
	echo '<div><table style="width:100%" border="1">
		<tr>
			<th>Date</th>
			<th>Matière</th>
			<th>Nom du fichier</th>
			<th>URL du fichier</th>
			<th>Emplacement du fichier</th>
			<th>Ressource ?</th>
			<th>ID du fichier</th>
		</tr>
	';
	
	$matiere = '';
	if(!empty($options)) {
		
		switch($options['matiere']) {
			
			case 'maths':
				$matiere = 'MATHEMATIQUES';
				break;
			
			case 'spc':
				$matiere = 'PHYSIQUE-CHIMIE';
				break;
			
			case 'svt':
				$matiere = 'SCIENCES VIE & TERRE';
				break;
			
			case 'ap':
				$matiere = 'ACCOMPAGNEMT. PERSO.';
				break;
			
			case 'hg':
				$matiere = 'HISTOIRE & GEOGRAPH.';
				break;
			
		}
		
		$matiere = " WHERE matiereName = '" . $matiere . "'";
		
	}
	
	$getList = "SELECT * FROM FILES_" . $ed_userId . $matiere . " ORDER BY fileDate";
	$queryResult = db_query($getList);
	while($row=mysqli_fetch_array($queryResult)) {
		
		echo '<tr>
				<td>' . $row['fileDate'] . '</td>
				<td>' . $row['matiereName'] . '</td>
				<td>' . $row['name'] . '</td>
				<td><a href="' . $row['url'] . '" target="_blank">' . $row['url'] . '</td>
				<td>' . $row['filePlace'] . '</td>
				<td>' . $row['ressourceDocuments'] . '</td>
				<td>' . $row['fileId'] . '</td>
			</tr>
		';
		
	}
	
}

?>	