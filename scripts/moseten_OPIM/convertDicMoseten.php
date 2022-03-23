<?php
/****************************************************************************
Program: convertDicMoseten.php
Author:  Amos B. Batto, email: amosbatto AT yahoo DOT com
Project: ILLA, (www.illa-a.org) / SimiDic (www.simidic.org)
Created: 2014-02-22, (La Paz, Bolivia)
License: Public domain
*****************************************************************************/
$help = <<<HELP
convertDicMoseten.php converts the Kirjka pheyakdye’ tɨ̈msi’ tsɨnsi’khan 
kastellanokhan: Diccionario Mosetén-Castellano (Proyecto EIBAMAZ, 2011) to be 
used in StarDict, GoldenDict and SimiDic. 

To call this program:
   php convertDicMoseten.php DIC-MOSETEN.txt [OUTPUT]

For help:
   php convertDicMoseten.php -h 

Parameters:   
DIC-MOSETEN.txt The Diccionario Mosetén file saved as plain text.
OUTPUT         Prefix for output files. If not specified, then the same as
               the filename of DIC-MOSETEN.txt without its extension. 

The script will output 4 StarDict TAB files:
- OUTPUT-mos_es-html.tab  (Mosetén-Spanish in HTML for GoldenDict)
- OUTPUT-mos_es-pango.tab (Tosetén-Spanish in Pango Markup for StarDict/SimiDic)
- OUTPUT-es_mos-html.tab  (Spanish-Mosetén in HTML for GoldenDict)
- OUTPUT-es_mos-pango.tab (Spanish-Mosetén in Pango Markup for StarDict/SimiDic)

Then, the script calls StarDict’s tabfile utility to generate the binary 
StarDict files, which can be used in both StarDict and GoldenDict. The script 
edits the generated IFO files so StarDict and GoldenDict can display 
information about the dictionaries.

After executing this script, run SimiDic-Builder and use The TAB files in 
Pango Markup to create the SimiDic Sqlite3 databases.

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

$sStartMos = "Ä' s.";	
$sEndMos   = "pron. ¿Con quién?";
	
//Get the text in the Moseten-Castellano section:
if (false === ($nStartMos = mb_strpos($sIn, $sStartMos)))
	exit("Error: Unable to find the start of Moseten-Castellano section.\n");
	
if (false === ($nEndMos = mb_strpos($sIn, $sEndMos)))
	exit("Error: Unable to find the end of the Moseten-Castellano section.\n");	

$nEndMos += mb_strlen($sEndMos);
$sInMos = extractStr($sIn, $nStartMos, $nEndMos);
extractEntriesMos($sInMos, "$sFOutput-mos_es");

//create binary dictionaries for StarDict and GoldenDict:
createDic(
	$sBookname = "Mosetén–Castellano (OPIM)",
	$sDir      = 'mos_es-opim-html',
	$sTabFile  = $sFOutput . '-mos_es-html.tab'
);

createDic(
	$sBookname = "Mosetén–Castellano (OPIM)",
	$sDir      = 'mos_es-opim-pango',
	$sTabFile  = $sFOutput . '-mos_es-pango.tab'
);

$sStartEs  = "A ese lado (f)";
$sEndEs    = "¿Ya terminaron? ¿Ajtyɨ' ijaimi'in?";

//Get the text in the Castellano-Moseten section:
if (false === ($nStartEs = mb_strpos($sIn, $sStartEs)))
	exit("Error: Unable to find the start of Castellano-Moseten section.\n");
	
if (false === ($nEndEs = mb_strpos($sIn, $sEndEs)))
	exit("Error: Unable to find the end of the Castellano-Moseten section.\n");	

$nEndEs += mb_strlen($sEndEs);
$sInEs = extractStr($sIn, $nStartEs, $nEndEs);
extractEntriesEs($sInEs, "$sFOutput-es_mos");

createDic(
	$sBookname = "Castellano–Mosetén (OPIM)",
	$sDir      = 'es_mos-opim-html',
	$sTabFile  = $sFOutput . '-es_mos-html.tab'
);

createDic(
	$sBookname = "Castellano–Mosetén (OPIM)",
	$sDir      = 'es_mos-opim-pango',
	$sTabFile  = $sFOutput . '-es_mos-pango.tab'
);

return;

