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
 * Instanciation de la classe de gestion de Bayes
 */

$spamchecker	= new SpamChecker ($dbLink);

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
        $server     = '{' . $line->hostname . ':' . $line->port . '/imap/ssl/novalidate-cert}' . 'Inbox.Ham';
    } else
    {
        $server     = '{' . $line->hostname . ':' . $line->port . '/imap/notls}' . 'Inbox.Ham';
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
			 * Affichage d'un message
			 */
			
			console($FromEmail . ' -> ' . 'Message dans le dossier HAM.');
            
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
             * Entraînement des messages légitimes 
             */
            
            $html 			= new \Html2Text\Html2Text($content . $subject);
			$contentBayes 	= $html->getText();
            $spamchecker->train($contentBayes, false);
            
            /**
             * Suppression du message
             */
            
            $delete = imap_delete($mbox, $msgid);
            imap_expunge($mbox);
        }
    }
    
    /**
     * Fermeture du socket
     */
    
    imap_expunge($mbox);
    imap_close($mbox);
}

/**
 * Dossier SPAM
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
        $server     = '{' . $line->hostname . ':' . $line->port . '/imap/ssl/novalidate-cert}' . 'Inbox.Spam';
    } else
    {
        $server     = '{' . $line->hostname . ':' . $line->port . '/imap/notls}' . 'Inbox.Spam';
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
			 * Affichage d'un message
			 */
			
			console($FromEmail . ' -> ' . 'Message dans le dossier SPAM.');
            
			/**
			 * Gestion des réponses automatiques
			 */
			
			if(preg_match("/" . 'auto-submitted:' . "/i", $head) || preg_match("/" . 'MAILER-DAEMON' . "/i", $head) || preg_match("/" . 'x-responder' . "/i", $head) || preg_match("/" . 'autorespond' . "/i", $head))
            {
				// Affichage d'un message
                console($FromEmail . ' -> ' . 'Détection d\'une notification ou d\'une auto-réponse.');
				
				// Suppression du message
                $delete = imap_delete($mbox, $msgid);
                imap_expunge($mbox);
                
                continue;
			}
            
            /**
             * Entraînement des messages légitimes 
             */
            
            $html 			= new \Html2Text\Html2Text($content . $subject);
			$contentBayes 	= $html->getText();
            $spamchecker->train($contentBayes, true);
            
            /**
             * Suppression du message
             */
            
            $delete = imap_delete($mbox, $msgid);
        }
    }
    
    /**
     * Fermeture du socket
     */
    
    imap_expunge($mbox);
    imap_close($mbox);
}

/**
 * END
 */

?>