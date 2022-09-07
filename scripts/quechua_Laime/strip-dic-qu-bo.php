<?php
/************************************************************************
Program: strip-dic-qu-bo.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com, web: www.ciber-runa.net/ab
Project: Runasimipi Qespisqa Software, web: www.runasimipi.org
Created: 16 Mar 2007, (La Paz, Bolivia)

strip-dic-qu-bo.php removes all the excess html formatting from: 
Teofilo Laime Ajacopa, Efraín Cazazola, Félix Layme Pairumani, _DICCIONARIO BILINGÜE:
Iskay simipi yuyayk'ancha: Quechua - Castellano, Castellano - Quechua, Segunda edición mejorada 
(versión preliminar), La Paz, Bolivia, Enero, 2007.
Then, inserts the dictionary entries in a mysql database for web serving and a 
StarDict tab file for an electronic dictionary.

To call this program:
   php strip-dic-qu-bo.php [DICTIONARY-FILE] [OUTPUT-NAME]

Parameters in [ ] are optional.  
If no DICTIONARY-FILE, then set to 'DicQuechuaBolivia.html' by default.
If no OUTPUT-NAME, then set to 'DicQuechuaBolivia' by default, so the files
DicQuechuaBolivia-strip.html, DicQuechuaBolivia-junk.htm, DicQuechuaBolivia.sql
and DicQuechuaBolivia-stardict.tab will be created.

DICTIONARY-FILE is the dictionary which has been saved in HTML format. 
strip-dic.php will strip off all the excess html formatting and leave only hard returns <p>, 
italics <i>, bold <b>, and small-caps <span style='font-variant:small-caps'>. 
All the dictionary entries will be writen to the HTML file STRIPPED-FILE.  
Text which isn't written to the STRIPPED-FILE, will be written to the JUNK-FILE. 
In addition strip-dic-qu-bo.php will insert the dictionary entries into the MySQL database 
"qu" in the table "dics" and create a tabs file for StarDict.

After runing strip-dic-qu-bo.php, start up MySQL as administrator in MS Windows or 
as root in Linux/UNIX:
$ su [enter root password] 

Enter MySQL:
# mysql
mysql> SOURCE [sql-file];

       In this case [sql-file] would be replaced with something like: 
       mysql> SOURCE  DicQuechuaBolivia.sql

You have to run this program with PHP5 with the mysql extension.

If the "qu" MySQL database doesn't exist, you will have to create it. In Windows, 
log in as administrator, then open a DOS command window. If using Linux/UNIX, 
switch to the root user: 
$ su [enter password for root]

Then enter MySQL and create the 'qu' database:
# mysql
mysql> CREATE DATABASE qu;

To see if the 'qu' database  exists:
mysql> USE qu;

To see if the 'voc' table exists:
mysql> DESCRIBE voc;

If the 'voc' table exists, then delete the contents of it:
mysql> DELETE FROM voc;

If the 'voc' table doesn't exist, then you will have to create it:
mysql> CREATE TABLE IF NOT EXISTS voc (id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, 
	-> lengua ENUM('Q', 'E') NOT NULL, dic ENUM('A', 'L', 'G', 'S'), clave VARCHAR(40) NOT NULL, definicion TEXT NOT NULL, 
	-> entrada TEXT NOT NULL, clave_son VARCHAR(40) NOT NULL, definicion_son TEXT NOT NULL,
	-> creado DATETIME NOT NULL, actualizado TIMESTAMP NOT NULL, 
	-> INDEX idx_clave (clave, dic, lengua), FULLTEXT (clave), FULLTEXT(definicion), 
	-> FULLTEXT(clave_son), FULLTEXT(definicion_son), PRIMARY KEY(id));


Then check if the saphi user exists:
mysql> SHOW GRANTS FOR saphi;

If user saphi doesn't exist or there are no privileges for saphi, create them:
mysql> GRANT DELETE, SELECT, UPDATE, INSERT ON qu.voc TO saphi 
    -> INDENTIFIED BY 'secret';
[The password is not 'secret'--ask Amos Batto for it] 

Exit MySQL:
mysql> EXIT;

If in Linux/UNIX, exit the root user account:
# exit

NOTE: 
in qu.voc.lengua:
Q = quechua
E = español

in qu.voc.dic:
A = Academia Mayor de la Lengua Quechua's dictionary (Cusco)
L = Teófilo Laime Ajacopa's quechua diccionario (Bolivia)
G = Leoncio Gutiérrez Camacho's quechua dictionary (Apurímac)
S = La Salle-Abancay's quechua dictionary (Cusco-Ayacucho)

*****************************************************************************/

