<?php

/**
 * CRDF API Service: AV Cloud
 *
 * @author CRDF France / Jocelyn G.
 * @copyright Private License CRDF
 * @since 30/06/2013
 */

/**
 * Génération d'une clé unique et simple pour le token
 */

function token ()
{
    return rand() . time();
}

/**
 * Générateur de clé unique
 */

function SessionIDGenerator ($namespace = '')
{
    $guid = '';

    $uid = uniqid("", true);
    $data = $namespace;
    $data .= $_SERVER['REQUEST_TIME'];
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['REMOTE_ADDR'];
    $data .= $_SERVER['REMOTE_PORT'];
    $hash = strtolower(hash('ripemd128', $uid . $guid . md5($data)));

    $guid = substr($hash,  0,  8) .
            '.' .
            substr($hash,  8,  4) .
            '.' .
            substr($hash, 12,  4) .
            '.' .
            substr($hash, 16,  4) .
            '.' .
            substr($hash, 20, 12);

    return $guid;
}

/**
 * Fonction permettant de gérer l'affichage des logs
 */

function console ($Msg)
{
  echo date("r") . ": " . $Msg . "\r\n";
  flush();
}

/**
 * Fonction permettant de retourner le texte et le contenu d'un email
 */

function getTextEmail ($code, $vars)
{
    $file = ROOT . 'text/' . $code . '.txt';

    if(file_exists($file))
    {
        // Récupère le contenu
        $content = file_get_contents($file);

        // Transformation des variables
        foreach ($vars as $key => $value)
        {
            $content = str_replace($key, $value, $content);
        }

        // Retour
        return $content;
    } else
    {
        trigger_error('getTextEmail(): mail id not found for ' . $file);
    }
}

/**
 * Fonction permettant de récupérer le nom de domaine associé à une adresse email
 */

function getDomainFromEmail ($email)
{
    // Get the data after the @ sign
    $domain = substr(strrchr($email, "@"), 1);

    return $domain;
}

/**
 * Fonction permettant de valider la validité d'un nom de domaine
 */

function is_valid_domain_name ($domain_name)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domain_name) //valid chars check
            && preg_match("/^.{1,253}$/", $domain_name) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name)   ); //length of each label
}

/**
 * Fonction permettant de vérifier les DNSBL/RBL
 */

function dnsBlacklist ($ip, $timeout = 1)
{
    $servers = array(
        "b.barracudacentral.org",
        "bl.spamcop.net",
        "zen.spamhaus.org"
    );

    foreach ($servers as $serverSel)
    {
        $response = array();
     	$host = implode(".", array_reverse(explode('.', $ip))).'.'.$serverSel.'.';
     	$cmd = sprintf('nslookup -type=A -timeout=%d %s 2>&1', $timeout, escapeshellarg($host));
     	@exec($cmd, $response);

     	for ($i=3; $i<count($response); $i++)
        {
     		if (strpos(trim($response[$i]), 'Name:') === 0)
            {
     			return true;

                break;
     		}
     	}
    }

    return false;
}

/**
 * Fonction permettant de vérifier si une adresse IP est publique et respect la RFC1918
 */

function reserved_ip ($ip)
{
    $reserved_ips = array( // not an exhaustive list
    '167772160'  => 184549375,  /*    10.0.0.0 -  10.255.255.255 */
    '3232235520' => 3232301055, /* 192.168.0.0 - 192.168.255.255 */
    '2130706432' => 2147483647, /*   127.0.0.0 - 127.255.255.255 */
    '2851995648' => 2852061183, /* 169.254.0.0 - 169.254.255.255 */
    '2886729728' => 2887778303, /*  172.16.0.0 -  172.31.255.255 */
    '3758096384' => 4026531839, /*   224.0.0.0 - 239.255.255.255 */
    );

    $ip_long = sprintf('%u', ip2long($ip));

    foreach ($reserved_ips as $ip_start => $ip_end)
    {
        if (($ip_long >= $ip_start) && ($ip_long <= $ip_end))
        {
            return TRUE;
        }
    }

    return FALSE;
}

?>
