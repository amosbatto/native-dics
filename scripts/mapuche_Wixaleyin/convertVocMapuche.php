<?php
/****************************************************************************
Program: convertVocMapuche.php
Author:  Amos B. Batto, email: amosbatto AT yahoo DOT com
Project: ILLA, (www.illa-a.org) / SimiDic (www.simidic.org)
Created: 2014-01-01, (La Paz, Bolivia)
License: Public domain
*****************************************************************************/
$help = <<<HELP
convertVocMapuche.php converts the Vocabulario Mapuche-Castellano del Equipo de
Educación Mapuche Wixaleyiñ (2013) to be used in StarDict, GoldenDict and 
SimiDic.

To call this program:
   php convertVocMapuche.php VOC-MAPUCHE.html [OUTPUT]

For help:
   php convertVocMapuche -h 

Parameters:   
VOC-MAPUCHE.html The Vocabulario Mapuche-Castellano file saved as HTML.
OUTPUT           Prefix for output files. If not specified, then the same as
                 the filename of VOC-MAPUCHE.html without its extension. 

The script will strip out all HTML tags except <FONT COLOR="...">, <I>, and
<B> in the definitions. It then creates the following 4 StarDict TAB files:
- OUTPUT-map_es-html.tab (Mapuche-Castellano in HTML for GoldenDict & SimiDic)
- OUTPUT-map_es-html.tab (Mapuche-Castellano in Pango for StarDict)
- OUTPUT-es_map-html.tab (Castellano-Mapuche in HTML for GoldenDict & SimiDic)
- OUTPUT-es_map-html.tab (Castellano-Mapuche in Pango for StarDict)

Because the Castellano-Mapuche section lacks many words, the text in the 
Mapuche-Castellano section is combined with the Castellano-Mapuche section to 
generate the HTML file:
- OUTPUT-es_map-generado.html
It is hoped that the Equipo de Educación Mapuche Wixaleyiñ will manually edited
this file to expand the current Vocabulario Mapuche-Castellano. 

Finally, the script calls StarDict’s tabfile utility to generate the binary 
StarDict files, which can be used in StarDict and GoldenDict. The script edits
the generated IFO files so StarDict and GoldenDict can display information 
about the Vocabulario.

After executing this script, run SimiDic-Builder and use The TAB files in 
HTML format to create the SimiDic Sqlite3 databases.

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

$sPath = './';

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
	
//Get the text inside the <multicol>...</multicol> tags, which contains the 
//Mapuche-Castellano section:
if (!preg_match('@<MULTICOL.*COLS=2[^>]*>@imU', $sIn, $aVocMap, PREG_OFFSET_CAPTURE))
	exit("Error: Unable to find the start of Mapuche-Castellano section.\n");

$nStartMap = $aVocMap[0][1] + mb_strlen($aVocMap[0][0]);

if (false === ($nEndMap = mb_stripos($sIn, '</MULTICOL>', $nStartMap)))
	exit("Error: Unable to find the end of Mapuche-Castellano section");

$sInMap = mb_substr($sIn, $nStartMap, $nEndMap - $nStartMap);

//Get the text inside the <multicol>...</multicol> tags, which contains the 
//Castellano-Mapuche section:
if (!preg_match('@<MULTICOL[^>]*>@mi', $sIn, $aVocEs, PREG_OFFSET_CAPTURE, $nEndMap + 11))
	exit("Error: Unable to find the Castellano-Mapuche section.\n");

$nEndEs = mb_stripos($sIn, '</MULTICOL>', $nEndMap + 11);
$sInEs = extractStr($sIn, $aVocEs[0][1] + mb_strlen($aVocEs[0][0]), $nEndEs);

$aEntriesMap = extractEntries($sDic = $sInMap, $sOutputFileName = "$sFOutput-map_es");
$aEntriesEs  = extractEntries($sDic = $sInEs,  $sOutputFileName = "$sFOutput-es_map");

//generate the Castellano-Mapuche.html file from the combined content of the Mapuche-Castellano 
//and Castellano-Mapuche sections:
$aEntriesEsComb = array();

foreach ($aEntriesEs as $aEntryEs) {	
	//check if the key already exists. If so, get the next free key.			
	$i = 2;
	$keyToFind = $aEntryEs['key'];

	for (; array_key_exists($keyToFind, $aEntriesEsComb); $i++) {
		$keyToFind = $aEntryEs['key'] . $i;
	}
		
	$aEntriesEsComb[$keyToFind] = array(
		'key'     => $aEntryEs['key'], 
		'grammar' => '', 
		'def'     => $aEntryEs['def']
	);
}

