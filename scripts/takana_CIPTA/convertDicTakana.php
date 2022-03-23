<?php
/************************************************************************
Program: convertDicTakana.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com
Project: ILLA, web: www.illa-a.org
Created: 2013-12-21, (La Paz, Bolivia)
License: Public domain 
*****************************************************************************/

$help = <<<HELP
convertDicTakana.php converts the Mimi butsepi Takana-Kastillanu, Kastillanu-
Takana Diccionario Takana-Castellano, Castellano-Takana (2011) to be used in 
StarDict, GoldenDict and SimiDic.

The script creates a TAB file in Pango Markup for StarDict and another TAB file
in HTML for GoldenDict and SimiDic-Builder. Then, it calls StarDict’s tabfile 
utility to create the electronic dictionaries. It then modifies the generated 
IFO files to insert information about the Takana Dictionary.

After running this script, use SimiDic-Builder to create the SimiDic 
dictionaries.

To call this program:
   php convertDicTakana.php DICTIONARY.txt [OUTPUT]
   
For help:	
	php convertDicTakana.php -h

DICTIONARY.txt is the filename of the Diccionario Takana in plain text.

OUTPUT is the optional filename for the generated files. If not included, 
then the generated files will have the same filename as DICTIONARY.txt.

The script convert all keywords to lowercase and all long dashes to normal 
dashes. It will create separate entries for entries indented with tabs, but 
also place those entries within the preceding parent entry. For example:
  –abu– v.act.tr. Cargar.
      Abu tupu s. Una carga.
Will create the following 2 entries:
   –abu–      v.act.tr. Cargar.\nAbu tupu s. Una carga.
   Abu tupu   s. Una carga.

Requirements:
Assuming that using a Linux machine with UTF-8 default character set and PHP5 
installed. StarDict’s tools need to be installed, which includes the tabfile 
program.

HELP;

//PHP configuration:
ini_set('max_execution_time', 0);  //set no max time for this program to run.
ini_set('memory_limit', '-1');  //set no memory limit
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
setlocale(LC_ALL, 'es_BO.utf8');

//process input parameters:	
if ($argc <= 1 or $argv[1] == '-h' or stristr($argv[1], 'help'))
	exit($help);

if ($argc < 2)
	exit("Error: Too few parameters.\n\n" . $help);
elseif ($argc > 3)
	exit("Error: Too many parameters.\n\n" . $help);
	
$sFDic = $argv[1];

if ($argc == 3)
	$sFOutput = $argv[2];
else 
	$sFOutput = $sFDic;

//strip out the path and the extension:	
$fparts = pathinfo($sFOutput);
$sFOutput = $fparts['filename'];

//if can't open file or empty file, then exit.
if (!($sIn = file_get_contents($sFDic)))
	exit("Error: Can't open file '$sFDic' or file empty.\n");

//Get the Takana-Spanish Dictionary:
if (!($nPos = mb_strpos($sIn, '¿A…? pron.interrog. Introduce pregunta.')))
	exit("Error: Unable to find start of Takana-Spanish dictionary.");

$sIn = mb_substr($sIn, $nPos);
$sEndDic = 'Yuwa s. Isigo (árbol).';

if (!($nPos = mb_strpos($sIn, $sEndDic)))
	exit("Error: Unable to find end of Takana-Spanish dictionary.");

$sDicTakana = mb_substr($sIn, 0, $nPos + mb_strlen($sEndDic));

//Get the Spanish-Takana Dictionary:
$sIn =  mb_substr($sIn, $nPos + mb_strlen($sEndDic));

if (!($nPos = mb_strpos($sIn, "A prep. Awa, s'u.")))
	exit("Error: Unable to find start of Spanish-Takana dictionary.");

$sDicSpanish = mb_substr($sIn, $nPos);

//Get an array of entries for the Takana and Spanish dictionaries:
$aEntriesTak = processDic($sDicTakana);
$aEntriesEs = processDic($sDicSpanish);
	
print "Takana entries: " . count($aEntriesTak) . ", Spanish entries: " . count($aEntriesEs) . "\n";

createDic(
	$aEntries = $aEntriesTak, 
	$lang     = "tak", 
	$fname    = $sFOutput, 
	$bookName = "Takana–Castellano (CIPTA)",
	$author   = "CIPTA y UMSS-PROEIB Andes",
	$desc     = "A. Marupa Beyuma, et al., Mimi butsepi Takana-Kastillanu, Kastillanu-Takana: Diccionario Takana-Castellano, Castellano-Takana, CIPTA y UMSS-PROEIB Andes, 2011, 206pp.",
	$sDir     = 'tak_es-cipta');