//set no max time for this program to run.
ini_set('max_execution_time', 0);
//set no memory limit
ini_set('memory_limit', '-1');
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
include("./soundslike.php");

$sPath = './';

if ($argc > 1)
	$sFDic = $argv[1];
else
	$sFDic = 'DicQuechuaBolivia.html';
	
if ($argc > 2)
	$sFName = $argv[2];
else
	$sFName = 'DicQuechuaBolivia';
		
$sFStripDic = $sFName . '-strip.html';
$sFJunk = $sFName . '-junk.html';
$sFSql =  $sFName . '.sql';
$sFStar =  $sFName . '-stardict.tab';

$sIn = file_get_contents($sFDic);
$sOut = '';

$nCntDef = $nCntContinueDef = $nCntJunk = 0;	//set counters to zero

//get the Quechua-Castellano section of the dictionary:
$nQuStart = strpos($sIn, '/a/.');	//go past the introduction
$nQuEnd = strpos($sIn, 'SUFIJOS'); //go to the end of the section Quechua - Castellano 

if ($nQuStart === false)
{
	print "No se encuentre el comienzo de sección quechua-castellano.\n";
	$nQuStart = 0;
}
	
if ($nQuEnd === false)
{
	print "No se encuentre el fin de sección quechua-castellano.\n";
	$nQuEnd = strlen($sIn);
}

$sQuIn = substr($sIn, $nQuStart, $nQuEnd - $nQuStart);

//get the Castellano-Quechua section of the dictionary:
$nEsStart = strpos($sIn, "/a/.", $nQuEnd);	//go to the definition of A

if ($nEsStart === false)
{
	print "No se encuentre el comienzo de sección castellano-quechua.\n";
	$nEsStart = 0;
}
	
$nEsEnd = strpos($sIn, 'SUFIJOS', $nEsStart); //go to the end of the section
	
if ($nEsEnd === false)
{
	print "No se encuentre el fin de sección castellano-quechua.\n";
	$nEsEnd = strlen($sIn);
}

$sEsIn = substr($sIn, $nEsStart, $nEsEnd - $nEsStart);

$sIn = $sQuIn . $sEsIn;
$sQuIn = $sEsIn = null;
$nInEnd = strlen($sIn);

for ($sCntIn = 0, $bSpan = false; $nCntIn < $nInEnd; $nCntIn++)
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
$sOut = preg_replace ('/\s+/', ' ', $sOut);
$sIn = null;
$aOut = preg_split('/<p>/i', $sOut);
$sOut = null;
$aOut = preg_replace('/\s*<\/p>/i', '', $aOut);

$fJunk = fopen($sFJunk, 'w');
$fOut = fopen($sFStripDic, 'w');
$fSql = fopen($sFSql, 'w');
$fStar = fopen($sFStar, 'w');
fwrite($fSql, "USE qu;\n");
$sDef = '';
$nCntIn = 0;

