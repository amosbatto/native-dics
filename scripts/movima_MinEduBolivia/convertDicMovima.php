<?php
/****************************************************************************
Program: convertDicMovima.php
Author:  Amos B. Batto, email: amosbatto AT yahoo DOT com
Project: ILLA, (www.illa-a.org) / SimiDic (www.simidic.org)
Created: 2014-01-04, (La Paz, Bolivia)
License: Public domain
*****************************************************************************/
$help = <<<HELP
convertDicMovima.php converts the Diccionario Movima (2007) to be used in 
StarDict, GoldenDict and SimiDic. It creates a Movima-Castellano dictionary,
then uses its content to create a Castellano-Movima dictionary.

To call this program:
   php convertDicMovima.php DIC-MOVIMA.txt [OUTPUT]

For help:
   php convertDicMovima.php -h 

Parameters:   
DIC-MOVIMA.txt The Diccionario Movima file saved as plain text.
OUTPUT         Prefix for output files. If not specified, then the same as
               the filename of DIC-MOVIMA.txt without its extension. 

The script will output 2 StarDict TAB files:
- OUTPUT-mov_es.tab (Movima-Castellano in plain text)
- OUTPUT-es_mov.tab (Castellano-Movima in plain text)
In addition, it creates an HtML file containing the generated Castellano-Movima
dictionary:
- OUTPUT-es_mov-generado.html

Then, the script calls StarDict’s tabfile utility to generate the binary 
StarDict files, which can be used in both StarDict and GoldenDict. The script 
edits the generated IFO files so StarDict and GoldenDict can display 
information about the dictionaries.

After executing this script, run SimiDic-Builder and use The TAB files in 
HTML format to create the SimiDic Sqlite3 databases.

Note: Due to errors with 

Requirements:
PHP 5 and StarDict Tools (which includes the tabfile utility).
Run this script in Linux/UNIX with the locale "es_BO.utf-8" installed.
Use this command to check whether installed: locale -a
 
HELP;

//configure PHP:
ini_set('max_execution_time', 0);  //set no max time for this program to run.
ini_set('memory_limit', '-1');  //set no memory limit
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
setlocale(LC_ALL, 'es_BO.utf-8');
mb_internal_encoding("UTF-8");

if ($argc <= 1 or preg_match('@^(-h|--help|/h|help|ayuda)$@i', $argv[1]))
	exit($help);

if ($argc < 2)
	exit("Error: Too few parameters.\n\n" . $help);
elseif ($argc > 3)
	exit("Error: Too many parameters.\n\n" . $help);
	
$sFDic = $argv[1];

if ($argc > 2) {
	$sFOutput = $argv[2];
}
else {
	$aParts = pathinfo($sFDic);
	$sFOutput = $aParts['filename'];
}

if (false === ($sIn = file_get_contents($sFDic)))
	exit("Error: Unable to open file '$sFDic'.\n");
	
//Get the text in the Movima-Castellano section:
if (false === ($nStartMov = mb_strpos($sIn, "Achapu'u. Palabra que se pronuncia")))
	exit("Error: Unable to find the start of Movima-Castellano section.\n");
	
$sEnd = 'Yełyełwanra:ni. Aliciado.';
	
if (false === ($nEndMov = mb_strpos($sIn, $sEnd)))
	exit("Error: Unable to find the end of Movima-Castellano section.\n");	

$nEndMov += mb_strlen($sEnd);
$sInMov = extractStr($sIn, $nStartMov, $nEndMov);
$aEntriesMov = extractEntries($sDic = $sInMov, $sOutputFileName = "$sFOutput-mov_es");

//generate the Castellano-Movima dictionary from the content of the Movima-Castellano section: 
$aEntriesEs = array();

foreach ($aEntriesMov as $aEntryMov) {
	
	//if definition contains commas or periods, then create multiple entries in the Castellano-Movima section
	$aMultipleDefs = preg_split('@[\.,;] @', $aEntryMov['def']);
	
	for ($i = 0; $i < count($aMultipleDefs); $i++) {
		$sSingleDef = $aMultipleDefs[$i];				 
		
		//if the definition contains the word "etc", then throw it out:
		if (preg_match('/\b(etc\.)/i', $sSingleDef)) {
			print "Excluding definition for key '{$aEntryMov['key']}': $sSingleDef\n";
			continue;
		}
		//if the next definition contains the word "ejemplo", then add it in parenthesis 
		//to the end of the current definition:
		if ($i + 1 < count($aMultipleDefs) and preg_match('/\b(ejemplo)/i', $aMultipleDefs[$i + 1])) {
			$sNextDef = $aMultipleDefs[$i + 1];
			$sNextDef = preg_replace('/\.$/', '', $sNextDef);
			$sSingleDef .= ' (' . $sNextDef . ')';
			$i++; //to skip the next definition
		}
		
		//remove beginning "¿" and "¡" and final "." from the definition
		$sFirst = mb_substr($sSingleDef, 0, 1);
		if ($sFirst == '¿' or $sFirst == '¡')
			$sSingleDef = mb_substr($sSingleDef, 1);
			
		if (mb_substr($sSingleDef, mb_strlen($sSingleDef) - 1) == '.')
			$sSingleDef = mb_substr($sSingleDef, 0, mb_strlen($sSingleDef) - 1);	
				
		$sDef1 = ucfirst($aEntryMov['key']);
		$sDef1 = preg_replace('/^ł/', 'Ł', $sDef1);
		
		if (preg_match('/\bapellido/i', $sSingleDef))
			$sKey1 = $sSingleDef;
		else 
			$sKey1 = mb_strtolower($sSingleDef);
		
		//move text in parentheses from key to definition:
		$nParen = mb_strpos($sKey1, ' (');	
		if ($nParen !== false) {
			$sParen = trim(mb_substr($sKey1, $nParen));
			$sKey1   = mb_substr($sKey1, 0, $nParen);
			$sDef1   = $sParen . ' ' . $sDef1; 
		}
				
		//check if the key already exists. If so, get the next free key.			
		$keyToFind = $sKey1;

		for ($ii = 2; array_key_exists($keyToFind, $aEntriesEs); $ii++) {
			$keyToFind = $sKey1 . $ii;
		}	
		
		$aEntriesEs[$keyToFind] = array('key' => $sKey1, 'def' => $sDef1);
	}
}			 	

