<?php
/************************************************************************
Program: strip-dic-nta.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com, web: www.ciber-runa.net/ab
Project: Runasimipi Qespisqa Software, web: http://www.runasimipi.org
Created: 13 Dec 2006
License: GNU General Protection License (latest available version at http://www.fsf.org)

strip-dic.php removes all the excess html formatting from _GLOSARIO DE NUEVOS 
TÉRMINOS AIMARAS_ por Gregorio Callisaya A. et al. de UMSA en La Paz, Bolivia.
and inserts the dictionary entries into a mysql database for web serving.

To call this program:
   php strip-dic-nta.php [DICTIONARY-FILE] [STRIPPED-FILE] [JUNK-FILE] [SQL-FILE]
		[START-PHRASE] [STOP-PHRASE]

Parameters in [] are optional.  
If no DICTIONARY-FILE, then set to 'NTAimara.htm' by default.
If no STRIPPED-FILE, then set to 'NTAimara-strip.htm' by default. 
If no JUNK-FILE, then set to 'NTAimara-junk.htm' by default.
If no SQL-FILE, then set to 'NTAAimara.sql' by default.
If no START-PHRASE, then set to 'Gregorio Callisaya A.' by default.
If no STOP-PHRASE, then set to 'BIBLIOGRAFÍA' by default.

DICTIONARY-FILE is the AMLQ dictionary (www.ciber-runa.net/dic/AMLQuechua-Dic.doc)
which has been saved in HTML format. strip-dic.php will strip off all the excess
html formatting and leave only hard returns <p>, italics <i>, bold <b>, and small-caps 
<span style='font-variant:small-caps'>. All the dictionary entries will be
writen to the HTML file STRIPPED-FILE.  Text which isn't written to 
STRIPPED-FILE (viz., page numbers, single letter headings, and key words in
the headers) will be written to JUNK-FILE. Then strip-dic-nta.php will insert
the dictionary entries into the MySQL database "amlq" in the table "dic". All the
text in between the START-PHRASE and the STOP-PHRASE (but not including those 
phrases) will be considered part of the dictionary. 

After runing strip-dic.php, start up MySQL as administrator in MS Windows or 
as root in Linux/UNIX:
$ su [enter root password] 

Enter MySQL:
# mysql
mysql> source SQL-FILE;

You have to run this program with PHP5 with the mysql extension.

If the nta MySQL database doesn't exist, you will have to create it. In Windows, 
log in as administrator, then open a DOS command window. If using Linux/UNIX, 
switch to the root user: 
$ su [enter password for root]

Then enter MySQL and create the 'nta' database:
# mysql
mysql> CREATE DATABASE nta;

To see if the 'nta' database  exists:
mysql> USE nta;

To see if the 'dic' table exists:
mysql> describe dic;

If the 'dic' table exists, then delete the contents of it:
mysql> delete from dic;

If the 'dic' table doesn't exist, then you will have to create it:
mysql> CREATE TABLE IF NOT EXISTS dic (id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, 
	-> lengua ENUM('A', 'E') NOT NULL, clave VARCHAR(40) NOT NULL, clave_frases TEXT, 
	-> definicion TEXT NOT NULL, entrada TEXT NOT NULL, claves_son TEXT NOT NULL, 
	-> definicion_son TEXT NOT NULL, creado DATETIME NOT NULL, 
	-> actualizado TIMESTAMP NOT NULL, INDEX idx_clave (clave),
	-> FULLTEXT (clave, clave_frases), FULLTEXT(clave, clave_frases, definicion), 
	-> FULLTEXT(claves_son), FULLTEXT(claves_son, definicion_son), PRIMARY KEY(id));
	
Then check if the amlq user exists:
mysql> SHOW GRANTS FOR nta;

If user amlq doesn't exist or there are no privileges for amlq, create them:
mysql> GRANT delete, select, update, insert ON amlq.dic TO amlq 
    -> INDENTIFIED BY 'secret';
[The password is not 'secret'--ask Amos Batto for it] 

Exit MySQL:
mysql> EXIT;

If in Linux/UNIX, exit the root user account:
# exit
*****************************************************************************/

