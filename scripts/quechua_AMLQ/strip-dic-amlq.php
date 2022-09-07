<?php
/************************************************************************
Program: strip-dic-amlq.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com, web: www.ciber-runa.net/ab
Project: Runasimipi Qespisqa Software, web: www.runasimipi.org
Created: 27 Nov 2006, Universidad Nacional Micaela Bastidas de Apurimac (Abancay, Peru)

strip-dic-amlq.php removes all the excess html formatting from _Quechua-Español 
Diccionario: Simi Taqe Runasimi-Español_ by the Academia Mayor de la Lengua 
Quechua in Cusco, Peru, and inserts the dictionary entries in a mysql database 
for web serving, in a tab file for use in StarDict electronic dictionary and in a 
SDictionary file for use in the Babiloo, JaLingo or SDict electronic dictionaries.

To call this program:
  php strip-dic-amlq.php [DICTIONARY-FILE] [OUTPUT-NAME] 
 
Parameters in [] are optional. 
DICTIONARY-FILE is the AMLQ dictionary in HTML format. If no DICTIONARY-FILE, then 
set to 'AMLQ-Dic.html' by default. To create this file, download the AMLQ dictionary
from http://www.runasimipi.org in OpenOffice save as HTML. (In OpenOffice go to 
Options > Load/Save> HTML Compatibility and set Export to "Netscape Navigator" 
and Character Set to "Unicode (UTF-8)".)  

OUTPUT-NAME is the name to prepend to the following files which will be created:
  OUTPUT-NAME-strip.html : Dictionary file with stripped HMTL output
  OUTPUT-NAME-junk.html  : Whatever text was discarded from the DICTIONARY-FILE
  OUTPUT-NAME.sql        : SQL statements to insert dictionary entries in a MySQL database
  OUTPUT-NAME.tab        : StarDict tab file 
  OUTPUT-NAME-QU-ES.sdct : Quechua - español dictionary in SDictionary format 
  OUTPUT-NAME-ES-QU.sdct : Español - Quechua dictionary in SDictionary format 
If no OUTPUT-NAME is given, then will set to 'AMLQ-Dic' by default. 

strip-dic-amlq.php will strip off all the excess html formatting and leave only 
hard returns <p>, italics <i>, bold <b>, and small-caps 
<span style='font-variant:small-caps'>. All the dictionary entries will be
writen to the OUTPUT-NAME-strip.html.  Text which isn't written to OUTPUT-NAME-strip.html, 
will be written to OUTPUT-NAME-junk.html. 

Then strip-dic-amlq.php will create the OUTPUT-NAME.sql, which consists of SQL statements
to insert the dictionary entries into the MySQL database "amlq" in the table "dic".

Then the StarDict tab file OUTPUT-NAME.tab will be created.

Finally the SDictionary files OUTPUT-NAME-QU-ES.sdct and OUTPUT-NAME-ES-QU.sdct will 
be created to use in desktop electronic dictionaries such as Babiloo, JaLingo or SDict.


-*- Creating the MySQL database -*-
After runing strip-dic.php, start up MySQL as administrator in MS Windows or 
as root in Linux/UNIX:
$ su [enter root password] 

Enter MySQL:
# mysql
mysql> source SQL-FILE;

You have to run this program with PHP5 with the mysql extension.

If the amlq MySQL database doesn't exist, you will have to create it. In Windows, 
log in as administrator, then open a DOS command window. If using Linux/UNIX, 
switch to the root user: 
$ su [enter password for root]

Then enter MySQL and create the 'amlq' database:
# mysql
mysql> CREATE DATABASE amlq;

To see if the 'amlq' database  exists:
mysql> USE amlq;

To see if the 'dic' table exists:
mysql> describe dic;

If the 'dic' table exists, then delete the contents of it:
mysql> delete from dic;

If the 'dic' table doesn't exist, then you will have to create it:
mysql> CREATE TABLE IF NOT EXISTS dic (id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, 
	-> lengua ENUM('Q', 'E') NOT NULL, clave VARCHAR(40) NOT NULL, definicion TEXT NOT NULL, 
	-> entrada TEXT NOT NULL, clave_son VARCHAR(40) NOT NULL, definicion_son TEXT NOT NULL,
	-> creado DATETIME NOT NULL, actualizado TIMESTAMP NOT NULL, 
	-> INDEX idx_clave (clave, lengua), FULLTEXT (clave), FULLTEXT(definicion), 
	-> FULLTEXT(clave_son), FULLTEXT(definicion_son), PRIMARY KEY(id));
	
Then check if the amlq user exists:
mysql> SHOW GRANTS FOR amlq;

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
	$sFDic = 'AMLQ-Dic.htm';
	
if ($argc > 2)
	$sFSql = $argv[2];
else
	$sFSql = 'AMLQ-Dic.sql';

if ($argc > 3)
	$sFSDictQu = $argv[3];
else
	$sFSDictQu = 'AMLQ-Dic-QU-ES.sdct';

if ($argc > 4)
	$sFSDictEs = $argv[4];
else
	$sFSDictEs = 'AMLQ-Dic-ES-QU.sdct';

if ($argc > 5)
	$sFStripDic = $argv[5];
else
	$sFStripDic = 'AMLQ-Dic-strip.htm';
	
if ($argc > 6)
	$sFJunk = $argv[6];
else
	$sFJunk = 'AMLQ-Dic-junk.htm';


$sIn = file_get_contents($sFDic);
$sOut = '';
$nCntDef = $nCntContinueDef = $nCntJunk = 0;
$nCntIn = strpos($sIn, "XXXI");	//go past the introduction
$nInEnd = strpos($sIn, "DICCIONARIO QUECHUA"); //go to the end

if ($nCntIn === false)
	$nCntIn = 0;
else 
	$nCntIn += 4;
	
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

$sIn = null;
$aOut = preg_split('/<p>/', $sOut);
$sOut = null;
$aOut = preg_replace('/\s*<\/p>\s*$/', '', $aOut);

$fJunk = fopen($sFJunk, 'w');
$fOut = fopen($sFStripDic, 'w');
$fSql = fopen($sFSql, 'w');
fwrite($fSql, "USE amlq;\n");

sHdrQu = """<header> 
title = Simi Taqe Runasimi-Español por AMLQ de Qosqo
copyright = GNU General Public License 3 or later
version = 0.1
w_lang = qu
a_lang = es
</header>
#
"""
sHdrEs = """<header> 
title = Simi Taqe Español-Runasimi por AMLQ de Qosqo
copyright = GNU General Public License 3 or later
version = 0.1
w_lang = es
a_lang = qu
</header>
#
"""

$fSDictQu = fopen($sFSDictQu, 'w');
fwrite($fSDictQu, sHdrQu);
$fSDictEs = fopen($sFSDictEs, 'w');
fwrite($fSDictEs, sHdrEs);


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
	//if doesn't begin in bold, but does contain a period or colon or more than 1 
	//question mark, then probably a continuation of a previous definition.
	elseif (preg_match('/[.:]/', $sPara) || preg_match_all('/\?/', $sPara, $m) > 1)
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
