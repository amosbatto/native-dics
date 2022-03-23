<?php
/************************************************************************
Program: convertDicHerreroSanchez.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com
Project: ILLA, web: www.illa-a.org
Created: 2014-01-27, (La Paz, Bolivia)
License: Public domain 
*****************************************************************************/

$help = <<<HELP
convertDicHerreroSanchez.php converts the "Diccionario Quechua: Estructura 
semántica del quechua cochabambino contemporáneo" by Joaquín Herrero S.J. 
and Federico Sánchez de Lozada into StarDict files with HTML formatting 
to be used in GoldenDict and SimiDic. It also creates files with Pango 
formatting to be used in StarDict. It strips out all formatting in the 
definitions except font color, italics, superscript and line breaks. 

To call this program:
   php convertDicHerreroSanchez.php DICTIONARY.htm
   
For help:	
	php convertDicHerreroSanchez.php -h

DICTIONARY.htm is the filename of the  saved in HTML (UTF-8 encoding) by
LibreOffice.

OUTPUT is the optional filename for the generated files. If not included, 
then the generated files will have the same filename as DICTIONARY.htm, but
with the extensions: "-html.tab" and "-pango.tab".

Requirements:
Assuming that using a Linux machine with UTF-8 default character set and PHP5 
installed.

HELP;

//PHP configuration:
ini_set('max_execution_time', 0);  //set no max time for this program to run.
ini_set('memory_limit', '-1');  //set no memory limit
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
setlocale(LC_ALL, 'es_BO.utf8');
mb_internal_encoding("UTF-8");

//process input parameters:	
if ($argc <= 1 or $argv[1] == '-h' or stristr($argv[1], 'help'))
	exit($help);

if ($argc < 2)
	exit("Error: Too few parameters.\n\n" . $help);
elseif ($argc > 3)
	exit("Error: Too many parameters.\n\n" . $help);
	
$sFDic = $argv[1];

//get filename of HTML output file:
if ($argc == 3) {
	$sFName = $argv[2];
}
else {
	//strip out the path and the extension:	
	$fparts = pathinfo($sFDic);
	$sFName = $fparts['filename'];
}

$fHtmlTab  = fopen($sFName . '-html.tab', 'w');
$fPangoTab = fopen($sFName . '-pango.tab', 'w');

//if can't open file or empty file, then exit.
if (!($sIn = file_get_contents($sFDic)))
	exit("Error: Can't open file '$sFDic' or file empty.\n");
	
//extract dictionary entries from HTML file:
if (!($nDicStart = stripos($sIn, '<P LANG="es-BO" CLASS="western"><B>a. </B>'))) {
	exit("Error: Unable to find start of Quechua dictionary.\n");
}
if (!($nDicEnd = stripos($sIn, '<P LANG="es-BO" CLASS="western" ALIGN=CENTER>La presente edición se'))) {
	exit("Error: Unable to find end of Quechua dictionary.\n");
}

	
$sIn = substr($sIn, $nDicStart, $nDicEnd - $nDicStart);
$sIn = preg_replace('@<H2[^>]*>.*</H2>@miU', '', $sIn, -1, $nCount);
$sIn = strip_tags($sIn, '<p><i><sup><font><br><b>'); 
$sIn = str_replace(' </B>', '</B> ', $sIn);
$sIn = str_replace(' LANG="es-BO" ', ' ', $sIn);


print "Replace H2: $nCount\n\n";

$aEntries = array();    //dictionary entries
$aSubEntries = array(); //dictionary subentries found under "Algunas expresiones usuales..."

$aParasIn = explode('<P', $sIn);
print '# of paragraphs: ' . count($aParasIn) . "\n";

