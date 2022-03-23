<?php
/************************************************************************
Program: convertDicMocho.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com
Project: ILLA, web: www.illa-a.org
Created: 2016-02-20, (La Paz, Bolivia)
License: Public domain 
*****************************************************************************/

$help = <<<HELP
convertDicMocho.php converts the Manual Mochó Alonso Guzmán (2004) to be used in 
StarDict, GoldenDict and SimiDic.

The script creates a TAB file in Pango Markup for StarDict and another TAB file
in HTML for GoldenDict and SimiDic-Builder. Then, it calls StarDict’s tabfile 
utility to create the electronic dictionaries. It then modifies the generated 
IFO files to insert information about the Takana Dictionary.

After running this script, use SimiDic-Builder to create the SimiDic 
dictionaries.

To call this program:
   php convertDicMocho.php DICTIONARY.txt [OUTPUT]
   
For help:	
	php convertDicMocho.php -h

DICTIONARY.txt is the filename of the Manual Mochó in plain text.

OUTPUT is the optional filename for the generated files. If not included, 
then the generated files will have the same filename as DICTIONARY.txt.

The script converts all text to lowercase.
 
Requirements:
Assuming that using a Linux machine with UTF-8 default character set and PHP5 
installed, with the "intl" extension installed. 
StarDict’s tools need to be installed, which includes the tabfile program.

HELP;

//PHP configuration:
ini_set('max_execution_time', 0);  //set no max time for this program to run.
ini_set('memory_limit', '-1');  //set no memory limit
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
setlocale(LC_ALL, 'es_ES.UTF-8');
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

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
if (!($aIn = file($sFDic)))
	exit("Error: Can't open file '$sFDic' or file empty.\n");



$aOutMaya = $aOutEsp = array();

//Start counting from 1 to ignore the first line:
$lineNo = 1;
$totalLines = count ($aIn);

for (; $lineNo < $totalLines; $lineNo++) {
	$sLine = mb_strtolower($aIn[$lineNo]);
	$sLine = str_replace('’', "'", $sLine);
	//var_dump($sLine);
	$aEntry = mb_split("\t", $sLine);
	$sEsp = trim($aEntry[0]);
	$sMaya = trim($aEntry[1]);
	
	$aEsp = preg_split('/ *[,\/] */', $sEsp);
	if (is_array($aEsp) and count($aEsp) == 2) {
		$aOutEsp[] = $aEsp[0] . ', ' . $aEsp[1] . "\t" . $sMaya;
		$aOutEsp[] = $aEsp[1] . ', ' . $aEsp[0] . "\t" . $sMaya;
	}   
   else {
   	$aOutEsp[] = $sEsp . "\t" . $sMaya;
   }
	
	$aMaya = preg_split('/ *[,\/] */', $sMaya);
	if (is_array($aMaya) and count($aMaya) == 2) {
		$aOutMaya[] = $aMaya[0] . ', ' . $aMaya[1] . "\t" . $sEsp;
		$aOutMaya[] = $aMaya[1] . ', ' . $aMaya[0] . "\t" . $sEsp;
	}   
   elseif (mb_substr($sEsp, -1) == '?') {
   	
   	$aOutMaya[] = ((mb_substr($sMaya, 0, 1) == '¿') ? mb_substr($sMaya, 1) : $sMaya) . "\t¿" . $sEsp;
   }
   else {
   	$aOutMaya[] = $sMaya . "\t" . $sEsp;
   }
}


print "Number of dictionary entries: $totalLines\n";

createDic(
	$aEntries = $aOutMaya, 
	$lang     = "mhc", 
	$fname    = $sFOutput, 
	$bookName = "Mocho'–Castellano (A.Guzmán)",
	$author   = "Alonso Guzmán (CELALI)",
	$desc     = "Alonso Guzmán, Manual Mocho', CELALI, 2004.",
	$sDir     = 'mhc_es-guzman'
);

createDic(
	$aEntries = $aOutEsp, 
	$lang     = "es", 
	$fname    = $sFOutput, 
	$bookName = "Castellano–Mocho' (A.Guzmán)",
	$author   = "Alonso Guzmán",
	$desc     = "Manual Mochó Alonso Guzmán (2004)",
	$sDir     = 'es_mhc-guzman'
);

return;



/* function createDic() to create StarDict and GoldenDict dictionaries
Takes an array of dictionary entries and generates the TAB file. 
Then it calls StarDict's tabfile to generate the dictionary files. 
Then moves the files to a separate directory. 
Finally searchs through the generated IFO files and inserts information about the dictionary. */
function createDic($aEntries, $lang, $fname, $bookName, $author, $desc, $sDir) {


	$col = new \Collator('es_ES.UTF-8');
   $col->asort($aEntries);

	//var_dump ($aEntries);
	
	$fTab = file_put_contents("$fname-$lang.tab", implode("\n", $aEntries) . "\n") or 
		die("Error: Unable to open '$fname-$lang.tab' for writing.");

	//run StarDict's tabfile to create the dictionaries for StarDict and GoldenDict
	system("tabfile $fname-$lang.tab");
	
	//Search for information in the generated IFO files:
	$sIfoFile  = file_get_contents("$fname-$lang.ifo");
	
	if (!preg_match('/wordcount=([0-9]+)/', $sIfoFile, $aMatch))
		exit("Error finding 'wordcount' in file '$fname-$lang.ifo'"); 
	else {
		$nWordCntIfo = $aMatch[1];
	}
		
			
	if (!preg_match('/idxfilesize=([0-9]+)/', $sIfoFile, $aMatch))
		exit("Error finding 'idxfilesize' in file '$fname-$lang.ifo'"); 
	else {
		$nIdxSizeIfo = $aMatch[1];
	}	
		
				
	$sIfo = "StarDict's dict ifo file\n" .
			"version=2.4.2\n" .
			"wordcount=$nWordCntIfo\n" .
			"idxfilesize=$nIdxSizeIfo\n" .
			"bookname=$bookName\n" .
			"description=$desc\n" .
			"author=$author\n" .
			"date=2004\n" .
			"website=http://www.illa-a.org/wp/diccionarios\n" .
			"email=amosbatto@yahoo.com\n" .
			"sametypesequence=g\n"; 
			
	file_put_contents("$fname-$lang.ifo", $sIfo);
		
	//Create directories for the GoldenDict and StarDict files if they don't exist
	if (file_exists($sDir))
		system("rm $sDir/*");
	else
		mkdir($sDir, 0755);	
		
	system("mv $fname-$lang.* $sDir");
	return;
}		
?>
