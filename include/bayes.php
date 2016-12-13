<?php

class SpamChecker
{
    private $aBuffer;
    
    function __construct ($pBuffer)
    {
        $this->aBuffer  =   $pBuffer;
    }
    
    function train ($text, $spam)
    {
        $result = $this->aBuffer->select('totals')->column('totalsid')->column('totalspam')->column('totalham')->fetch();
        
        foreach($result as $line)
        {
            $totalsid  = $line->totalsid;
            $totalspam = $line->totalspam;
            $totalham  = $line->totalham;
        }
        
        if($spam)
        {
            $totalspam++;
            
            $this->aBuffer->update('totals')->value('totalspam', $totalspam)->where('totalsid', '=', 1)->limit(1)->execute();
        } else
        {
            $totalham++;
            
            $this->aBuffer->update('totals')->value('totalham', $totalham)->where('totalsid', '=', 1)->limit(1)->execute();
        }
        
        $text = preg_replace('/\W+/',' ',$text);
        $temparray = explode(' ',strtolower($text));
        
        foreach ($temparray as $token)
        {
            $token = trim($token);
            
            $result = $this->aBuffer->select('spam')->column('spamid')->where('token', '=', $token)->fetch();
            
            foreach($result as $line)
            {
                $spamid = $line->spamid;
            }
            
            if(count($result) == 1)
            {
                if($spam)
                {
                    $result2 = $this->aBuffer->select('spam')->column('token')->column('spamcount')->column('hamcount')->where('spamid', '=', $spamid)->fetch();
                    
                    foreach($result2 as $line)
                    {
                        $token      = $line->token;
                        $spamcount  = $line->spamcount;
                        $hamcount   = $line->hamcount;
                        
                        $spamcount++;
                        $spamrating = 0.4;
                        
                        if($totalham != 0 && $totalspam !=0)
                        {
                            $hamprob = $hamcount/$totalham;
                            $spamprob = $spamcount/$totalspam;
                        
                            $spamrating = $spamprob/($hamprob+$spamprob);
              
                            if($hamcount == 0)
                            {
                              $spamrating = 1;
                            }
                        }
                        
                        if($spamcount != 0 && $hamcount == 0)
                        {
                            $spamrating = 1;
                        }
                        
                        $this->aBuffer->update('spam')->value('spamcount', $spamcount)->value('spamrating', $spamrating)->where('spamid', '=', $spamid)->limit(1)->execute();
                    }
                } else
                {
                    $result2 = $this->aBuffer->select('spam')->column('token')->column('spamcount')->column('hamcount')->where('spamid', '=', $spamid)->limit(1)->fetch();
                    
                    foreach($result2 as $line)
                    {
                        $token      = $line->token;
                        $spamcount  = $line->spamcount;
                        $hamcount   = $line->hamcount;
                        
                        $hamcount++;
                        
                        if($totalham != 0 && $totalspam !=0)
                        {
                            $hamprob = $hamcount/$totalham;
                            $spamprob = $spamcount/$totalspam;
                            
                            $spamrating = $spamprob/($hamprob+$spamprob);
                              
                            if($spamcount == 0)
                            {
                              $spamrating = 0;
                            }
                        }
                        
                        if($hamcount != 0 && $spamcount == 0)
                        {
                            $spamrating = 0;
                        }
                        
                        $this->aBuffer->update('spam')->value('hamcount', $hamcount)->value('spamrating', $spamrating)->where('spamid', '=', $spamid)->limit(1)->execute();
                    }
                }
            } else
            {
                // Not in the database
                
                if(strlen($token) >= 4 && ctype_alpha($token))
                {
                    if($spam)
                    {
                        $id = $this->aBuffer->insert('spam', array(
                            'token'         => $token,
                            'spamcount'     => 1,
                            'hamcount'      => 0,
                            'spamrating'    => 1
                        ));
                    } else
                    {
                        $id = $this->aBuffer->insert('spam', array(
                            'token'         => $token,
                            'spamcount'     => 0,
                            'hamcount'      => 1,
                            'spamrating'    => 0
                        ));
                    }
                }
            }
        }
    }
    
    function checkSpam ($text)
    {
        $text = preg_replace('/\W+/',' ',$text);
        $temparray = explode(' ', strtolower($text));
        
        $spamratings = array();
        $count = 0;
        
        foreach ($temparray as $token)
        {
            $result = $this->aBuffer->select('spam')->column('spamrating')->where('token', '=', $token)->fetch();
            
            foreach($result as $line)
            {
                $spamratings[$count] = $line->spamrating;
            }
            
            if(count($result) == 0)
            {
                $spamratings[$count] = 0.9;
            }
            
            $count++;
        }
        
        $a = null;
        $b = null;
        
        foreach ($spamratings as $token)
        {
            if($a == null)
            {
                $a = (float)$token;
            } else
            {
                $a = $a * $token; 
            }
            
            if($b == null)
            {
                $b = 1 - (float)$token;
            } else
            {
                $b = $b * ( 1 - (float)$token); 
            }
        }
        
        $spam = (float)0;
        $spam = (float)$a/(float)((float)$a+(float)$b);
        
        return $spam;
    }
}

?>