foreach ($aParasIn as $sPara) {
	$sPara = str_replace("\n", ' ', $sPara);
	$sPara = str_replace("\t", ' ', $sPara);
	$sPara = preg_replace('/ {2,}/', ' ', $sPara);	
	//convert all HTML tags to lowercase:
	$sPara = preg_replace(
		array('@(</?)BR>@', '@(</?)B>@', '@(</?)I>@', '@(</?)SUP>@', '@<FONT COLOR@', '@</FONT>@'),
		array('\1br>',      '\1b>',       '\1i>',       '\1sup>',       '<font color',   '</font>'), 
		$sPara
	);
	//remove the paragraph details in <P (...)> tag
	$nParaEnd = strpos($sPara, '>');
	$sParaType = substr($sPara, 0, $nParaEnd);
	$sPara = substr($sPara, $nParaEnd + 1);
	$sParaPlain = trim(strip_tags($sPara));

	//remove blanck paragraphs and letter headers: A, B, CH, CHH, etc. 	
	if (strlen($sParaPlain) < 4) {
		print "Paragraph excluded: $sParaPlain\n";
		continue;
	}	
	
	//Place superscripted numbers inside parentheses. Ex: lla <sup>2</sup>. -> lla(2).
	if (stripos($sPara, '<sup>')) {
		
		if (!preg_match('@ ?<sup>.*([1-5]).*</sup>@U', $sPara)) {
			print "Bad formatted superscript: $sPara\n\n";
		}
		else {
			$sPara = preg_replace('@ ?<sup>.*([1-5]).*</sup>@U', '(\1)', $sPara);
		}
	}	
	
	//if the paragraph starts with "Algunas expresiones usuales...", 
	//then append this paragraph to the previous one
	if (preg_match('/^Algunas /', $sParaPlain)) {
		$sLastEntry = str_replace("\n", '<br>', array_pop($aEntries));
		$aEntries[] = $sLastEntry . trim(strip_tags($sPara, '<i><b>')) . "\n";
		continue;
	}
	
	// get key word(s) for each entry, which are placed inside bold tags
	if (!($nKeyEnd = strrpos($sPara, '</b>'))) { 
		print "Unable to find key's end:\n" . substr($sPara, 0, 200) . "\n\n"; 
		continue;
	} 
	
	$sKey = trim(strip_tags(substr($sPara, 0, $nKeyEnd)));
	$sKey = preg_replace('/\.$/', '', $sKey); //remove any "." from end of key
	$sDef = trim(strip_tags(substr($sPara, $nKeyEnd + 4), '<i><font><br>')); //add 4 for "</B>"
	
	//if the paragraph class is "frasesusuales", then it is a subentry in the dictionary
	if (strpos($sParaType, 'frasesusuales') !== false) {
		$aSubEntries[] = $sKey . "\t" . $sDef . "\n";
		$sLastEntry = str_replace("\n", '<br>', array_pop($aEntries)); 
		$aEntries[] = $sLastEntry . trim(strip_tags($sPara, '<i><font><br><b>')) . "\n";
	}	
	// if a normal dictionary entry:	
	else {
		//add all subentries to the $aEntries array
		$aEntries = array_merge($aEntries, $aSubEntries);
		$aSubEntries = array();
				
		$aEntries[] = $sKey . "\t" . $sDef . "\n";
	}
}
		
foreach ($aEntries as $sEntry) {		
	//convert HTML to Pango Markup  
	$sEntryPango = preg_replace('@<font color=([^>]+)>(.*)</font>@iU', '<span fgcolor=\1>\2</span>', $sEntry);
	//strip all <font> tags that don't have "color" attribute
	$sEntryPango = preg_replace('@</?FONT[^>]*>@i', '', $sEntryPango); 
	//convert back to HTML:
	$sEntryHtml = str_replace('<span fgcolor', '<font color', $sEntryPango); 
	$sEntryHtml = str_replace('</span>', '</font>', $sEntryHtml);

	$sEntryPango = str_replace('<br>', '\n', $sEntryPango);
	$sEntryPango = preg_replace('/^¡/', '', $sEntryPango); 
	
	fwrite($fHtmlTab, $sEntryHtml);
	fwrite($fPangoTab, $sEntryPango);
}		

fclose($fHtmlTab);
fclose($fPangoTab);

//run StarDict's tabfile to create the dictionaries for StarDict and GoldenDict
system("tabfile $sFName-html.tab");
system("tabfile $sFName-pango.tab");

$sIfoHtml  = file_get_contents($sFName . '-html.ifo');
$sIfoPango = file_get_contents($sFName . '-pango.ifo');

if (!preg_match('/wordcount=([0-9]+)/', $sIfoHtml, $aMatch))
	exit("Error finding 'wordcount' in file '$sFName-html.ifo'"); 
else {
	$nWordCntHtml = $aMatch[1];
}	
if (!preg_match('/wordcount=([0-9]+)/', $sIfoPango, $aMatch))
	exit("Error finding 'wordcount' in file '$sFName-pango.ifo'"); 