createDic(
	$aEntries = $aEntriesEs, 
	$lang     = "es", 
	$fname    = $sFOutput, 
	$bookName = "Castellano–Takana (CIPTA)",
	$author   = "CIPTA y UMSS-PROEIB Andes",
	$desc     = "A. Marupa Beyuma, et al., Mimi butsepi Takana-Kastillanu, Kastillanu-Takana: Diccionario Takana-Castellano, Castellano-Takana, CIPTA y UMSS-PROEIB Andes, 2011, 206pp.",
	$sDir     = 'es_tak-cipta');

return;


//Function to break dictionary into an array, which is returned
function processDic($sDic) { 

	//abbreviations to place in green cursive:
	$aAbbrevs = array (
		'act',      // activo
		'actr',     // actor
		'adv',      // adverbio
		'af',       // afijo
		'adj',      // adjetivo
		'adjl',     // adjetival
		'asrv',     // asertativo
		'arv',      // ???
		'av',       // ??? verbo
		'aym',      // aymara
		'conj',     // conjunción
		'cond',     // condicional
		'cv',       // cuerpo del verbo
		'dem',      // demostrativo
		'dub',      // dubitativo
		'e',        // estado
		'enfq',     // enfoque
		'esp',      // español
		'excl',     // exclusivo
		'fund',     // fundamento
		'fam',      // familia
		'fmbrev',   // forma breve
		'fmlarg',   // forma larga
		'form',     // formativo
		'gen',      // género
		'habil',    // habilitativo
		'hesit',    // hesitación
		'intr',     // intransitivo
		'incl',     // inclusivo
		'indef',    // indefinido
		'imper',    // imperativo
		'impers',   // impersonal
		'int',      // intentivo
		'interrog', // interrogativo
		'loc',      // locativo???
		'mod',      // modismo
		'neg',      // negativo
		'obj',      // objetivo
		'onomat',   // onomatopéico
		'pas',      // pasado
		'pl',       // plural
		'pers',     // personal
		'pospos',   // posposición
		'pos',      // posesivo
		'pref',     // prefijo
		'prep',     // preposición
		'pron',     // pronombre
		'prog',     // progresivo
		'quech',    // quechua
		'rel',      // relativo
		'report',   // reportativo
		's',        // sustantivo
		'sarc',     // sarcasmo
		'sev',      // ??? verbo
		'sing',     // singular
		'subj',     // subjuntivo
		'sub',      // subjetivo
		'subord',   // subordinador
		'suf',      // sufijo
		'tr',       // transitivo
		'vl',       // verbal
		'v',        // verbo
		'Nota',
		'Ej',       // ejemplo
		'Ver',
	);

	/*an array to hold the entries in the dictionary
	each element in the $aEntries array is an associative array with the following format:
	  array (
	   tabs:   Number of tabs, indicating indentation level. 0 is a top level entry, 
	           1 is a subentry, 2 is a subentry of a subentry, etc.
	   key:    key word(s) in plain text.
	   def:    definition in HTML.
	  ) */
	$aEntries = array();
	
	$aLines = explode("\n", $sDic); 
	
	for ($iLines = 0; $iLines < count($aLines); $iLines++) {
		$sLine = rtrim($aLines[$iLines]);
		
		//if a letter heading, throw it out
		if (preg_match("/^[A-Z']{1,2}$/", $sLine))
			continue;
			
		//Don't process lines that start with number or "Ej:" or are empty, because they aren't separate entries
		if (preg_match('/^\t*([1-9]\.|Ej:) .*/', $sLine) or trim($sLine) == '')
			continue;
		 
		
		$sEntry = $sLine;
		
		if (preg_match('/^\t+/', $sLine, $aMatch))
			$nTabs = mb_strlen($aMatch[0]);
		else 
			$nTabs = 0;
				
		//search for the end of the the current entry, which includes all subsequent lines which 
		//have more tabs than the current line
		for ($iSubsequent = $iLines + 1; $iSubsequent < count($aLines); $iSubsequent++) {
			
			if (preg_match('/^\t+/', $aLines[$iSubsequent], $aMatchSubsequent)) {
				
				if (mb_strlen($aMatchSubsequent[0]) > $nTabs) {
					$sSubsequent = trim($aLines[$iSubsequent]);

					//if contains key word(s), then place them in bold.
					if (preg_match('/^(Ej:|[1-9]\.)/', $sSubsequent) == 0) {
						$nStartDef = findDef($sSubsequent);
						
						//if the start of the definition was found
						if ($nStartDef != 1000) {  
							$sSubsequent = '<b>' . mb_substr($sSubsequent, 0, $nStartDef) . '</b>' .
								mb_substr($sSubsequent, $nStartDef);
						}
					}
					$sEntry .= "\n" . $sSubsequent;
				}				
				else
					break;
			}
			else {
				break;
			}
		}
		
		//replace long dashes with normal dashes which can be typed on a normal keyboard 
		$sEntry = str_replace('–', '-', $sEntry);
			
		$nDefPos = findDef($sEntry);
	   	
		$sKey = rtrim(trim(mb_substr($sEntry, 0, $nDefPos)), '.');
		
		$sDef = trim(mb_substr($sEntry, $nDefPos));
		
		//In definitions, remove any indentation at beginning of lines
		$sDef = preg_replace("/\n\t+/", "\n", $sDef);
		//remove any new lines at the start of definition
		$sDef = preg_replace("/^\n/", '', $sDef); 
		
		//place definition numbers in bold
		$sDef = preg_replace('/\b([1-9])\. /', '<b>\1.</b> ', $sDef);
		
		//place references to Takana text in blue:
		$sDef = preg_replace('/\[Ver: (.*)\]/U', '[Ver: <span fgcolor="blue">\1</span>]', $sDef); 	
		
		//place Takana text in examples in blue:	
		if (preg_match_all("/\nEj: (.*)/", $sDef, $aEjs)) {
			
			foreach ($aEjs[1] as $sEj) {
				
				if (preg_match_all('/[\?\.!]/', $sEj, $aTerminators, PREG_OFFSET_CAPTURE)) {
							
						//if not an even number of sentence terminators, then Takana text ends with the first terminator
						if (count($aTerminators[0]) % 2)
							$nTerminator = 0;
						else 
							$nTerminator = count($aTerminators[0]) / 2 - 1;
							
						$sEjColored = '<span fgcolor="blue">' . mb_substr($sEj, 0, $aTerminators[0][$nTerminator][1] + 1) . 
							"</span>" . mb_substr($sEj, $aTerminators[0][$nTerminator][1] + 1);
				}
				else {
					print "Warning: No sentence terminators in the following line:\n$sEj\n";
					$sEjColored = $sEj;
				}
				
				$sDef = str_replace($sEj, $sEjColored, $sDef);
			}
		}
		
		//place abbreviations in green cursive
		foreach ($aAbbrevs as $sAbbrev) {
			$sDef = preg_replace("/\b$sAbbrev([\.:])/", '<span fgcolor="#228B22"><i>' . $sAbbrev . '\1</i></span>', $sDef);
			$sDef = str_replace('</i></span><span fgcolor="#228B22"><i>', '', $sDef);
			//$sDef = preg_replace('@</i></span>([\.:])@', '\1</i></span>', $sDef);
		}
		
		$sDef = str_replace("\n", "\\n", $sDef);
		
		$aEntries[] = array(
			'tabs' => $nTabs,
			'key' => strtolower($sKey),
			'def' => $sDef
		);	
	}
	return $aEntries;
}