//Go back through the file and eliminate all the paragraphs which aren't dictionary
//definitions, like page numbers and the page headers. Also eliminate line breaks 
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
	//if doesn't begin in bold, but does contain a period, colon, exclamation mark, or  
	//question mark, then probably a continuation of a previous definition.
	elseif (preg_match('/[.:?!]/', $sPara))
	{
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
fclose($fStar);
print "Dictionary Definitions: $nCntDef, Continuing Definitions: " . 
	"$nCntContinueDef, Junk Phrases: $nCntJunk\n";
return;


//prepare the SQL query. Remove new lines, trailing and leading spaces, and escape
//the characters that need escaping.
function sqlPrep($s)
{
	//return @mysql_real_escape_string(trim(preg_replace("/\r\n|\n/", ' ', $s)));
	return trim(preg_replace("/\r\n|\n/", ' ', $s));
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
	global $nCntDef, $fOut, $fSql, $fStar;
	static $sLengua = 'Q';
	static $bFirstA = false;
	
	$sEntrada = stripDupTags($sEntrada);
	$sDef = trim(strip_tags($sEntrada));
	fwrite($fOut, "<p>$sEntrada</p>\n");
	$nCntDef++;
				
	//In Teofilo Laime's dictionary, key words can end with a period:
	//	<b>amisqa.<b/> <i>p.</i> Empalagado, hartado.
	//	<b>achu,</b> achuch. <i>s.</i> Represión a los niños.
	//Or an exclamation mark or a question mark:
	//	<b>achhalay!<b> <i>interj.</i> ¡Qué maravilla!
	//But sometimes the exclamation mark is followed by sinonyms or variations:
	//	<b>achachay!,</b> achachaw! <i>interj.</i> ¡Qué frío!
	//So have to test for each case.
	if (!preg_match('/\.|\? |! /', $sDef, $aMatch, PREG_OFFSET_CAPTURE))
		print "Bad Format: $sEntrada\n\n";
	else
	{
		//only include "?" or "!" in the clave, but not "."
		$sClave = substr($sDef, 0, $aMatch[0][1]) .
			($aMatch[0][0] == '.' ? '' : $aMatch[0][0]);
		$sClave = html_entity_decode($sClave, ENT_QUOTES, 'UTF-8');
				
		if ($sClave == '/a/')
		{
			if ($bFirstA)
				$sLengua = 'E';
			else
				$bFirstA = true;
		}

		$sDef = substr($sDef, $aMatch[0][1] + 1);
		$sDef = html_entity_decode($sDef, ENT_QUOTES, 'UTF-8');

		$sIns = sprintf("INSERT INTO voc SET lengua = '%s', dic = 'L', clave = '%s', " .
			"clave_son = '%s', definicion = '%s', definicion_son = '%s', " .
			"entrada = '%s', creado = '%s';\n\n", $sLengua, sqlPrep($sClave), 
			sqlPrep(soundsLike($sClave)), sqlPrep($sDef), sqlPrep(soundsLike($sDef)), 
			sqlPrep($sEntrada), date('Y-m-d H:i:s'));

		fwrite($fSql, $sIns);
		
		$aEntrada = splitDef($sEntrada);
		
		if ($aEntrada === false)
		{
			print "Unable to find definition:\n$sEntrada\n\n";
			fwrite($fStar, "$sClave\t$sDef\n");
		}
		else
			fwrite($fStar, $aEntrada[0] . "\t" . $aEntrada[1] . "\n");	
	}
	return;
}

//function to split a dictionary entry in HTML or Pango Markup Language 
//between its key and its definition. 
//Returns an array with the key in the first element and definition in second
//element or false if not found.
function splitDef($str)
{
	$startDef = 0;
	$lastCh = '';
	$cnt = 0;
	$found = false;
	
	for ($inTag = false; $cnt < strlen($str); $cnt++)
	{
		if (!$inTag && $str[$cnt] == '<')
			$inTag = true;
		elseif ($inTag && $str[$cnt] == '>')
			$inTag = false;
		elseif ($inTag)
			continue;
		elseif (($str[$cnt] == ' ' || $str[$cnt] == "\n" || $str[$cnt] == "\t") && 
				($lastCh == '.' || $lastCh == '!' || $lastCh == '?'))
		{
			$found = true;
			break;
		}
		else 
			$lastCh = $str[$cnt];
	}
	
	if ($found)
	{
		$def = trim(substr($str, $cnt + 1));
		//$def = html_entity_decode($def, ENT_QUOTES, 'UTF-8');
		$def = preg_replace('/^<\/B>/i', '', $def);
		$def = str_ireplace("<span style='font-variant:small-caps'>", 
			'<span variant="smallcaps">', $def); 
		$def = preg_replace('/[^<]\//', '//', $def);
		
		$clave = trim(substr($str, 0, $cnt));
		$clave = html_entity_decode($clave, ENT_QUOTES, 'UTF-8');
		
		if (preg_match("/<span style='font-variant:small-caps'>.*<\/span/i",
			$clave, $matches, PREG_OFFSET_CAPTURE));
		{
			$clave = substr($clave, 0, $matches[0][1]) .
				strtoupper($matches[0][0]) . substr($clave, $matches[0][1] + 
				strlen($matches[0][0]));
		}

		$clave = trim(strip_tags($clave));
		$clave = preg_replace('/\.$/', '', $clave);
		$clave = preg_replace('/[^<]\//', '//', $clave);
		
		return array($clave, $def);
	}
	else
		return false;
}
		
?>