else {
	$nWordCntPango = $aMatch[1];
}		
if (!preg_match('/idxfilesize=([0-9]+)/', $sIfoHtml, $aMatch))
	exit("Error finding 'idxfilesize' in file '$sFName-html.ifo'"); 
else {
	$nIdxSizeHtml = $aMatch[1];
}	

if (!preg_match('/idxfilesize=([0-9]+)/', $sIfoPango, $aMatch))
	exit("Error finding 'idxfilesize' in file '$sFName-pango.ifo'"); 
else {
	$nIdxSizePango = $aMatch[1];
}	

$sBookName = 'Quechua Cochabambino–Castellano (Herrero y Sánchez de Lozada)';
$sAuthor   = "Joaquín Herrero S.J. y Federico Sánchez de Lozada";
$sDesc     = "Joaquín Herrero S.J. y Federico Sánchez de Lozada, Diccionario Quechua: " . 
	"Estructura semántica del quechua cochabambino contemporáneo, Cochabamba, Bolivia, 1983, 581pp.";
$sDir      = 'qu-es-herrero-sanchez';
$sDate     = '1983';
			
$sIfoHtml = "StarDict's dict ifo file\n" .
		"version=2.4.2\n" .
		"wordcount=$nWordCntHtml\n" .
		"idxfilesize=$nIdxSizeHtml\n" .
		"bookname=$sBookName\n" .
		"description=$sDesc\n" .
		"author=$sAuthor\n" .
		"date=$sDate\n" .
		"website=http://www.illa-a.org/wp/diccionarios\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=g\n"; 

$sIfoPango = "StarDict's dict ifo file\n" .
		"version=2.4.2\n" .
		"wordcount=$nWordCntPango\n" .
		"idxfilesize=$nIdxSizePango\n" .
		"bookname=$sBookName\n" .
		"description=$sDesc\n" .
		"author=$sAuthor\n" .
		"date=$sDate\n" .
		"website=http://www.illa-a.org/wp/diccionarios\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=g\n"; 
		
		
file_put_contents($sFName . '-html.ifo', $sIfoHtml);
file_put_contents($sFName . '-pango.ifo', $sIfoPango);

//Create directories for the GoldenDict and StarDict files if they don't exist
if (file_exists($sDir . '-html'))
	system("rm $sDir-html/*");
else
	mkdir($sDir . '-html', 0755);

if (file_exists($sDir . '-pango'))
	system("rm $sDir-pango/*");
else 
	mkdir($sDir . '-pango', 0755);	
	
system("mv $sFName-pango.* $sDir-pango");
system("mv $sFName-html.* $sDir-html");
	 
/*
if ($sEsTab) {
	//run StarDict's tabfile to create the Spanish dictionaries for StarDict and GoldenDict
	system("tabfile $sEsTab");
	
	$sIfoEs = file_get_contents($sFNameEs . '.ifo');

	if (!preg_match('/wordcount=([0-9]+)/', $sIfoEs, $aMatch))
		exit("Error finding 'wordcount' in file '$sFNameEs.ifo'"); 
	else
		$nWordCnt = $aMatch[1];

	if (!preg_match('/idxfilesize=([0-9]+)/', $sIfoEs, $aMatch))
		exit("Error finding 'idxfilesize' in file '$sFNameEs.ifo'"); 
	else
		$nIdxSize = $aMatch[1];
	
	$sIfoEs = "StarDict's dict ifo file\n" .
		"version=2.4.2\n" .
		"wordcount=$nWordCnt\n" .
		"idxfilesize=$nIdxSize\n" .
		"bookname=$sBookNameES\n" .
		"description=$sDesc\n" .
		"author=$sAuthor\n" .
		"year=2005\n" .
		"web=http://www.illa-a.org\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=m\n";

	file_put_contents($sFNameEs . '.ifo', $sIfoEs);	
	
	//Create directories for the GoldenDict and StarDict files if they don't exist
	if (file_exists($sDirES))
		system("rm $sDirES/*");
	else
		mkdir($sDirES, 0755);
	
	system("mv $sFNameEs.* $sDirES/");
	system("cp $sDirES/$sFNameEs.tab $sFNameEs.tab");
}
*/ 
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
	//$s = preg_replace('/<\/b><b>/i', '', $s);
	$s = preg_replace('/<\/i><i>/i', '', $s);
	return $s;
}		


		
?>