foreach ($aEntriesMap as $aEntryMap) {		
	$sDef = preg_replace('/ {2,}/', ' ', strip_tags($aEntryMap['def']));
	$sKey = strip_tags($aEntryMap['key']);
	$sGrammar = 'deriv\.[/ ]*|adj\.[/ ]*|adv\.[/ ]*|aux\.[/ ]*|complem\.[/ ]*|conj\.[/ ]*|desin\.[/ ]*|' .
		'fig\.[/ ]*|interj\.[/ ]*|interrog\.[/ ]*|modif\.[/ ]*|num\.[/ ]*|part\.[/ ]*|pospos\.[/ ]*|prep\.[/ ]*|' .
		'pron\.[/ ]*|s\.[/ ]*|trans\.[/ ]*|v\.trans\.[/ ]*|v\.trasl\.[/ ]*|v\.[/ ]*|voc\.[/ ]*';
	$aDefs = array();
	
	//if multiple definitions:
	if (preg_match_all('/\b[1-9] [^0-9]+/', $sDef, $aDefParts) and count($aDefParts[0]) >= 2) {
			
		//first check if there is a general grammatical part for all definitions:
		$sGrammarGeneral = '';  
		if (preg_match("@^($sGrammar)+@", $sDef, $aMatch)) {
			$sGrammarGeneral = $aMatch[0];
		}
			
		foreach ($aDefParts[0] as $sDefPart) {		
			//extract just the definition and grammatical part and exclude everything else
			if (preg_match('/^[1-9] (.+)(Ej:|\*|Sinón:|Ver:|Es mejor:|$)/U', $sDefPart, $aMatch)) {
				$sGrammarOnly = '';
				$sDefOnly     = $aMatch[1];
				
				//extract grammatical part, if exists
				if (preg_match("@(($sGrammar)+)(.*)$@", $aMatch[1], $aMatchGrammar)) {
					$sGrammarOnly = trim($aMatchGrammar[1]);
					$sDefOnly     = trim($aMatchGrammar[3]);
				}
				//insert general grammatical part if not found in this definition part
				if (empty($sGrammarOnly)) 
					$sGrammarOnly = $sGrammarGeneral;
				
				$aDefs[] = array('key' => $sKey, 'grammar' => $sGrammarOnly, 'def' => $sDefOnly);
			}
			else {
				print "Excluding in '$sKey': $sDefPart\n\n";
			}
		}
	}
	else { //if only one definition
		//extract just the definition and grammatical part and exclude everything else
		if (preg_match('/^(.+)(Ej:|\*|Sinón:|Ver:|Es mejor:|$)/U', $sDef, $aMatch)) {
			$sGrammarOnly = '';
			$sDefOnly     = $aMatch[1];
			
			//extract grammatical part, if exists
			if (preg_match("@(($sGrammar)+)(.*)$@", $aMatch[1], $aMatchGrammar)) {
				$sGrammarOnly = trim($aMatchGrammar[1]);
				$sDefOnly     = trim($aMatchGrammar[3]);
			}
			
			$aDefs[] = array('key' => $sKey, 'keyPango' => $aEntryMap['key'], 
				'grammar' => $sGrammarOnly, 'def' => $sDefOnly);			
		}
		else {
			print "Can't process '$sKey': $sDef\n\n";
		}
	}	
	
	foreach ($aDefs as $aDef) {
		//eliminate periods, commas and spaces at end of definitions: 
		$aDef['def'] = preg_replace('/[\.,; ]+$/', '', $aDef['def']);
		
		//move any text in beginning parentheses to the end of key:
		//ex: (trato recíproco) tía paterna -> tía paterna (trato recíproco)
		$sParentheses = '';
		if (preg_match('/^(\(.+\))(.+)$/', $aDef['def'], $aMatchParentheses)) {
			$aDef['def'] = trim($aMatchParentheses[2]);
			$sParentheses = $aMatchParentheses[1];
		}
			
		//if definition contains commas, then create en entry in the Castellano-Mapuche section for each one
		$aMultipleDefs = preg_split('/[,;] */', $aDef['def']);
		
		foreach ($aMultipleDefs as $sSingleDef) {
			//check if the key already exists. If so, get the next free key.			
			$i = 2;
			$keyToFind = $sSingleDef;

			for (; array_key_exists($keyToFind, $aEntriesEsComb); $i++) {
				$keyToFind = $sSingleDef . $i;
			}	
			if (!empty($aDef['key']) and !empty($sSingleDef) and $sSingleDef != 'etc') {
				$aEntriesEsComb[$keyToFind] = array(
					'key'     => trim($sSingleDef . ' ' . $sParentheses), 
					'grammar' => $aDef['grammar'], 
					'def'     => $aDef['key']
				);
			}
		}
	}	
}			 	

