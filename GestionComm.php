<?php

/**
 * 
 * Fonctions définies dans ce fichier :
 *
 *	- SendSmsByFree : Envoie un SMS à mon numéro de TEL. Nécessite un paramètre, le message à envoyer.
 *
 *	- SendMail : Envoie un mail, avec les paramètres suivants :
 *		adresse e-mail du destinataire souhaité du mail
 *		sujet du mail
 *		message contenu dans le mail
 *		[facultatif] nom de l'expéditeur
 *		[facultatif] adresse e-mail de l'expéditeur
 *		--> à ajouter : copie, copie cachée, (envoi de fichier ?)
 *
**/

function SendSmsByFree($CurlSendSMSMsg='') {
	
 	if(empty($CurlSendSMSMsg)) $CurlSendSMSUrl = FREEMB_SMSAPI_URL . "Aucune url n'a ete specifiee";
	else $CurlSendSMSUrl = FREEMB_SMSAPI_URL . $CurlSendSMSMsg;
	
/* 	if(empty($CurlSendSMSMsg)) $CurlSendSMSMsg = "Aucune url n'a ete specifiee";
	else $CurlSendSMSUrl = FREEMB_SMSAPI_URL . dbCleaner($CurlSendSMSMsg, 'mysql'); */
	
	// $postSendSMS = array(
	// 'user' => FREEMB_SMSAPI_USER,
	// 'pass' => FREEMB_SMSAPI_PASS,
	// 'msg' => 
	// )
	
	$CurlSensSMSOptions=array(
			CURLOPT_URL            => $CurlSendSMSUrl, // Url cible (l'url la page que vous voulez télécharger)
			CURLOPT_RETURNTRANSFER => true, // Retourner le contenu téléchargé dans une chaine (au lieu de l'afficher directement)
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HEADER         => false, // Ne pas inclure l'entête de réponse du serveur dans la chaine retournée
			CURLOPT_FAILONERROR    => false,       // Gestion des codes d'erreur HTTP supérieurs ou égaux à 400
			CURLOPT_USERAGENT      => 'Mozilla/5.0 (X11; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0)', 
	);
	
	$CurlSendSMS = curl_init();
	curl_setopt_array($CurlSendSMS,$CurlSensSMSOptions);
	$CurlSendSMSContent = curl_exec ($CurlSendSMS);
	curl_close($CurlSendSMS);
	
}

function SendMail($destinataire, $sujet, $message, $expediteurName='', $expediteurMail='', $attachedFile=false, $attachedFileInfos='') {
	
		if(empty($expediteurName)) $expediteurName = 'Bidouillages - contact';
		if(empty($expediteurMail)) $expediteurMail = 'contact@bidouillages.tk';

		$headers = "From: \"" . $expediteurName . "\"<" . $expediteurMail . "\n";
		$headers .= "Reply-To: " . $expediteurMail . "\n";
		if($attachedFile) {
			
			$fichier = $attachedFileInfos;
			
			// $path = $fichier['filePath'];
			$path = EC_FILES;
			$filename = '1S-TP8.pdf';
			$file = $path . $filename;

			$content = file_get_contents($file);
			$content = chunk_split(base64_encode($content));

			// a random hash will be necessary to send mixed content
			$separator = md5(time());

			// carriage return type (RFC)
			$eol = "\r\n";

			// main header (multipart mandatory)
			$headers .= "MIME-Version: 1.0" . $eol;
			$headers .= "Content-Type: multipart/mixed; boundary=\"" . $separator . "\"" . $eol;
			$headers .= "Content-Transfer-Encoding: 7bit" . $eol;
			$headers .= "This is a MIME encoded message." . $eol;

			// message
			$body = "--" . $separator . $eol;
			$body .= "Content-Type: text/plain; charset=\"iso-8859-1\"" . $eol;
			$body .= "Content-Transfer-Encoding: 8bit" . $eol;
			$body .= $message . $eol;

			// attachment
			$body .= "--" . $separator . $eol;
			$body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
			$body .= "Content-Transfer-Encoding: base64" . $eol;
			$body .= "Content-Disposition: attachment" . $eol;
			$body .= $content . $eol;
			$body .= "--" . $separator . "--";
			
		}
		elseif (!$attachedFile) $headers .= "Content-Type: text/html; charset=\"UTF-8\"";

		mail($destinataire,$sujet,$message,$headers);
	
}

/* // On va dabors définir le fichier à envoyer et à qui
$fichier = 'mon_fichier.pdf';
$destinataire = 'mon_client@son_fai.fr';
// On créer un boundary unique
$boundary = md5(uniqid(rand(), true));
// On met les entêtes
$entetes = 'Content-Type: multipart/mixed;'."n".' boundary="'.$boundary.'"';
$body = 'This is a multi-part message in MIME format.'."n";
$body .= '--'.$boundary."n";
// ici, c'est la première partie, notre texte HTML (ou pas !)
// Là, on met l'entête
$body .= 'Content-Type: text/html; charset="UTF-8"'."n";
// On peut aussi mettres les autres entêtes (voir à la fin)
$body .= "n"
// On remet un deuxième retour à la ligne pour dire que les entêtes sont finie, on peut afficher notre texte !
$body .= 'Bonjour,<br />Voici ci-joint la facture de <strong>Juillet 2008</strong> a payer sous <strong>2 heures</strong>';
// Le texte est finie, on va faire un saut à la ligne
$body .= "n";
// Et on commence notre deuxième partie qui va contenir le PDF
$body .= '--'.$boundary."n";
// On lui dit (dans le Content-type) que c'est un fichier PDF
$body .= 'Content-Type: application/pdf; name="'.$fichier.'"'."n";
$body .= 'Content-Transfer-Encoding: base64'."n";
$body .= 'Content-Disposition: attachment; filename="'.$fichier.'"'."n";
// Les entêtes sont finies, on met un deuxième retour à la ligne
$body .= "n"; */

?>