//Function to return the position of the first letter of a definition in a dictionary entry. 
//If fails to find a definition, returns 1000.
function findDef($sEntry) {
		$nCapitalPos = $nBracketPos = $nDefPos = 1000;
		if (preg_match('/\b(1|act|actr|adv|af|adj|adjl|asrv|arv|av|conj|cv|dem|dub|e|enfq|fund|fam|fmbrev|fmlarg|form|' .
			'gen|habil|hesit|intr|indef|imper|impers|int|interrog|loc|mod|neg|obj|onomat|pas|pl|pers|pos|pospos|' . 
			'pref|prep|pron|prog|rel|report|s|sarc|sev|sing|sub|subord|subj|suf|tr|vl|v)\./m', $sEntry, $aDefMatch, 
			PREG_OFFSET_CAPTURE))
		{
			$nDefPos = $aDefMatch[1][1];
		}
		
		$nBracketPos = mb_strpos($sEntry, '[') or $nBracketPos = 1000;
			
		if (preg_match('/ [¿¡]*[A-ZÑÁÉÍÓÚ]/', $sEntry, $aCapitalMatch, PREG_OFFSET_CAPTURE))
			$nCapitalPos = $aCapitalMatch[0][1];
			
		$nDefPos = min($nCapitalPos, $nDefPos, $nBracketPos);
		
		//if can't find definition, then must have a definition which starts with dash "-"
	   if ($nDefPos == 1000) {
	   	$nDefPos = mb_strpos($sEntry, ' -');
	   	
	   	if ($nDefPos === false)
	   		$nDefPos = 1000;
	   }
		return $nDefPos;
}