//set no max time for this program to run.
ini_set('max_execution_time', 0);
//set no memory limit
ini_set('memory_limit', '-1');
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
include("./soundslike.php");

if ($argc > 1)
	$sFDic = $argv[1];
else
	$sFDic = 'NTAimara.htm';
	
if ($argc > 2)
	$sFStripDic = $argv[2];
else
	$sFStripDic = 'NTAimara-strip.html';
	
if ($argc > 3)
	$sFJunk = $argv[3];
else
	$sFJunk = 'NTAimara-junk.html';

if ($argc > 4)
	$sFSql = $argv[4];
else
	$sFSql = 'NTAimara.sql';

if ($argc > 5)
	$sStartDic = $argv[5];
else
	$sStartDic = 'Gregorio Callisaya A.';

if ($argc > 6)
	$sStopDic = $argv[6];
else
	$sStopDic = 'BIBLIOGRAFÍA';

$sIn = file_get_contents($sFDic);
$sOut = '';
$nCntDef = $nCntContinueDef = $nCntJunk = 0;
$nCntIn = strpos($sIn, $sStartDic);	//go past the introduction
$nInEnd = strpos($sIn, $sStopDic); //go to the end

if ($nCntIn === false)
	$nCntIn = 0;
else 
	$nCntIn += strlen(sStartDic);
	
if ($nInEnd == false)
	$nInEnd = strlen($sIn);

	
for ($bSpan = false, $bBold = false; $nCntIn < $nInEnd; $nCntIn++)
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
	
	//if a bold (<b> or <span style="...bold...">), italics (<i>), supertext (<sup>), 
	//small caps (<span style="...small-caps...>), or paragraph tag (<p>), 
	//strip the modifiers, then copy to file
	if (preg_match('/^<\/?[bip][\s>]/si', $sTag))
		$sOut .= substr($sTag, 0, ($sTag[1] == '/' ? 3 : 2)) . '>';
	elseif (preg_match('/^<\/?sup\s*>$/i', $sTag))
		$sOut .= $sTag;
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
	elseif (preg_match('/^<span\s.*style.*bold/si', $sTag))
	{
		$sOut .= '<b>';
		$bSpan = true;
	}
	elseif ($bBold && preg_match('/^<\/span\s*>$/i', $sTag))
	{
		$sOut .= '</b>';
		$bBold = false;
	}
}

//delete me:
$fOut = fopen($sFStripDic, 'w');
fwrite($fOut, $sOut);
return; 
// end delete me

$sIn = null;
$aOut = preg_split('/<p>/', $sOut);
$sOut = null;
$aOut = preg_replace('/\s*<\/p>\s*$/', '', $aOut);
$fJunk = fopen($sFJunk, 'w');
$fOut = fopen($sFStripDic, 'w');
$fSql = fopen($sFSql, 'w');
fwrite($fSql, "USE nta;\n");
$sDef = '';
$nCntIn = 0;

//Go back through the file and eliminate all the paragraphs which aren't dictionary
//definitions, like page numbers and the page headers. Also elimate line breaks 
//in the definitions.
foreach ($aOut as $sPara)
{
	//if the paragraph begins with a bold tag, then probably a dictionary definition
	if (preg_match('/^\s*<b>/si', $sPara))
	{
		//if paragraph contains a period, then likely a dictionary definition
		if (strpos($sPara, '.') !== false || strpos($sPara, ':') !== false)
		{
			if ($sDef != '')
				processEntry($sDef);
	
			$sDef = $sPara;
		}
		else
		{
			fwrite($fJunk, $sPara . "\n\n");
			$nCntJunk++;
		}
		
		continue;
	}
	//if doesn't begin in bold, but does contain a period or colon or a 
	//question mark, then probably a continuation of a previous definition.
	{
		//if the last character of the previous paragraph is a dash(-), then strip it.
		if (preg_match('/-\s*$/s', strip_tags($sDef)))
			$sDef = stripEnd($sDef) . $sPara;
		else
			$sDef .= ' ' . $sPara;
			
		$nCntContinueDef++;
	}
	else
	{
		fwrite($fJunk, $sPara . "\n\n");
		$nCntJunk++;
	}
}	

