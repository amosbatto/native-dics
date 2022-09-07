<?php
/************************************************************************
Program: dic-intro-strip.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com, web: www.ciber-runa.net/ab
Project: Runasimipi Qespisqa Software, web: www.runasimipi.org
Created: 27 Nov 2006, Universidad Nacional Micaela Bastidas de Apurimac (Abancay, Peru)

dic-intro-strip.php removes all the excess html formatting from the introduction of
_Quechua-Español Diccionario: Simi Taqe Runasimi-Espàñol_ by the Academia Mayor 
de la Lengua Quechua in Cusco, Peru. 

To call this program:
   php dic-strip.php [DICTIONARY-FILE] [STRIPPED-FILE] 
   
Parameters in [] are optional.  
If no DICTIONARY-FILE, then set to 'AMLQuechua-Dic.htm' by default.
If no STRIPPED-FILE, then set to 'amlq-intro.htm' by default. 

DICTIONARY-FILE is the AMLQ dictionary (www.ciber-runa.net/dic/AMLQuechua-Dic.doc)
which has been saved in HTML format. strip-dic.php will strip off all the excess
html formatting and leave only hard returns <p>, italics <i>, bold <b>, and small-caps 
<span style='font-variant:small-caps'>. All the dictionary entries will be
writen to the HTML file STRIPPED-FILE.  
*****************************************************************************/

//set no max time for this program to run.
ini_set('max_execution_time', 0);
//set no memory limit
ini_set('memory_limit', '-1');
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');

if ($argc > 1)
	$sFDic = $argv[1];
else
	$sFDic = 'AMLQuechua-Dic.htm';
	
if ($argc > 2)
	$sFStripDic = $argv[2];
else
	$sFStripDic = 'amlq-intro.html';
	
$sIn = file_get_contents($sFDic);
$sOut = '';
$nCntIn = 0; 
$nInEnd = strpos($sIn, "XXXI");	//go past the introduction

if ($nInEnd == false)
	$nInEnd = strlen($sIn);
	
for ($bSpan = false; $nCntIn < $nInEnd; $nCntIn++)
{
	if ($sIn[$nCntIn] != '<')
	{
		$sOut .= $sIn[$nCntIn];
		continue;
	}
	
	for ($sTag = '' ; $nCntIn < $nInEnd; $nCntIn++) 
	{
		$sTag .= $sIn[$nCntIn]; 
		if ($sIn[$nCntIn] == '>')
			break;
	}
	
	//if a bold, italics, or paragraph tag, strip the modifiers, then copy to file
	if (preg_match('/^<\/?[bip][\s>]/si', $sTag))
		$sOut .= substr($sTag, 0, ($sTag[1] == '/' ? 3 : 2)) . '>';
	elseif (preg_match('/^<span\s.*style.*small-caps/si', $sTag))
	{
		$sOut .= '<span style=\'font-variant:small-caps\'>';
		$bSpan = true;
	}
	elseif ($bSpan && preg_match('/^<\/span\s*>$/i', $sTag))
	{
		$sOut .= '</span>';
		$bSpan = false;
	}
}

$fOut = fopen($sFStripDic, 'w');
fwrite($fOut, $sOut);
fclose($fOut);
return;

?>