//function to convert a string from Pango Markup to HTML
function pango2html($s) {
	$sHtml = preg_replace('/<span fgcolor=([^>]+)>/i', '<font color=\1>', $s);
	$sHtml = str_replace('</span>', '</font>', $sHtml);
	$sHtml = str_replace('\n', '<br>', $sHtml);
	//white screen of StarDict needs darker colors than off-white screen of GoldenDict and SimiDic
	//convert from dark green to normal green
	$sHtml = str_replace('="#228B22">', '="green">', $sHtml); 
	//need to check blue
	return $sHtml;
}	

/* function createDic() to create StarDict and GoldenDict dictionaries
Takes an array of dictionary entries and generate the HTML and Pango TAB files. 
Then it calls StarDict's tabfile to generate the dictionary files. 
Then moves the files to a separate directory. 
Finally searchs through the generated IFO files and inserts information about the dictionary. */
function createDic($aEntries, $lang, $fname, $bookName, $author, $desc, $sDir) {
	$fPango = fopen("$fname-$lang-pango.tab", 'w') or die("Error: Unable to open '$sFOutput-$lang-pango.tab' for writing.");
	$fHtml  = fopen("$fname-$lang-html.tab",  'w') or die("Error: Unable to open '$sFOutput-$lang-html.tab' for writing.");

	foreach ($aEntries as $aEntry) {
		$sEntry = $aEntry['key'] . "\t" . $aEntry['def'] . "\n";
		fwrite($fPango, $sEntry);
		fwrite($fHtml, pango2html($sEntry));
	}

	//run StarDict's tabfile to create the dictionaries for StarDict and GoldenDict
	system("tabfile $fname-$lang-html.tab");
	system("tabfile $fname-$lang-pango.tab");
	
	//Search for information in the generated IFO files:
	$sIfoHtml  = file_get_contents("$fname-$lang-html.ifo");
	$sIfoPango = file_get_contents("$fname-$lang-pango.ifo");
	
	if (!preg_match('/wordcount=([0-9]+)/', $sIfoHtml, $aMatch))
		exit("Error finding 'wordcount' in file '$fname-$lang-html.ifo'"); 
	else {
		$nWordCntHtml = $aMatch[1];
	}
		
	if (!preg_match('/wordcount=([0-9]+)/', $sIfoPango, $aMatch))
		exit("Error finding 'wordcount' in file '$fname-$lang-pango.ifo'"); 
	else {
		$nWordCntPango = $aMatch[1];
	}
			
	if (!preg_match('/idxfilesize=([0-9]+)/', $sIfoHtml, $aMatch))
		exit("Error finding 'idxfilesize' in file '$fname-$lang-html.ifo'"); 
	else {
		$nIdxSizeHtml = $aMatch[1];
	}	
	
	if (!preg_match('/idxfilesize=([0-9]+)/', $sIfoPango, $aMatch))
		exit("Error finding 'idxfilesize' in file '$fname-$lang-pango.ifo'"); 
	else {
		$nIdxSizePango = $aMatch[1];
	}	
				
	$sIfoHtml = "StarDict's dict ifo file\n" .
			"version=2.4.2\n" .
			"wordcount=$nWordCntHtml\n" .
			"idxfilesize=$nIdxSizeHtml\n" .
			"bookname=$bookName\n" .
			"description=$desc\n" .
			"author=$author\n" .
			"date=2011\n" .
			"website=http://www.illa-a.org/wp/diccionarios\n" .
			"email=amosbatto@yahoo.com\n" .
			"sametypesequence=g\n"; 
	
	$sIfoPango = "StarDict's dict ifo file\n" .
			"version=2.4.2\n" .
			"wordcount=$nWordCntPango\n" .
			"idxfilesize=$nIdxSizePango\n" .
			"bookname=$bookName\n" .
			"description=$desc\n" .
			"author=$author\n" .
			"date=2011\n" .
			"website=http://www.illa-a.org/wp/diccionarios\n" .
			"email=amosbatto@yahoo.com\n" .
			"sametypesequence=g\n"; 
			
	file_put_contents("$fname-$lang-html.ifo", $sIfoHtml);
	file_put_contents("$fname-$lang-pango.ifo", $sIfoPango);
		
	//Create directories for the GoldenDict and StarDict files if they don't exist
	if (file_exists($sDir . '-html'))
		system("rm $sDir-html/*");
	else
		mkdir($sDir . '-html', 0755);
	
	if (file_exists($sDir . '-pango'))
		system("rm $sDir-pango/*");
	else 
		mkdir($sDir . '-pango', 0755);	
		
	system("mv $fname-$lang-html.* $sDir-html");
	system("mv $fname-$lang-pango.* $sDir-pango");
	return;
}		
?>