ksort($aEntriesEs, SORT_LOCALE_STRING);
$fTabEs = fopen($sFOutput . '-es_mov.tab', 'w');
$fEsHtml = fopen($sFOutput . '-es_mov-generado.html', 'w');
$header = <<<HEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<html>
<head>
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
	<title>Diccionario Castellano-Movima</title>
	<meta name="author" content="Amos Batto" >	
	<META NAME="CREATED" CONTENT="20050413;3422600">
	<META NAME="CHANGEDBY" CONTENT="Amos Batto">
	<META NAME="CHANGED" CONTENT="20131213;2420600">
	<STYLE>
		<!-- 
		BODY,DIV,TABLE,THEAD,TBODY,TFOOT,TR,TH,TD,P {font-family:"serif";}
		 -->
	</STYLE>
</head>
<body>
<center>
<h1>Castellano-Movima</h1>
<p><big><b>del Equipo de Educación Mapuche Wixaleyiñ</b></big></p>
<p></p>
<p><b>
</center>
<a href="#Aes">A</a><br>
<a href="#Bes">B</a><br>
<a href="#Ces">C</a><br>
<a href="#Des">D</a><br>
<a href="#Ees">E</a><br>
<a href="#Fes">F</a><br>
<a href="#Hes">H</a><br>
<a href="#Ies">I</a><br>
<a href="#Jes">J</a><br>
<a href="#Les">L</a><br>
<a href="#Mes">M</a><br>
<a href="#Nes">N</a><br>
<a href="#Nes">Ñ</a><br>
<a href="#Oes">O</a><br>
<a href="#Pes">P</a><br>
<a href="#Qes">Q</a><br>
<a href="#Res">R</a><br>
<a href="#Ses">S</a><br>
<a href="#Tes">T</a><br>
<a href="#Ues">U</a><br>
<a href="#Ves">V</a><br>
<a href="#Yes">Y</a><br>
<a href="#Zes">Z</a>
</b></p>
<hr>
<h2 id="Aes"><b>A</b></h2>
		
HEADER;

fwrite($fEsHtml, $header);
$aLettersEs = array('A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'L', 'M', 'N', 'Ñ',
	 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'Y', 'Z');
$nCurLetter = 0;

foreach ($aEntriesEs as $aEntryEs) {
	if (!preg_match('@[\.\?!]$@', $aEntryEs['def']))
		$aEntryEs['def'] .= '.';
	
	$sLetter = mb_strtoupper(mb_substr($aEntryEs['key'], 0, 1));
		
	if ($sLetter != $aLettersEs[$nCurLetter] and $sLetter == $aLettersEs[$nCurLetter + 1]) {
		$nCurLetter++;
		fwrite($fEsHtml, "<h2 id=\"{$sLetter}es\"><b>$sLetter</b></h2>\n");
	}
		
	fwrite($fEsHtml, "<p><b>{$aEntryEs['key']}.</b> {$aEntryEs['def']}</p>\n");
		
	$sEntry = $aEntryEs['key'] . "\t" . $aEntryEs['def'] . "\n";		
	fwrite($fTabEs, $sEntry);
}	
fclose($fEsHtml);
fclose($fTabEs);

//create binary dictionaries for StarDict and GoldenDict:
createDic(
	$sBookname = 'Movima–Castellano (PEIB-Movima)',
	$sDir      = 'mov_es-peib-movima',
	$sTabFile  = $sFOutput . '-mov_es.tab'
);

createDic(
	$sBookname = 'Castellano–Movima (PEIB-Movima)',
	$sDir      = 'es_mov-peib-movima',
	$sTabFile  = $sFOutput . '-es_mov.tab'
);

return;