ksort($aEntriesEsComb, SORT_LOCALE_STRING);
//$fEsMapPango = fopen($sFOutput . '-es_map-comb-pango.tab', 'w');
//$fEsMapHtml  = fopen($sFOutput . '-es_map-comb-html.tab', 'w');

$fOutputHtml = fopen($sFOutput . '-es_map-generado.html', 'w');
$header = <<<HEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<html>
<head>
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
	<title>Vocabulario Castellano-Mapuche</title>
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
<h1>Vocabulario Castellano-Mapuche</h1>
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

fwrite($fOutputHtml, $header);
$aLettersEs = array('A', 'B', 'C', 'D', 'E', 'F', 'H', 'I', 'J', 'L', 'M', 'N',
	 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'Y', 'Z');
$nCurLetter = 0;

foreach ($aEntriesEsComb as $aEntryEsComb) {
	if (!preg_match('@[\.\?!](</[^>]+>)*$@', $aEntryEsComb['def']))
		$aEntryEsComb['def'] .= '.';
		
	$sDefPango = $aEntryEsComb['key'] . 
		"\t<span fgcolor=\"#008000\"><i>{$aEntryEsComb['grammar']}</i></span> {$aEntryEsComb['def']}\n";
	$sDefHtml = str_replace('<span fgcolor="#008000">', '<font color="green">', $sDefPango);
	$sDefHtml = str_replace('</span>', '</font>', $sDefHtml);
	$sOutputHtml = "<p><b>{$aEntryEsComb['key']}</b> <font color=\"green\"><i>{$aEntryEsComb['grammar']}</i></font> {$aEntryEsComb['def']}</p>\n";
		
	//fwrite($fEsMapPango, $sDefPango);
	//fwrite($fEsMapHtml,  $sDefHtml);
	
	$sLetter = strtoupper(mb_substr($aEntryEsComb['key'], 0, 1));
	//if (mb_strlen($aEntryEsComb['key']) >= 2 and mb_substr($aEntryEsComb['key'], 0, 2) == 'll')
	//	$sLetter = 'LL';
	
	if ($sLetter != $aLettersEs[$nCurLetter] and $sLetter == $aLettersEs[$nCurLetter + 1]) {
		$nCurLetter++;
		fwrite($fOutputHtml, "<h2 id=\"{$sLetter}es\"><b>$sLetter</b></h2>\n");
	}
	
	fwrite($fOutputHtml, $sOutputHtml);
}	
		
//fclose($fEsMapPango);
//fclose($fEsMapHtml);
fwrite($fOutputHtml, '</body></html>');
fclose($fOutputHtml);

//create binary dictionaries for StarDict and GoldenDict:
createDic(
	$sBookname = 'Mapuche–Castellano (Educación Mapuche Wixaleyiñ)',
	$sDir      = 'map_es-wixaleyin-html',
	$sTabFile = $sFOutput . '-map_es-html.tab'
);
createDic(
	$sBookname = 'Mapuche–Castellano (Educación Mapuche Wixaleyiñ)',
	$sDir      = 'map_es-wixaleyin-pango',
	$sTabFile = $sFOutput . '-map_es-pango.tab'
);
createDic(
	$sBookname = 'Castellano–Mapuche (Educación Mapuche Wixaleyiñ)',
	$sDir      = 'es_map-wixaleyin-html',
	$sTabFile  = $sFOutput . '-es_map-html.tab'
);
createDic(
	$sBookname = 'Castellano–Mapuche (Educación Mapuche Wixaleyiñ)',
	$sDir      = 'es_map-wixaleyin-pango',
	$sTabFile  = $sFOutput . '-es_map-pango.tab'
);

return;