/*******************************************************************************************
function to extract the Mosetén entries from a dictionary and create HTML and Pango TAB files. 
It also checks whether sinonyms "/ ..." and variants "[...]" are keys in other entries.
Parameters:
 $sDic:            String containing the contents of the Mosetén section.
 $sOutputFileName: Prefix of the generated TAB files. "-html.tab" and "-pango.tab" extensions 
                   will be added.
********************************************************************************************/ 
function extractEntriesMos($sDic, $sOutputFileName) {
	//open TAB file for writing:
	if (!($fTabHtml = fopen($sOutputFileName . '-html.tab', 'w')))
		exit("Error: Unable to open file '$sOutputFileName-html.tab' for writing.\n");
		
	if (!($fTabPango = fopen($sOutputFileName . '-pango.tab', 'w')))
		exit("Error: Unable to open file '$sOutputFileName-pango.tab' for writing.\n");
	
	$aParas = explode("\n", $sDic); 
	$aKeys = array();   //empty array to hold the keys from the dictionary
	$aSinons = array(); //empty array to hold sinonyms and variants
		
	//loop through the paragraphs, pulling out the keys and definitions and inserting them in $aEntries
	foreach ($aParas as $sPara) {
		$sPara = trim($sPara);
		$sPara = preg_replace('/\s{2,}/', ' ', $sPara);  //replace all multiple whitespace with a single space
	
		//ignore lines which are empty or letter headings 	
		if (empty($sPara) or mb_strlen($sPara) <= 3 or $sPara == "Expresiones") {
			print("Removing entry: $sPara\n"); 
			continue;
		}
		
		//find the start of the defintion:
		if (preg_match('@(/|\[|\(|adj\.|adv\.|art\.|conj\.|imper\.|incl\.|indef\.|interj\.|interrog\.|' .
			'person\.|prep\.|pron\.|s\.|superl\.|v\.| [¿¡]?[A-ZÁÉÍÓÚÑ])@', $sPara, $aMatch, PREG_OFFSET_CAPTURE)) {
			$nDefPos = $aMatch[0][1];
			$sKey = trim(substr($sPara, 0, $nDefPos));
			$sDef = trim(substr($sPara, $nDefPos));
		}
		elseif ($nDefPos = strpos($sPara, ' ¿')){
			$sKey = trim(substr($sPara, 0, $nDefPos));
			$sDef = trim(substr($sPara, $nDefPos));
		}
		//skip entries where can't find definition.
		else {
			print "Unable to find start of definition in entry:\n$sPara\n\n";
			continue;
		}
		
		$sKey = mb_strtolower($sKey, 'UTF-8');
		
		//colorize sinonyms "/ ..." 
		$sDef = preg_replace("@/ ([a-zñäëïöü'ɨ̈ɨ ¿?¡!]+) " . 
			"(adj|adv|art|conj|imper|incl|indef|interj|interrog|person|prep|pron|s|superl|v)\.@U", 
			'<font color="green">Sinón:</font> <b>\1</b>. \2.', $sDef);
		$sDef = preg_replace("@/ ([a-zñäëïöü'ɨ̈ɨ ¿?¡!]+) (\(Cov\)|\(SA\))@U", 
			'<font color="green">Sinón:</font> <b>\1</b> \2.', $sDef);
		$sDef = preg_replace("@/ ([a-zñäëïöü'ɨ̈ɨ ¿?¡!]+) \(f\)@U", 
			'<font color="green">Sinón:</font> <b>\1</b>. (f)', $sDef);
		$sDef = preg_replace("@\[([a-zñäëïöü'ɨ̈ɨ ]+) (\([CovSA]{2,3}\))?\]@U", '[<b>\1</b> \2]', $sDef);
		$sDef = preg_replace('@\((f|m|Cov|SA|SA / Cov|Cov – SA)\)@', '<font color="blue">(\1)</font>', $sDef);
		$sDef = preg_replace('/\b(adj|adv|art|conj|imper|incl|indef|interj|interrog|person|prep|pron|s|superl|v)\./',
			'<i><font color="green">\1.</font></i>', $sDef);
		$sDef = str_replace(' .', '.', $sDef);		
		
		//remove beginning "¿" and "!" from key for StarDict and SimiDic
		$sKeyPango = str_replace('¿', '', $sKey);
		$sKeyPango = str_replace('¡', '', $sKeyPango);
		
		//convert definition from HTML to Pango Markup:
		$sDefPango = str_replace('<font color=', '<span fgcolor=', $sDef);
		$sDefPango = str_replace('</font>', '</span>', $sDefPango);
		$sDefPango = str_replace('"green"', '"#008000"', $sDefPango);
		
		$aKeys[] = $sKey;
		
		if (preg_match("@<b>([a-zäëïöü'ɨ̈ɨ ]+)</b>@", $sDef, $aMatchSinon)) {
			$aSinons[] = trim($aMatchSinon[1]);
		}
		
		fwrite($fTabHtml, "$sKey\t$sDef\n");
		fwrite($fTabPango, "$sKeyPango\t$sDefPango\n");
	}

	fclose($fTabHtml);
	fclose($fTabPango);
	
	foreach ($aSinons as $sSinon) {
		if (!in_array($sSinon, $aKeys)) 
			print "Sinonyn \"$sSinon\" not found as key.\n\n";
	}	
}

