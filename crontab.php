<?php

/**
 * Supprime les erreurs PHP
 */

error_reporting(E_ALL & ~E_NOTICE);

/**
 * Inclusion des fichiers de configuration nécessaires au daemon
 */

require_once 'include/CRDFCore.php';

/**
 * Vérification de l'existence de la lib imap
 */

if(!function_exists('imap_open'))
{
	console('Le module imap est manquant sur le serveur.');
    
    exit;
}

/**
 * Récupération en base de données des comptes à vérifier
 */

$accountList = $dbLink->select('sendident__accounts')->where('status', '=', 1)->fetch();

/**
 * Lecture de toute la liste des comptes à vérifier
 */

foreach($accountList as $line)
{
    /**
     * Affichage du message de traitement
     */
    
    console('* Vérification en cours du compte : ' . $line->username . '.');
    
    /**
     * Vérification des paramètres indiquées en base de données
     */
    
    if(empty($line->hostname) || empty($line->port) || is_int($line->port) || empty($line->isTLS) || empty($line->username) || empty($line->password) || empty($line->inboxName) || empty($line->inboxTemp))
    {
        console('Paramètres invalides. Veuillez vérifier les paramètres indiquées en base de données.');
        continue;
    }
    
    /**
     * Création des informations de connexion au serveur
     */
    
    if($line->isTLS == 1)
    {
        $server     = '{' . $line->hostname . ':' . $line->port . '/imap/ssl/novalidate-cert}' . $line->inboxName;
    } else
    {
        $server     = '{' . $line->hostname . ':' . $line->port . '/imap/notls}' . $line->inboxName;
    }
    
    /**
     * Création du socket de connexion
     */
    
    $mbox = imap_open($server, $line->username, $line->password);
    
    if($mbox === FALSE)
    {
        console('Impossible de se connecter au compte avec les paramètres indiqués.');
        continue;
    }
    
    /**
     * Traitement et analyse de l'email
     */
    
    if ($headers = imap_headers($mbox))
    {
        $i = 0;
    
        foreach ($headers as $val)
        {
            $i ++;
            
            /**
             * Will return many infos about current email
             * Use var_dump($info) to check content
             */
    
            $info       = imap_headerinfo($mbox, $i);
            $msgid      = trim($info->Msgno);
            $subject    = imap_mime_header_decode($info->Subject);
			$head		= imap_fetchheader($mbox, $msgid);
			$content	= imap_body($mbox, $msgid);
			
			// Décodage des informations avec le bon charset
			$subject	= $subject[0]->text;
    
            /**
             * Retrieve email adress
             */
    
            $from = $info->from;
        
            if(is_array($from))
            {
                 foreach($from as $id => $object) {
                    $FromName = $object->personal;
                    $FromEmail = strtolower(trim($object->mailbox . "@" . $object->host));
                    $FromHost = trim($object->host);
                }
            }
        
            /**
             * Gets the current email structure (including parts)
             */
    
            $structure = imap_fetchstructure($mbox, $msgid);
			
            /**
             * Véfication de l'état de l'adresse dans la liste blanche
             */
            
            $whitelist = $dbLink->select('sendident__whitelist')->where('ID_ACCOUNT', '=', $line->ID)->and_where('email', '=', $FromEmail)->limit(1)->fetch();
            
            if(count($whitelist) != 0)
            {
                console($FromEmail . ' -> ' . 'Utilisateur déjà en liste blanche.');
                continue;
            }
			
			/**
			 * Affichage d'un message
			 */
			
			console($FromEmail . ' -> ' . 'Message dans boîte de réception');
            
			/**
			 * Gestion des réponses automatiques
			 */
			
			if(preg_match("/" . 'auto-submitted:' . "/i", $head) || preg_match("/" . 'MAILER-DAEMON' . "/i", $head) || preg_match("/" . 'x-responder' . "/i", $head) || preg_match("/" . 'autorespond' . "/i", $head))
            {
				// Affichage d'un message
                console($FromEmail . ' -> ' . 'Détection d\'une notification ou d\'une auto-réponse.');
				
				// Suppression du message
                $delete = imap_delete($mbox, $msgid);
                
                continue;
			}
			
			/**
			 * Gestion de la liste noire
			 */
			
			$blacklistEmail 	= $dbLink->select('sendident__blacklist')->where('ID_ACCOUNT', '=', $line->ID)->and_where('email', '=', $FromEmail)->limit(1)->fetch();
            $blacklistDomain 	= $dbLink->select('sendident__blacklist')->where('ID_ACCOUNT', '=', $line->ID)->and_where('email', '=', getDomainFromEmail($FromEmail))->limit(1)->fetch();
            
			if(count($blacklistEmail) != 0 OR count($blacklistDomain) != 0)
			{
				// Affichage d'un message
                console($FromEmail . ' -> ' . 'Adresse email ou nom de domaine incluse dans la liste noire.');
				
				// Déplacement du message dans le répertoire temporaire
                $move = imap_mail_move($mbox, $msgid, $line->inboxName . '.' . $line->inboxTemp);
				
				// Arrête la continuité de la boucle
				continue;
                
                // Vérification du déplacement
                if($move === FALSE)
                {
                    console($msgid . ' -> ' . 'Impossible de déplacer le message. Le répertoire existe-il ?');
                    continue;
                }
			}
			
            /**
             * Gestion de l'authentification
             */
            
            if(preg_match("/" . 'TOKEN' . "/i", $subject))
            {
                // Affichage d'un message
                console($FromEmail . ' -> ' . 'Authentification en cours des emails...');
                
                // Récupération des valeurs d'authentification
                preg_match("/\\[TOKEN: (.*)\\]/", $subject, $return);
                
                // Mise en variable du token
                $token = trim($return[1]);
                
                // Vérification de la cohérence des données
                $check = $dbLink->select('sendident__mails')->where('ID_ACCOUNT', '=', $line->ID)->and_where('token', '=', $token)->and_where('emailFrom', '=', $FromEmail)->and_where('validate', '=', 0)->limit(1)->fetch();
                
                if(count($check) != 0)
                {
                    // Affichage du message
                    console($FromEmail . ' -> ' . 'Cohérence des données trouvées. Authentification acceptée.');
                    
                    // Ajout de l'adresse email à la whitelist
                    $insert_id = $dbLink->insert('sendident__whitelist', array(
                                    'ID_ACCOUNT' 	=> $line->ID,
                                    'email' 		=> $FromEmail,
                                    'timestamp'     => time()
                    ));
                    
                    // Validation de tous les emails à éventuellement déplacer
                    $dbLink->update('sendident__mails')
                        ->value('validate', 1)
                        ->where('ID_ACCOUNT', '=', $line->ID)
                        ->and_where('emailFrom', '=', $FromEmail)
                        ->and_where('validate', '=', 0)
                        ->and_where('onMove', '=', 0)
                        ->execute();
                    
                    // Vérification de la suppression
                    if($move === FALSE)
                    {
                        console($msgid . ' -> ' . 'Impossible de supprimer le message.');
                        continue;
                    }
                } else
                {
                    // Affichage d'un message
                    console($FromEmail . ' -> ' . 'Authentification invalide (' . $subject . ').');
                }
                
                // Suppression du message
                $delete = imap_delete($mbox, $msgid);
                
                continue;
            }
            
            /**
             * Dans le cas contraire, on va devoir authentifier l'email
             */
            
            // Signature unique de l'email
            $sig = sha1($FromName . $subject . $FromEmail . $content . strtotime($head->Date) . $head);
            
            // Vérification de l'existence de cet email en base de données
            $mail = $dbLink->select('sendident__mails')->where('ID_ACCOUNT', '=', $line->ID)->and_where('sig', '=', $sig)->and_where('validate', '=', 0)->limit(1)->fetch();
            
            // Condition de logique
            if(count($mail) == 0)
            {
                // Déplacement du message dans le répertoire temporaire
                $move = imap_mail_move($mbox, $msgid, $line->inboxName . '.' . $line->inboxTemp);
                
                // Vérification du déplacement
                if($move === FALSE)
                {
                    console($msgid . ' -> ' . 'Impossible de déplacer le message. Le répertoire existe-il ?');
                    continue;
                }
                
                // Génération d'un token unique
                $newToken = token();
                
				// Gestion de la classe permettant d'envoyer un email
				$mail = new PHPMailer;
				
				$mail->isSMTP();
				$mail->Host = $line->hostname;                        // Specify main and backup SMTP servers
				$mail->SMTPAuth = true;                               // Enable SMTP authentication
				$mail->Username = $line->username;                    // SMTP username
				$mail->Password = $line->password;                    // SMTP password
				$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
				$mail->Port = 465;                                    // TCP port to connect to
				
				$mail->From = $line->username;
				$mail->FromName = $line->name;
				$mail->addAddress($FromEmail, $FromName);     // Add a recipient
				$mail->addReplyTo($line->username, $line->name);
				$mail->CharSet = 'UTF-8';
				
				// Subject Message
				$mail->Subject	= 'Re: [TOKEN: ' . $newToken . '] ' . $subject;
				
				// Plain text message
				$mail->Body		= getTextEmail('auth', array('[EMAIL]' => $FromEmail, '[TOKEN]' => $newToken, '[PROCESS-ID]' => $insert_id, '[USERNAME]' => $line->username, '[SIG]' => $sig));
				
				if (!$mail->send())
				{
					console($msgid . ' -> ' . "L'email d'authentification ne peut pas être envoyé pour le moment. Erreur : " . $mail->ErrorInfo);
                    continue;
				}
				
                // Enregistrement de l'email en base de données
                $insert_id = $dbLink->insert('sendident__mails', array(
                                    'ID_ACCOUNT' 	=> $line->ID,
                                    'sig' 		    => $sig,
                                    'emailFrom'     => $FromEmail,
                                    'token'	        => $newToken,
                                    'timestamp'     => time()
                ));
            } else
			{
				// Affichage d'un message
                console($FromEmail . ' -> ' . 'Email déjà traité (sig: ' . $sig . ').');
				
				// Déplacement du message dans le répertoire temporaire
                $move = imap_mail_move($mbox, $msgid, $line->inboxName . '.' . $line->inboxTemp);
				
				// Vérification du déplacement
                if($move === FALSE)
                {
                    console($msgid . ' -> ' . 'Impossible de déplacer le message. Le répertoire existe-il ?');
                    continue;
                }
			}
        }
    }
    
    /**
     * Fermeture du socket
     */
    
    imap_expunge($mbox);
    imap_close($mbox);
}