/*******************************************************************************************
function to extract the entries from a dictionary and create HTML and Pango TAB files.
Parameters:
 $sDic: String containing the content of the Mapuche or Castellano section
 $sOutputFileName: Prefix of the generated TAB files. -html.tab and -pango.tab will be added
Return Value:
 An array of associative arrays containing entries in the dictionary. 
 Each entry contains: array('key' => '...', 'def' => '...') 
********************************************************************************************/ 
function extractEntries($sDic, $sOutputFileName) {

	//open HTML and Pango TAB files for writing:
	if (!($fTabPango = fopen($sOutputFileName . '-pango.tab', 'w')))
		exit("Error: Unable to open file '$sOutputFileName-pango.tab' for writing.\n");
	
	if (!($fTabHtml = fopen($sOutputFileName . '-html.tab', 'w')))
		exit("Error: Unable to open file '$sOutputFileName-html.tab' for writing.\n");
	
	$aParagraphs = preg_split('@(</(P|H2)>)?\s*<(P|H2)[^>]*>@im', $sDic, NULL, PREG_SPLIT_NO_EMPTY); 
	$aEntries = array(); //empty array to hold the entries in the dictionary

	
	//loop through the paragraphs, pulling out the keys and definitions and inserting
	//them in $aEntries
	for ($iPara = 0; $iPara < count($aParagraphs); $iPara++) {
		$sPara = trim($aParagraphs[$iPara]);
		$sPara = preg_replace("/ *\n */", ' ', $sPara);      //remove all hard returns
		$sPara = preg_replace('/ *\t+ */', ' ', $sPara);     //replace all tabs with spaces
		//remove all tags which aren't bold, italics or font tags:
		$sPara = strip_tags($sPara, '<i><b><em><strong><font>'); 
	
		//convert all tags to lowercase b/c Pango doesn't recognize uppercase tags 
		$sPara = preg_replace('@<(/?)(B|STRONG)>@i', '<\1b>', $sPara);
		$sPara = preg_replace('@<(/?)(I|EM)>@i', '<\1i>', $sPara);
		
		//eliminate all <font> tags except <font color="#008000"> (green) and 
		//<font color="#000080"> (blue) and convert to Pango (span fgcolor="#008000">)
		$sPara = preg_replace('@<FONT COLOR="#008000">(.*)</FONT>@iU', 
			'<span fgcolor="green">\1</span>', $sPara);
		$sPara = preg_replace('@<FONT COLOR="#000080">(.*)</FONT>@iU', 
			'<span fgcolor="blue">\1</span>', $sPara);
		$sPara = preg_replace('@</?FONT[^>]*>@i', '', $sPara);
		$sPara = str_replace('<span fgcolor="green"><i> </i></span>', ' ', $sPara);       
		$sPlainText = trim(strip_tags($sPara));
	
		//ignore lines which are empty or letter headings 	
		if (empty($sPlainText) or preg_match('/^[A-ZÑ]{1,3}$/', $sPlainText)) {
			print("Removing entry: $sPara\n"); 
			continue;
		}
		
		//find the definition which starts with grammar in green cursive or a number in bold
		if (preg_match('@(<span fgcolor="green"><i>|<i><span fgcolor="green">|<b> *1)@', 
			$sPara, $aEntry, PREG_OFFSET_CAPTURE))
		{
			$sKeyPango = trim(mb_substr($sPara, 0, $aEntry[1][1]));
			$sKey = strip_tags($sKeyPango);
						
			$sDefPango = trim(mb_substr($sPara, $aEntry[1][1]));			
			//eliminate any spaces between the first tag and the start of the definition>
			$sDefPango = preg_replace('@^((<i>|<b>|<span fgcolor="green">)+) @', '\1', $sDefPango);
			$sDef = strip_tags($sDefPango);			
		}
		//if no grammar or definition number, then look for end of bold text and a space which ends key:
		elseif (preg_match('@(</b> | </b>)@', $sPara, $aEntry, PREG_OFFSET_CAPTURE)) {
			$sKeyPango = trim(mb_substr($sPara, 0, $aEntry[1][1] + mb_strlen($aEntry[1][0])));
			$sKey = trim(strip_tags($sKeyPango));
						
			$sDefPango = trim(mb_substr($sPara, $aEntry[1][1] + mb_strlen($aEntry[1][0])));
			$sDef = trim(strip_tags($sDefPango));
		}
		//skip entries where can't find definition.
		else {
			print "Unable to find start of definition in entry:\n$sPara\n\n";
			continue;
		}
	
		//convert Pango to HTML:	 	
		$sDefHtml = str_replace('<span fgcolor=', '<font color=', $sDefPango);
		$sDefHtml = str_replace('</span>', '</font>', $sDefHtml);
		//Darken the green in Pango:
		$sDefPango = str_replace('<span fgcolor="green">', '<span fgcolor="#008000">', $sDefPango);
		
		$aEntries[] = array('key' => $sKey, 'def' => $sDefPango);
		
		fwrite($fTabPango, "$sKey\t$sDefPango\n");
		fwrite($fTabHtml,  "$sKey\t$sDefHtml\n");
	}
	
	fclose($fTabPango);
	fclose($fTabHtml);
	
	return $aEntries;
}

//function to create the binary StarDict files (dict.dz, idx, ifo) from a plain text TAB file and 
//insert information in the generated IFO file. Then moves the generated files into a directory named $sDir 
function createDic($sBookname, $sDir, $sTabFile) {
	$sAuthor = "Educación Mapuche Wixaleyiñ";
	$sDesc = "Equipo de Educación Mapuche Wixaleyiñ, Vocabulario Mapuche-Castellano, http://sites.google.com/site/wixaleyin (2013), 94pp.";

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
		"date=2013\n" .
		"website=http://www.illa-a.org/wp/diccionarios\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=g\n"; 
		
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
	if ($nEndPos === false) {
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