/*******************************************************************************************
function to extract the Spanish entries from a dictionary and create HTML and Pango TAB files. 
Parameters:
 $sDic:            String containing the contents of the Spanish section.
 $sOutputFileName: Prefix of the generated TAB files. "-html.tab" and "-pango.tab" extensions 
                   will be added.
********************************************************************************************/ 
function extractEntriesEs($sDic, $sOutputFileName) {
	//open TAB file for writing:
	if (!($fTabHtml = fopen($sOutputFileName . '-html.tab', 'w')))
		exit("Error: Unable to open file '$sOutputFileName-html.tab' for writing.\n");
		
	if (!($fTabPango = fopen($sOutputFileName . '-pango.tab', 'w')))
		exit("Error: Unable to open file '$sOutputFileName-pango.tab' for writing.\n");
	
	$aParas = explode("\n", $sDic); 
		
	//loop through the paragraphs, pulling out the keys and definitions and inserting them in $aEntries
	foreach ($aParas as $sPara) {
		$sPara = trim($sPara);
		//$sPara = preg_replace('/\s{2,}/', ' ', $sPara);  //replace all multiple whitespace with a single space
	
		//ignore lines which are empty or letter headings 	
		if (empty($sPara) or mb_strlen($sPara) <= 3 or $sPara == "Expresiones") {
			print("Removing entry: $sPara\n"); 
			continue;
		}
		
		//find the start of the defintion:
		if (preg_match('@(\(f\)|\(m\)|\(SA\)|\(Cov\)|adj\.|adv\.|art\.|conj\.|imper\.|incl\.|indef\.|' . 
				'interj\.|interrog\.|person\.|prep\.|pron\.|s\.|superl\.|v\.| [A-Z])@', 
				$sPara, $aMatch, PREG_OFFSET_CAPTURE)) 
		{
			$nDefPos = $aMatch[0][1];
			$sKey = trim(substr($sPara, 0, $nDefPos));
			$sDef = trim(substr($sPara, $nDefPos));
		}
		else {
			$aCapLetters = array(' ¿', ' ¡', ' Ñ', ' Ä', ' Ë', ' Ï', ' Ö', ' Ü', ' Ɨ', ' Ɨ̈');
			$nDefPos = false;
			
			foreach ($aCapLetters as $sCapLetter) {
				if ($nDefPos = mb_strpos($sPara, $sCapLetter, 0, 'UTF-8'))
					break;
			}
			
			if ($nDefPos !== false) {
				$sKey = trim(mb_substr($sPara, 0, $nDefPos, 'UTF-8'));
				$sDef = trim(mb_substr($sPara, $nDefPos, 1000, 'UTF-8'));
			}
			//skip entries where can't find definition.
			else {
				print "Unable to find start of definition in entry:\n$sPara\n\n";
				continue;
			}
		}
		
		$sKey = mb_strtolower($sKey, 'UTF-8');
		
		//colorize abbreviations: 
		$sDef = preg_replace('@\((f|m|Cov|SA|SA / Cov|Cov – SA)\)@', '<font color="blue">(\1)</font>', $sDef);
		$sDef = preg_replace('/\b(adj|adv|art|conj|imper|incl|indef|interj|interrog|person|prep|pron|s|superl|v)\./',
			'<i><font color="green">\1.</font></i>', $sDef);	
		
		//remove beginning "¿" and "!" from key for StarDict and SimiDic
		$sKeyPango = str_replace('¿', '', $sKey);
		$sKeyPango = str_replace('¡', '', $sKeyPango);
		
		//convert definition from HTML to Pango Markup:
		$sDefPango = str_replace('<font color=', '<span fgcolor=', $sDef);
		$sDefPango = str_replace('</font>', '</span>', $sDefPango);
		$sDefPango = str_replace('"green"', '"#008000"', $sDefPango);
		
		fwrite($fTabHtml, "$sKey\t$sDef\n");
		fwrite($fTabPango, "$sKeyPango\t$sDefPango\n");
	}

	fclose($fTabHtml);
	fclose($fTabPango);	
}

//function to create the binary StarDict files (dict.dz, idx, ifo) from a TAB file and 
//insert information in the generated IFO file. Then moves the generated files into a directory named $sDir 
function createDic($sBookname, $sDir, $sTabFile) {
	$sAuthor = "Organización de los Pueblos Indígenas Mosetén y UMSS–PROEIB Andes";
	$sDesc = "Kirjka pheyakdye' tïmsi' tsinsi'khan kastellanokhan: Diccionario Mosetén-Castellano, " .
		"Castellano-Mosetén, Cochabamba (2011), 131pp.";

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
		"date=2011\n" .
		"website=http://www.illa-a.org/wp/diccionarios\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=g\n"; 
		
	file_put_contents($sIfoFile, $sIfo);

	//Create directories for the GoldenDict and StarDict files if they don't exist
	if (file_exists($sDir)) {
		system("rm -f $sDir/{$aParts['filename']}*");
	}
	else {
		mkdir($sDir, 0755);
	}
	
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