/**
 * Tâche secondaire permettant de déplacer les messages authentifiées
 */

$accountList = $dbLink->select('sendident__accounts')->where('status', '=', 1)->fetch();

foreach($accountList as $line)
{
    $task = $dbLink->select('sendident__mails')->where('validate', '=', 1)->and_where('onMove', '=', 0)->and_where('ID_ACCOUNT', '=', $line->ID)->limit(1)->fetch();
    
    if(count($task) != 0)
    {
        // Affichage d'un message
        console('* La tâche secondaire doit maintenant déplacer des messages authentifiés pour le compte ' . $line->username . '.');
        
        /**
         * Vérification des paramètres indiquées en base de données
         */
        
        if(empty($line->hostname) || empty($line->port) || is_int($line->port) || empty($line->isTLS) || empty($line->username) || empty($line->password) || empty($line->inboxName) || empty($line->inboxTemp))
        {
            console('Paramètres invalides. Veuillez vérifier les paramètres indiquées en base de données.');
            continue;
        }
        
        /**
         * Création des informations de connexion au serveur
         */
        
        if($line->isTLS == 1)
        {
            $server     = '{' . $line->hostname . ':' . $line->port . '/imap/ssl/novalidate-cert}' . $line->inboxName . '.' . $line->inboxTemp;
        } else
        {
            $server     = '{' . $line->hostname . ':' . $line->port . '/imap/notls}' . $line->inboxName . '.' . $line->inboxTemp;
        }
        
        /**
         * Création du socket de connexion
         */
        
        $mbox = imap_open($server, $line->username, $line->password);
        
        if($mbox === FALSE)
        {
            console('Impossible de se connecter au compte avec les paramètres indiqués.');
            continue;
        }
        
        /**
         * Traitement et analyse de l'email
         */
        
        if ($headers = imap_headers($mbox))
        {
            $i = 0;
        
            foreach ($headers as $val)
            {
				unset($FromEmail);
                $i ++;
                
                /**
                 * Will return many infos about current email
                 * Use var_dump($info) to check content
                 */
        
                $info       = imap_headerinfo($mbox, $i);
                $msgid      = trim($info->Msgno);
                $subject    = imap_mime_header_decode($info->Subject);
                $head       = imap_fetchheader($mbox, $msgid);
                $content    = imap_body($mbox, $msgid);
				
				// Décodage des informations avec le bon charset
				$subject	= $subject[0]->text;
        
                /**
                 * Retrieve email adress
                 */
        
                $from = $info->from;
            
                if(is_array($from))
                {
                     foreach($from as $id => $object) {
                        $FromName = $object->personal;
                        $FromEmail = strtolower(trim($object->mailbox . "@" . $object->host));
                        $FromHost = trim($object->host);
                    }
                }
				
				// Signature unique de l'email
				$sig = sha1($FromName . $subject . $FromEmail . $content . strtotime($head->Date) . $head);
                
                /**
                 * Déplacement de tous les emails déjà authentifiés
                 */
                
                $whitelist = $dbLink->select('sendident__whitelist')->where('ID_ACCOUNT', '=', $line->ID)->and_where('email', '=', $FromEmail)->limit(1)->fetch();
                $sigChecks = $dbLink->select('sendident__mails')->where('ID_ACCOUNT', '=', $line->ID)->and_where('emailFrom', '=', $FromEmail)->and_where('sig', '=', $sig)->and_where('validate', '=', 1)->and_where('onMove', '=', 0)->fetch();
				
				if(count($sigChecks) != 0)
				{
					if(count($whitelist) != 0)
					{
						// Affichage d'un message
						console('Déplacement d\'un email authentifié pour le compte ' . $line->username . ' (email: ' . $FromEmail . ').');
						
						// Déplacement du message
						imap_clearflag_full($mbox, $msgid, "\\Unseen \\Flagged");
						$move = imap_mail_move($mbox, $msgid, $line->inboxName);
						
						// Vérification du déplacement
						if($move === FALSE)
						{
							console($msgid . ' -> ' . 'Impossible de déplacer le message dans la boîte de réception. Le répertoire existe-il ?');
							continue;
						}
						
						// Mise à jour des messages authentifiés
						$dbLink->update('sendident__mails')
							->value('onMove', 1)
							->where('ID_ACCOUNT', '=', $line->ID)
							->and_where('sig', '=', $sig)
							->and_where('emailFrom', '=', $FromEmail)
							->and_where('validate', '=', 1)
							->and_where('onMove', '=', 0)
							->execute();
					}
				}
            }
        }
        
        /**
        * Fermeture du socket
        */
       
       imap_expunge($mbox);
       imap_close($mbox);
    }
}

/**
 * END
 */

?>