if ($sDef != '')
	processEntry($sDef);
	
fclose($fOut);
fclose($fJunk);
fclose($fSql);
print "Dictionary Definitions: $nCntDef, Continuing Definitions: " . 
	"$nCntContinueDef, Junk Phrases: $nCntJunk\n";
return;


//removes spaces and any final dashes(-) from the end of a string, while still
//preserving the html tags.  For instance: "in these times-</i> " becomes 
//"in these times</i>"
function stripEnd($s)
{
	$a = str_split($s);
	$nCnt = count($a) - 1;
	
	for (; $nCnt >= 0; $nCnt--)
	{
		if ($a[$nCnt] == '>')
		{
			for (; $nCnt >= 0 && $a[$nCnt] != '<'; $nCnt--)
				; //empty statement
		}
		elseif ($a[$nCnt] == '-')
		{
			$a[$nCnt] == '';
			break;
		}
		elseif ($a[$nCnt] == ' ' || $a[$nCnt] == "\t" || $a[$nCnt] == "\n") 
			$a[$nCnt] == '';
		else
			break;
	}
	
	$s = implode('', $a);
	
	return $s;
}

//function to strip dashes (-) from text, but leave them inside html tags
function stripDash($s)
{
	$sReturn = '';
	
	for ($bTag = false, $nEnd = strlen($s), $nCnt = 0; $nCnt < $nEnd; $nCnt++)
	{
		/*if ($s[$nCnt] == '<')
			$bTag = true;
		elseif ($s[$nCnt] == '>')
			$bTag = false;
			
		//173 is the character coding for the hyphen used to break up long
		//words in ISO-8859-1, ISO-8859-15 and Windows-1252. Change this number
		//if using a different caracter set.		
		if (($s[$nCnt] != '-' && ord($s[$nCnt]) != 173) || $bTag)
			$sReturn .= $s[$nCnt];
		*/
		if (ord($s[$nCnt]) != 173)
			$sReturn .= $s[$nCnt];		
	}
	
	return $sReturn;
}

//prepare the SQL query. Remove new lines, trailing and leading spaces, and escape
//the characters that need escaping.
function sqlPrep($s)
{
	return @mysql_real_escape_string(trim(preg_replace("/\r\n|\n/", ' ', $s)));
}

//strip duplicate html tags
function stripDupTags($s)
{
	$s = preg_replace('/<\/b><b>/', '', $s);
	$s = preg_replace('/<\/i><i>/', '', $s);
	$s = preg_replace('/<\/span><span style=\'font-variant:small-caps\'>/', '', $s);
	return $s;
}		

function processEntry($sEntrada)
{
	global $nCntDef, $fOut, $fSql;
	static $sLengua = 'Q';
	static $bFirstA = false;
	
	$sEntrada = stripDupTags(stripDash($sEntrada));
	$sDef = trim(strip_tags($sEntrada));
	fwrite($fOut, "<p>$sEntrada</p>\n");
	$nCntDef++;
				
	if (!preg_match('/[.?!]/', $sDef, $aMatch, PREG_OFFSET_CAPTURE))
		print "Bad Format: $sEntrada\n\n";
	else
	{
		//only include "?" or "!" in the clave, but not "."
		$sClave = substr($sDef, 0, $aMatch[0][1]) .
			($aMatch[0][0] == '.' ? '' : $aMatch[0][0]);
		
		if ($sClave == 'A, a')
		{
			if ($bFirstA)
				$sLengua = 'E';
			else
				$bFirstA = true;
		}

		$sDef = substr($sDef, $aMatch[0][1] + 1);

		$sIns = sprintf("INSERT INTO dic SET lengua = '%s', clave = '%s', " .
			"clave_son = '%s', definicion = '%s', definicion_son = '%s', " .
			"entrada = '%s', creado = '%s';\n\n", $sLengua, sqlPrep($sClave), 
			sqlPrep(soundsLike($sClave)), sqlPrep($sDef), sqlPrep(soundsLike($sDef)), 
			sqlPrep($sEntrada), date('Y-m-d H:i:s'));

		fwrite($fSql, $sIns);
	}
	return;
}
?>