/*******************************************************************************************
function to extract the entries from a dictionary and create plain text TAB files.
Parameters:
 $sDic: String containing the content of the Movima section.
 $sOutputFileName: Prefix of the generated TAB files. ".tab" extension will be added.
Return Value:
 An array of associative arrays containing entries in the dictionary. 
 Each entry contains: array('key' => '...', 'def' => '...') 
********************************************************************************************/ 
function extractEntries($sDic, $sOutputFileName) {
	//open TAB file for writing:
	if (!($fTab = fopen($sOutputFileName . '.tab', 'w')))
		exit("Error: Unable to open file '$sOutputFileName.tab' for writing.\n");
	
	$aParagraphs = explode("\n", $sDic); 
	$aEntries = array(); //empty array to hold the entries in the dictionary
		
	//loop through the paragraphs, pulling out the keys and definitions and inserting them in $aEntries
	for ($iPara = 0; $iPara < count($aParagraphs); $iPara++) {
		$sPara = trim($aParagraphs[$iPara]);
		//$sPara = preg_replace('/\s+/', ' ', $sPara);  //replace all whitespace with a single space
	
		//ignore lines which are empty or letter headings 	
		if (empty($sPara) or preg_match('/^[A-ZÑ]{1,3}$/', $sPara)) {
			print("Removing entry: $sPara\n"); 
			continue;
		}
		
		//find the key
		if (preg_match('@[\.\?!] @', $sPara, $aEntry, PREG_OFFSET_CAPTURE)) {
			$sKey = trim(substr($sPara, 0, $aEntry[0][1] + 2));
			$sKey = preg_replace('/\.$/', '', $sKey);	
			$sDef = trim(substr($sPara, $aEntry[0][1] + 2));
		}
		//skip entries where can't find definition.
		else {
			print "Unable to find start of definition in entry:\n$sPara\n\n";
			continue;
		}		
		
		//remove beginning "¿" and "!" and final "." from the definition
		if (mb_substr($sKey, 0, 1) == '¿' or mb_substr($sKey, 0, 1) == '¡')
			$sKey = mb_substr($sKey, 1);
			
		if (mb_substr($sKey, mb_strlen($sKey) - 1) == '.')
			$sKey = mb_substr($sKey, 0, mb_strlen($sKey) - 1);
		
		$sKey = mb_strtolower($sKey);
		$aEntries[] = array('key' => $sKey, 'def' => $sDef);
		fwrite($fTab, "$sKey\t$sDef\n");
	}

	fclose($fTab);
	return $aEntries;
}

//function to create the binary StarDict files (dict.dz, idx, ifo) from a plain text TAB file and 
//insert information in the generated IFO file. Then moves the generated files into a directory named $sDir 
function createDic($sBookname, $sDir, $sTabFile) {
	$sAuthor = "PEIB-Movima";
	$sDesc = "Melvin Rossel Yoqui, et al., Diccionario Movima, Programa de Educación Intercultural Bilingüe-Movima, Santa Cruz (2007), 72pp.";

	//run StarDict's tabfile to create the dictionaries for StarDict and GoldenDict
	system("tabfile ". $sTabFile);
	
	$aParts   = pathinfo($sTabFile);
	$sIfoFile = $aParts['filename'] . '.ifo';
	$sIfo = file_get_contents($sIfoFile);

	if (!preg_match('/wordcount=([0-9]+)/', $sIfo, $aMatch))
		exit("Error finding 'wordcount' in file '$sIfoFile'.\n"); 
	else
		$nWordCnt = $aMatch[1];
			
	if (!preg_match('/idxfilesize=([0-9]+)/', $sIfo, $aMatch))
		exit("Error finding 'idxfilesize' in file '$sIfoFile'.\n"); 
	else
		$nIdxSize = $aMatch[1];
			
	$sIfo = "StarDict's dict ifo file\n" .
		"version=2.4.2\n" .
		"wordcount=$nWordCnt\n" .
		"idxfilesize=$nIdxSize\n" .
		"bookname=$sBookname\n" .
		"description=$sDesc\n" .
		"author=$sAuthor\n" .
		"date=2007\n" .
		"website=http://www.illa-a.org/wp/diccionarios\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=m\n"; 
		
	file_put_contents($sIfoFile, $sIfo);

	//Create directories for the GoldenDict and StarDict files if they don't exist
	if (file_exists($sDir))
		system("rm $sDir/*");
	else
		mkdir($sDir, 0755);
	
	system("mv {$aParts['filename']}.*  $sDir");
	return;
}

//function like mb_substr(), but it takes as its third parameter the position 
//where the substring ends, rather than the length of the substring. If the position
//is negative, then it substracts that many characters from the end of the string.
//if third parameter is less than second, then returns false
//Ex: extractStr("abcde", 1, 3) returns "bc"; extractStr("abcde", 1, -1) returns "bcd"
function extractStr($s, $nStartPos, $nEndPos) {
	if ($nEndPos === false or $nEndPos === null) {
		return mb_substr($s, $nStartPos);
	}
	elseif ($nEndPos < 0) {
		$nEndPos = mb_strlen($s) - $nEndPos;
	}

	if ($nEndPos < $nStartPos)
		return false;

	return mb_substr($s, $nStartPos, $nEndPos - $nStartPos);
}


?>
