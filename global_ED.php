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
					$eclCurl=curl_init();
					curl_setopt_array($eclCurl,$eclOptions); 
					$eclContent=curl_exec($eclCurl);
					 
					curl_close($eclCurl);
					$eclContentDecoded = json_decode($eclContent, true);
					$eclContentDecoded_data = $eclContentDecoded['data']; $eclContentDecoded_data_accounts = $eclContentDecoded_data['accounts']; $eclContentDecoded_data_accounts_0 = $eclContentDecoded_data_accounts['0'];
					$eclCurl = null;
					
					$eclReturn = array(
					'cookie' => $cookie,
					'options' => $eclOptions,
					'token' => $eclContentDecoded['token'],
					'id' => $eclContentDecoded_data_accounts_0['id'],
					);
					
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

			// Dans la table "UsersIDs", sꭥctionner toutes les lignes dont la colonne "NotifyNewFile" contient 1, et rꤵp곥r les identifiants ED
			
			// $EcoleDirecteGetID = EcoleDirecteLogin(/*, */$eclLogin, $eclPass);
			$EcoleDirecteGetID = EcoleDirecteLogin(EC_CHARLES_LOGIN, EC_CHARLES_PASS);
			if(!empty($ed_logins)) $EcoleDirecteGetID = EcoleDirecteLogin($ed_logins['login'], $ed_logins['pwd']);
			
			$EcoleDirecteUserID = $EcoleDirecteGetID['id'];

			$dateFinale = array();
			
			/*
			
				Ici :
					- Recherche dans la table "FILES_users" si l'ID Ecole Directe de l'utilisateur est présent
						- s'il l'est, on continue et quitte la condition
						- s'il n'y est pas, 
							- on crꩠla table FILES_XXXX dans la BDD
							- on crꩠle dossier 
							- d괡ctiver les notifs ($notif = false)
			
			*/
			
				$querySearch = "SELECT * FROM FILES_users WHERE userId='" . $EcoleDirecteUserID . "'"; $existing = false;
			
				while($row=mysqli_fetch_array(db_query($querySearch))) {
					$existing = true;
				}

			$createUserTableQuery = "CREATE TABLE IF NOT EXISTS FILES_" . $EcoleDirecteGetID['id'] . " (id INT(11) NOT NULL AUTO_INCREMENT,
			  fileDate VARCHAR(11) DEFAULT NULL,
			  matiereName VARCHAR(255) DEFAULT NULL,
			  name VARCHAR(255) DEFAULT NULL,
			  url VARCHAR(255) DEFAULT NULL,
			  filePlace VARCHAR(20) DEFAULT NULL,
			  fileId INT(11) DEFAULT NULL,
			  fileType VARCHAR(20) DEFAULT NULL,
			  PRIMARY KEY (id))";
			$createUserTableQueryExec = db_query($createUserTableQuery);

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
								'notif' => $notif
							);
							doAction($action, $tmpOptions);
							
						}
						
					}
					
				}
}

function GetFileIDs($tmpOptions) {

			$ffdate = $tmpOptions['fdate'];
            $EcoleDirecteGetAgenda = EcoleDirecteGetAgenda($ffdate); // Ã  supprimer aprÃ¨s
            $content3 = $EcoleDirecteGetAgenda['content'];
            $content3Decoded = $EcoleDirecteGetAgenda['content'];
			$content3Decoded_data = $content3Decoded['data'];
			$content3Decoded_data_matieres = $content3Decoded_data['matieres']; $content3Decoded_data_matieres_0 = $content3Decoded_data_matieres['0'];
			$date_agenda = $content3Decoded_data['date'];
			
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
							'fileType' => $documentActuel['type']
							);
							
							EcoleDirecteSaveNewFileInDB($EcoleDirecteGetAgenda['id'], $dataFilesFinal, $tmpOptions);
							
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
							'fileType' => $documentActuel['type']
							);
							
							EcoleDirecteSaveNewFileInDB($EcoleDirecteGetAgenda['id'], $dataFilesFinal, $tmpOptions);
							
						}
						
					} else $toDisplay .= '<span style="color:#8ABAE2;">Pas de document pour cette matiÃ¨re ! (catÃ©gorie contenu de seance)</span>';
					
				}
				
				
			}

}

function EcoleDirecteSaveNewFileInDB($userID, $insertData, $tmpOptions='') { // Identifiant sur Ecole DIrecte de l'utilisateur + données sur le fichier + options (notif = true ou false)
	
	// $adresse_nouvelle = "SELECT name FROM FILES_" . $userID . " WHERE fileId='".$fileId."'";
	$adresse_nouvelle = "SELECT name FROM FILES_" . $userID . " WHERE fileId='".$insertData['fileId']."'";
	$resultat = db_query($adresse_nouvelle);
	$nombre_adresse = mysqli_num_rows($resultat);
	if(!empty($insertData['fileId'])) {
		
			if($nombre_adresse < 1)
			{
				
				$realFileLink = EcoleDirecteSaveNewFileOnDD(array(
					'token' => EcoleDirecteLogin(EC_CHARLES_LOGIN, EC_CHARLES_PASS)['token'],
					'fileId' => $insertData['fileId'],
					'fileName' => $insertData['name']
					)
				);
				$modifiedFileLink = str_replace(' ', '%20', $realFileLink);
					
				$insertData['url'] = $realFileLink;
				
				var_dump(dbInsert("FILES_" . $userID, $insertData));
				
				if($tmpOptions['notif']) {
					$fileData = $insertData;
					$fileData['encodedUrl'] = $modifiedFileLink;
					EcoleDirecteNotifyNewFile($fileData);
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

	$sujet = utf8_decode("[NOTIFICATION]" . $fileData['matiereName'] . " : Nouveau fichier : " . $fileData['name']);
	$corps = utf8_decode("Un nouveau fichier a été mis en ligne en " . $fileData['matiereName'] . " dans " . $fileData['filePlace'] . ".
		<br /><br />Il est présent à  la date du " . $fileData['fileDate'] . " et son nom est \"" . $fileData['name'] . "\".
		<br /><br />Le lien pour le télécharger est <a href=\"" . $modifiedFileLink . "\">" . $realFileLink . "</a>.
		<br /><br />Si besoin, son identifiant est \"" . $fileData['fileId'] . "\".");
	SendMail('charlesdecoux92@gmail.com', $sujet, $corps);
	
}

function EcoleDirecteSaveNewFileOnDD($eclData, $fileType='FICHIER_CDT') {

			// eclData => token; fichier_id; [fichier_name]
			
			$srvname = $_SERVER["SERVER_NAME"];
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
			$fileA = EC_FILES . $fileName;
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
				 
			if ($curl_errno > 0) {
			   echo "cURL Error ($curl_errno): $curl_error\n";
			}
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
				<td>' . $row['fileId'] . '</td>
			</tr>
		';
		
	}
	
}

?>	
