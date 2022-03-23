<?php
/************************************************************************
Program: convertVocUru.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com
Project: ILLA, web: www.illa-a.org
Created: 2013-12-12, (La Paz, Bolivia)
License: Public domain 
*****************************************************************************/

$help = <<<HELP
convertVocUru.php converts the Vocabulario Uru (Uchumataqu) by Kajta Hannss (2013) to be 
used in StarDict, GoldenDict and SimiDic and generates an HTML version of the 
vocabulary that can be edited later for print publication.

The script creates 10 TAB files (ES-URU, EN-URU, FR-URU, DE-URU 
and URU-ES for both Pango and HTML markup) and creates IFO files for each one. 
Then, it calls StarDict’s tabfile utility to create the electronic dictionaries.
After running this script, use SimiDic-Builder to create the SimiDic dictionaries.

To call this program:
   php convertVocUru.php VOCABULARIO-URU.html
   
For help:
	php convertVocUru.php -h

VOCABULARIO-URU.html is the filename of the Vocabulario Uru which has been converted to HTML.
Before running this script, open the Vocabulario Uru with LibreOffice/OpenOffice Calc and
go to File > Export and save it as XHTML. This option will preserve the notes. Saving it
as normal HTML, won’t preserve the notes.

The script will strip out all HTML tags in the definitions. Text between #...# will be
superposed, text between $...$ will be placed in cursive and text between @...@ will
be underlined. Then it will convert the text to Pango for StarDict and HTML for GoldenDict 
and SimiDic.

Requirements:
Assuming that using a Linux machine with UTF-8 default character set and PHP5 installed. 
StarDict’s tools need to be installed, which includes the tabfile program. 
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
$fparts = pathinfo($sFDic);
$sFName = $fparts['filename'];

//if can't open file or empty file, then exit.
if (!$sIn = file_get_contents($sFDic))
	exit();

$nPos = mb_stripos($sIn, '<tr');
if ($nPos === false) {
	exit("Error: Unable to find start of table.");
}
$sIn = mb_substr($sIn, $nPos);

$nPos = mb_stripos($sIn, '</table>');
if ($nPos === false) {
	exit("Error: Unable to find end of table.");
}
$sIn = mb_substr($sIn, 0, $nPos);

$aCols = array('ES', 'EN', 'DE', 'FR', 'Uhle 1894', 'Polo 1901', 'Bacarreza 1910', 'Lehmann Ancoaqui 1929', 
	'Lehmann Chi\'mu 1929', 'Florentino 1929', 'Nicolás 1929', 'Posnansky 1932', 'Métraux 1935', 'LaBarre 1941', 
	'Palavecino 1949', 'Vellard 1949', 'Vellard 1950', 'Vellard 1951', 'Vellard 1967', 'Muysken 2005');

//create HTML output file 
if (!($fOutputHtml = fopen($sFName . '-output.html', 'w')))
	exit("Error: Unable to open file '$sFName-output.html' for writing.");
	
$htmlHeader = <<<HEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN">
<HTML>
<HEAD>
	<META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=utf-8">
	<TITLE>Vocabulario Uru (Uchumataqu)</TITLE>
	<meta name="generator" content="Bluefish 2.2.3" >
	<meta name="author" content="Amos Batto" >
	<META NAME="CREATED" CONTENT="20050413;3422600">
	<META NAME="CHANGEDBY" CONTENT="Amos Batto">
	<META NAME="CHANGED" CONTENT="20131213;2420600">
	<STYLE>
		<!-- 
		BODY,DIV,TABLE,THEAD,TBODY,TFOOT,TR,TH,TD,P {font-family:"serif";}
		 -->
	</STYLE>
</HEAD>
<BODY>
<h1><center>Vocabulario Uru (Uchumataqu)</center></h1>
<p>
<center><b><big>Katja Hannss<br>2013</big></b></center>
</p>
<hr>
<h2>Índice</h2>
<p><b><big><a href="#introduccion">Introducción</a></big><b></p>
<p><b><big>Vocabulario Castellano - Uru (Uchumataqu)</big><br></p>
<p> &nbsp; &nbsp; <a href="#castellano_en_secciones">En secciones temáticas</a>:
<div style="margin-left:50px;">
	<a href="#cuerpo">Partes del cuerpo</a><br>
	<a href="#enfermedades">Enfermedades</a><br>
	<a href="#parentesco">Términos de parentesco</a><br>
	<a href="#pronombres">Pronombres personales</a><br>
	<a href="#interrogativas">Palabras interrogativas</a><br>
	<a href="#personas_gente">Personas, gente</a><br>
	<a href="#profesion">Profesión</a><br>
	<a href="#casa">Casa, utensilios domésticos</a><br>
	<a href="#danzas_musica">Danzas y música; fiestas</a><br>
	<a href="#ropa">Ropa</a><br>	
	<a href="#animales_bravos">Animales bravos</a><br>
	<a href="#animales_domest">Animales domésticos</a><br>
	<a href="#comida">Comida</a><br>
	<a href="#lanchas_pesca">Lanchas, botas, pescadoría</a><br>
	<a href="#agricultura">Agricultura</a><br>
	<a href="#tiempo_clima">Tiempo, clima</a><br>
	<a href="#pueblo">El pueblo</a><br>
	<a href="#religion">Religión</a><br>
	<a href="#tiempo">Tiempo</a><br>
	<a href="#numeralia">Numeralia</a><br>
	<a href="#otro">Otro</a><br>
	<a href="#verbos">Verbos</a><br>
	<a href="#verbos_intrans">Verbos intransitivos</a><br>
	<a href="#verbos_trans">Verbos transitivos</a><br>
	<a href="#verbos_ditrans">Verbos ditransitivos</a><br>
	<a href="#adj_adv_part">Adjetivos, adverbios, partículas</a><br>
	<a href="#negacion">Negación</a><br>	
	<a href="#colores">Colores</a><br>
	<a href="#adv_adj_espacial">Adverbios, adjetivos espacial</a><br>
	<a href="#adj_del_sabor">Adjetivos del sabor</a><br>
	<a href="#adj_fisicos">Adjetivos físicos, características</a><br>
	<a href="#otros">Otros</a><br>
</div></p>
<p> &nbsp; &nbsp; <a href="#castellano_en_orden">En Orden Alfabetico</a>
<div style="margin-left:50px;">
<table><tr valign="top"><td>
	<a href="#A-es">A</a> &nbsp; <br>
	<a href="#B-es">B</a><br>
	<a href="#C-es">C</a><br>
	<a href="#D-es">D</a><br>
	<a href="#E-es">E</a><br>
	<a href="#F-es">F</a><br>
</td><td>
	<a href="#H-es">H</a> &nbsp; <br>
	<a href="#I-es">I</a><br>
	<a href="#J-es">J</a><br>
	<a href="#K-es">K</a><br>
	<a href="#L-es">L</a><br>
	<a href="#LL-es">LL</a><br>
</td><td>
	<a href="#M-es">M</a> &nbsp; <br>
	<a href="#N-es">N</a><br> 
	<a href="#O-es">O</a><br>
	<a href="#P-es">P</a><br>
	<a href="#Q-es">Q</a><br>
	<a href="#R-es">R</a><br>
</td><td>
	<a href="#S-es">S</a> &nbsp; <br>
	<a href="#T-es">T</a><br>
	<a href="#U-es">U</a><br>
	<a href="#V-es">V</a><br>
	<a href="#W-es">W</a><br>
	<a href="#Y-es">Y</a><br>
</td><td> 
	<a href="#Z-es">Z</a> &nbsp; <br>
</td></tr></table></div>
</b>
</p>
<p><b><big><a href="#uru">Vocabulario Uru (Uchumataqu) - Castellano</a></big><br>
<div style="margin-left:50px;">
<table><tr><td>
	<a href="#A-uru">A</a> &nbsp; <br>
	<a href="#B-uru">B</a><br>
	<a href="#C-uru">C</a><br>
	<a href="#D-uru">D</a><br>
	<a href="#E-uru">E</a><br>
	<a href="#F-uru">F</a><br>
</td><td>
	<a href="#H-uru">H</a> &nbsp; <br>
	<a href="#I-uru">I</a><br>
	<a href="#J-uru">J</a><br>
	<a href="#K-uru">K</a><br>
	<a href="#L-uru">L</a><br>
	<a href="#M-uru">M</a><br>
</td><td>
	<a href="#N-uru">N</a> &nbsp; <br>
	<a href="#Ñ-uru">N</a><br> 
	<a href="#O-uru">O</a><br>
	<a href="#P-uru">P</a><br>
	<a href="#Q-uru">Q</a><br>
	<a href="#R-uru">R</a><br>
</td><td>
	<a href="#S-uru">S</a> &nbsp; <br>
	<a href="#T-uru">T</a><br>
	<a href="#U-uru">U</a><br>
	<a href="#V-uru">V</a><br>
	<a href="#W-uru">W</a><br>
	<a href="#X-uru">X</a><br>
</td></tr></table></div>
</p>
<hr>
<h2 id="castellano_en_secciones"><center>Vocabulario Castellano - Uru (Uchumataqu)<br>En secciones temáticas	</center></h2>
<p></p>
HEADER;

fwrite($fOutputHtml, $htmlHeader);

//section IDs for the HTML document
$aEsSections = array (
	'cuerpo'          => 'partes del cuerpo', 
	'enfermedades'    => 'enfermedades',
	'parentesco'      => 'términos de parentesco',
	'pronombres'      => 'pronombres personales',
	'interrogativas'  => 'palabras interrogativas',
	'personas_gente'  => 'personas, gente',
	'profesion'       => 'profesión',
	'casa'            => 'casa, utensilios domésticos',
	'danzas_musica'   => 'danzas y música; fiestas',
	'ropa'            => 'ropa',
	'animales_bravos' => 'animales bravos',
	'animales_domest' => 'animales domésticos',
	'comida'          => 'comida',
	'lanchas_pesca'   => 'lanchas, botas, pescadoría',
	'agricultura'     => 'agricultura',
	'tiempo_clima'    => 'tiempo, clima',
	'pueblo'          => 'el pueblo',
	'religion'        => 'religión',
	'tiempo'          => 'tiempo',
	'numeralia'       => 'numeralia',
	'otro'            => 'otro',
	'verbos'          => 'verbos',
	'verbos_intrans'  => 'verbos intransitivos',
	'verbos_trans'    => 'verbos transitivos',
	'verbos_ditrans'  => 'verbos ditransitivos',
	'adj_adv_part'    => 'adjetivos, adverbios, partículas',
	'negacion'        => 'negación',
	'colores'         => 'colores',
	'adv_adj_espacial'=> 'adverbios, adjetivos espacial',
	'adj_del_sabor'   => 'adjetivos del sabor',
	'adj_fisicos'     => 'adjetivos físicos, características',
	'otros'           => 'otros'
);


//empty arrays to hold the entries to the dictionaries
$aEsEntries = $aEnEntries = $aDeEntries = $aFrEntries = $aUruEntries = array();

$aRows = preg_split('/(<\/tr>)\s*<tr[^>]*>/im', $sIn); 

#start at 1 to skip the first row, which contains the headers
for ($iRows = 1; $iRows < count($aRows); $iRows++) {
	$aKeys = array(); //array holding key words (does not include any notes or sources)
	$aCells = preg_split('/(<\/td>)\s*<td[^>]*>/im', $aRows[$iRows]);
	
	if (count($aCells) < 20)
		exit("Error: Row " . ($iRows + 1) . " only has " . count($aCells) . " cells.");
	
	for ($iCells = 0; $iCells < 20; $iCells++) {
		//remove all HTML tags except <br> and convert all whitespace to a single space
		$sCell = strip_tags($aCells[$iCells], '<br>');
		$sCell = str_replace('&nbsp;', ' ', $sCell);
		$sCell = str_replace(html_entity_decode('&nbsp;'), ' ', $sCell); 
		$sCell = preg_replace('/\s+/m', ' ', $sCell);
		$sCell = preg_replace('/<br[^>]*>/m', '<br>', $sCell);
		
		//place text between #...# in <sup>...</sup>
		$sCell = preg_replace('/#([^#]+)#/', '<sup>\1</sup>', $sCell);

		//place text between @...@ in <u>...</u>
		$sCell = preg_replace('/@([^@]+)@/', '<u>\1</u>', $sCell);
		
		//place text between $...$ in <i>...</i>
		$sKey = $sCell = preg_replace('/\$([^\$]+)\$/', '<i>\1</i>', $sCell);
			 		
		//place annotations on a separate line
		//assuming that no more than one annotation in a cell
		if (preg_match("/\[ANNOTATION:(.+')\]/mU", $sCell, $aMatch, PREG_OFFSET_CAPTURE)) {

			$sKey = $sCell = str_replace($aMatch[0][0], '', $sCell);
			$sAnnotation = $aMatch[1][0];
			
			if (preg_match_all("/NOTE: '(.+)'(<br>|$)/mU", $sAnnotation, $aNotes)) {
				
				$nNotes = 0;
				for ($iNotes = 0; $iNotes < count($aNotes[1]); $iNotes++) {
					$sNote = $aNotes[1][$iNotes];
					
					if ($sNote == 'Katja:')
						continue;
					elseif ($sNote == 'pq:')
						$sCell .= ', <font color="green">Nota:</font> '; 
					elseif ($nNotes == 0) { //if first note, don't add line break
						$sCell .= '<font color="grey">' . $sNote . '</font>';
						$nNotes++;
					}
					else { //subsequent notes add line break
						$sCell .= '<br><font color="grey">' . $sNote . '</font>';
					}
				}
			}	
		}	
		//remove any <br> and spaces at the end and the beginning of the cell
		$sCell = preg_replace('/^(\s*<br>\s*)+/m', '', $sCell);
		$sCell = preg_replace('/(\s*<br>\s*)+$/m', '', $sCell);
		$aCells[$iCells] = trim($sCell);
		
		//Future implementation: Convert all keys to Muyskin's alphabet which will be more easily searched
		$aKeys[] = trim(strip_tags($sKey));
		
	}
	
	$aUruKeys = array_unique(array_slice($aKeys, 4));
	
	//construct the Uru Definitions	
	$sUruDefs = '';
	for ($iUruDefs = 19; $iUruDefs >= 4; $iUruDefs--) {
		$sUruDef = trim($aCells[$iUruDefs]);
		
		if ($sUruDef == '')
			continue;
		
		$author = preg_replace('/\s*\d*$/', '', $aCols[$iUruDefs]);
		$year	= mb_substr($aCols[$iUruDefs], -4);
		
		//if the Uru Definition doesn't contain both the author and the year, then insert the source
		if (!(mb_stripos($sUruDef, $author) !== false and mb_strpos($sUruDef, $year) !== false)) {
			$sUruDef .= " <font color=\"grey\">({$aCols[$iUruDefs]})</font>";
		}	
		$sUruDefs .= ($sUruDefs ? '; ' : '') . $sUruDef;
	}
	
	//if no Uru definitions, then a section header
	if (empty($sUruDefs)) {
		//don't print empty lines
		if (empty($aKeys[0])) 
			continue;		
			
		$sHeader = '<font color="blue">ES:</font> ' . $aCells[0] .
			($aKeys[1] != '' ? '; <font color="blue">EN:</font> ' . $aCells[1] : '') .
			($aKeys[2] != '' ? '; <font color="blue">DE:</font> ' . $aCells[2] : '') .
			($aKeys[3] != '' ? '; <font color="blue">FR:</font> ' . $aCells[3] : '');
			
		//If a section header, place in H3 tags	
		if ($sectionKey = array_search($aKeys[0], $aEsSections)) {
			fwrite($fOutputHtml, "<h3 id=\"$sectionKey\">$sHeader</h3>\n");
			$aEsSections[$sectionKey] = '';
		}
		else { //A normal entry which lacks an uru definition. Place in red to mark it. 			
			fwrite($fOutputHtml, "<p><font color=\"red\">$sHeader</font></p>\n");
		}
		continue;
	}	
	
	$sUruDefs = '<font color="blue">URU:</font> ' . $sUruDefs;
	
	//terminate the Uru definitions with '.' if doesn't already end with '.', '?' or '!'
		$lastChar = mb_substr(strip_tags($sUruDefs), -1);
	if (!preg_match('/[\.\?!]\s*$/', strip_tags($sUruDefs)))
		$sUruDefs .= '.';
	 				 		
	$sEs = $sEn = $sDe = $sFr = '';
	
	if ($aKeys[0] != '') 
		$sEs = '<font color="blue">ES:</font> ' . $aCells[0];

	if ($aKeys[1] != '') 
		$sEn = '<font color="blue">EN:</font> ' . $aCells[1];

	if ($aKeys[2] != '') 
		$sDe = '<font color="blue">DE:</font> ' . $aCells[2];
	
	if ($aKeys[3] != '') 
		$sFr = '<font color="blue">FR:</font> ' . $aCells[3];			
	
	$font = '<font face="Doulos SIL">';
	$fontEnd = '</font>';
	
	//add entry to Spanish dictionary:
	$sEsEntry = 
		//if ES key not the same as the ES definition, then it contains a note to be added to definition 
		($aCells[0] != $aKeys[0] ? "$sEs; " : '') . 
		$sEn . ($sEn ? '; ' : '') . 
		$sDe . ($sDe ? '; ' : '') .
		$sFr . ($sFr ? '; ' : '') . 
		$sUruDefs;
  	$aEsEntries[] = $aKeys[0] . "\t" . $font . $sEsEntry . $fontEnd;

  	fwrite($fOutputHtml, "<p><b>{$aKeys[0]}.</b> $font$sEsEntry$fontEnd</p>\n");
				
  	//add entry to English dictionary:	
   if (!empty($sEn)) {  
		$aEnEntries[] = $aKeys[1] . "\t" . $font .
			//if EN key not the same as the EN definition, then it contains a note to be added to definition 
			($aCells[1] != $aKeys[1] ? "$sEn; " : '') . 
			$sEs . ($sEs ? '; ' : '') . 
			$sDe . ($sDe ? '; ' : '') .
			$sFr . ($sFr ? '; ' : '') . 
			$sUruDefs . '</font>';
	}
	//add entry to German dictionary:	
   if (!empty($sDe)) {  
		$aDeEntries[] = $aKeys[2] . "\t" . $font .
			//if DE key not the same as the DE definition, then it contains a note to be added to definition 
			($aCells[2] != $aKeys[2] ? "$sDe; " : '') . 
			$sEs . ($sEs ? '; ' : '') . 
			$sEn . ($sEn ? '; ' : '') .
			$sFr . ($sFr ? '; ' : '') . 
			$sUruDefs . '</font>';
	}		
	//add entry to French dictionary:	
   if (!empty($sFr)) {  
		$aFrEntries[] = $aKeys[3] . "\t" . $font .
			//if FR key not the same as the FR definition, then it contains a note to be added to definition 
			($aCells[3] != $aKeys[3] ? "$sFr; " : '') . 
			$sEs . ($sEs ? '; ' : '') . 
			$sEn . ($sEn ? '; ' : '') .
			$sDe . ($sDe ? '; ' : '') . 
			$sUruDefs . '</font>';	
	}		
	//add entries to Uru dictionary:
	foreach ($aUruKeys as $sUruKey) { 		
		if (!empty($sUruKey))
			$aUruEntries[] = $sUruKey . "\t" . $font . 
				$sEs . ($sEs ? '; ' : '') . 
				$sEn . ($sEn ? '; ' : '') . 
				$sDe . ($sDe ? '; ' : '') .
				$sFr . ($sFr ? '; ' : '') . 
				$sUruDefs . '</font>';
	}   
}

print "ES: " . count($aEsEntries) . ", EN: " . count($aEnEntries) . ", DE: " . count($aDeEntries) . 
	", FR: " . count($aFrEntries) . ", Uru: " . count($aUruEntries) . "\n";

createDic($aEsEntries, 
	$lang     = "es", 
	$fname    = $sFName, 
	$bookName = "Castellano - Uru (Katja Hannß)",
	$author   = "Katja Hannß",
	$desc     = "Katja Hannß, Vocabulario Uru (Uchumataqu): Castellano-Uru, 2013.",
	$sDir     = 'es_uru-hannss');
	
createDic($aEnEntries, 
	$lang     = "en", 
	$fname    = $sFName, 
	$bookName = "English - Uru (Katja Hannß)",
	$author   = "Katja Hannß",
	$desc     = "Katja Hannß, Vocabulario Uru (Uchumataqu): English-Uru, 2013.",
	$sDir     = 'en_uru-hannss');

createDic($aDeEntries, 
	$lang     = "de", 
	$fname    = $sFName, 
	$bookName = "Deutsch - Uru (Katja Hannß)",
	$author   = "Katja Hannß",
	$desc     = "Katja Hannß, Vocabulario Uru (Uchumataqu): Deutsch-Uru, 2013.",
	$sDir     = 'de_uru-hannss');
	
createDic($aFrEntries, 
	$lang     = "fr", 
	$fname    = $sFName, 
	$bookName = "Français - Uru (Katja Hannß)",
	$author   = "Katja Hannß",
	$desc     = "Katja Hannß, Vocabulario Uru (Uchumataqu): Français-Uru, 2013.",
	$sDir     = 'fr_uru-hannss');

createDic($aUruEntries, 
	$lang     = "uru", 
	$fname    = $sFName, 
	$bookName = "Uru - Castellano (Katja Hannß)",
	$author   = "Katja Hannß",
	$desc     = "Katja Hannß, Vocabulario Uru (Uchumataqu): Uru-Castellano, 2013.",
	$sDir     = 'uru_es-hannss');
 

//create Spanish section of dictionary:
$aEsLetters = array (
	'A' => 'a ambos lados de la balsa',
	'B' => 'bahía',
	'C' => 'caballo',
	'D' => 'danza en la que los bailarines tienen bolsas en sus manos',
	'E' => 'eclipse de sol',
	'F' => 'faja, ceñidor',
	'G' => 'gallina',
	'H' => 'habas', 
	'I' => 'iglesia',
	'J' => 'jalar',
	'K' => 'k\'ispina',
	'L' => 'labio inferior',
	'LL' => 'llama',
	'M' => 'macho',
	'N' => 'nacer',
	'O' => 'obedecer',
	'P' => 'pachamama, madre de la tierra',
	'Q' => "que",
	'R' => "rabadilla",
	'S' => 'sábado',
	'T' => 'tabaco',
	'U' => 'último/-a',
	'V' => 'vaca',
	'W' => 'wankara',
	'Y' => 'yerno',
	'Z' => 'zampoña; género de música, tipo de sampoña [sic]'
);


$sDicHeader = "\n<hr>\n<h2 id=\"castellano_en_orden\"><center>Vocabulario Castellano - Uru (Uchumataqu)<br>(Orden alfabetico)</center></h2>";
fwrite($fOutputHtml, $sDicHeader);
$aEsOrdered = array();

foreach ($aEsEntries as $sEntry) {
	$aEntry = explode("\t", $sEntry, 2);
	$sKey = $aEntry[0];
	
	while (array_key_exists($sKey, $aEsOrdered)) {
		if (preg_match('/^(.+ )(\d)$/U', $sKey, $aMatches))
			$sKey = $aMatches[1] . strval(intval($aMatches[2]) + 1);		
		else
			$sKey .= " 1";
	}
		
	$aEsOrdered[$sKey] = $sEntry; 
}
		
ksort($aEsOrdered, SORT_LOCALE_STRING); 
$sDefPrev = $sKeyPrev = '';

foreach ($aEsOrdered as $sEntry) {
	$aEntry = explode("\t", $sEntry, 2);
	//check for duplicate entries and eliminate them
	if ($sKeyPrev == $aEntry[0] and $sDefPrev == $aEntry[1]) {
		continue; 
	}
	
	if ($letter = array_search($aEntry[0], $aEsLetters)) {
		fwrite($fOutputHtml, "<h3 id=\"$letter-es\">$letter</h3>\n");
		//set to empty to prevent duplicate keys from inserting same letter later
		$aEsLetters[$letter] = '';
	}
	 	
	$sKeyPrev = $aEntry[0];
	$sDefPrev = $aEntry[1]; 
		
	fwrite($fOutputHtml, "\n<p><b>{$aEntry[0]}.</b> $font{$aEntry[1]}$fontEnd</p>\n");
}


//create Uru (uchumataqu) section of dictionary:
$aUruLetters = array (
	'A' => 'ača',
	'B' => 'ba- (?)',
	'C' => 'čá-',
	'D' => 'depj- (?)',
	'E' => 'ech / choq choqña',
	'F' => 'farola-chay',
	'G' => 'ġắja',
	'H' => 'háča', 
	'I' => 'iakako',
	'J' => 'jachaki-chay',
	'K' => 'ka-‘á / kāy',
	'L' => 'laachs para / laachs pacha para',
	'M' => 'maa',
	'N' => 'nai',
	'Ñ' => 'ñeñe',
	'O' => 'oč (?)',
	'P' => 'pa-',
	'Q' => "q’ā",
	'R' => "ṛā'kō",
	'S' => 'saaki',
	'T' => 'tá',
	'U' => 'ŭắks',
	'V' => 'vaxaña',
	'W' => 'wač’[i]',
	'X' => 'xala'
);

$sDicHeader = "\n<hr>\n<h2 id=\"uru\"><center>Vocabulario Uru (Uchumataqu)<br>Uru - Castellano/English/Deutsch/Français</center></h2><p></p>";
fwrite($fOutputHtml, $sDicHeader);
$aUruOrdered = array();

foreach ($aUruEntries as $sEntry) {
	$aEntry = explode("\t", $sEntry, 2);
	$sKey = $aEntry[0];
	
	while (array_key_exists($sKey, $aUruOrdered)) {
		if (preg_match('/^(.+ )(\d)$/U', $sKey, $aMatches))
			$sKey = $aMatches[1] . strval(intval($aMatches[2]) + 1);		
		else
			$sKey .= " 1";
	}
		
	$aUruOrdered[$sKey] = $sEntry; 
}
		
ksort($aUruOrdered, SORT_LOCALE_STRING); 
$sDefPrev = $sKeyPrev = '';

foreach ($aUruOrdered as $sEntry) {
	$aEntry = explode("\t", $sEntry, 2);
	//check for duplicate entries and eliminate them
	if ($sKeyPrev == $aEntry[0] and $sDefPrev == $aEntry[1]) {
		continue; 
	}
	
	if ($letter = array_search($aEntry[0], $aUruLetters)) {
		fwrite($fOutputHtml, "<h3 id=\"$letter-uru\">$letter</h3>\n");
		//set to empty to prevent duplicate keys from inserting same letter later
		$aUruLetters[$letter] = '';
	} 
					
	fwrite($fOutputHtml, "\n<p>$font<b>{$aEntry[0]}.</b> {$aEntry[1]}$fontEnd</p>\n");
	$sKeyPrev = $aEntry[0];
	$sDefPrev = $aEntry[1]; 
}

//close dictionary 
fwrite($fOutputHtml, "\n</body>\n</html>");
fclose($fOutputHtml);

return;

//function to convert a string from HTML to Pango Markup
function html2pango($s) {
	$sPango = preg_replace('/<font color=([^>]+)>/i', '<span fgcolor=\1>', $s);
	$sPango = preg_replace('/<font face=([^>]+)>/i', '<span face=\1>', $sPango);
	$sPango = str_replace('</font>', '</span>', $sPango);
	$sPango = str_replace('<br>', '\n', $sPango);
	//white screen of StarDict needs darker colors than GoldenDict and SimiDic
	$sPango = str_replace('="green">', '="#228B22">', $sPango); //convert to darker green
	$sPango = str_replace('="grey">',  '="#696969">', $sPango); //convert to darker grey
	
	return $sPango;
}	

/* function createDic() to create StarDict and GoldenDict dictionaries
Takes an array of dictionary entries and generate the HTML and Pango TAB files. 
Then it calls StarDict's tabfile to generate the dictionary files. 
Then moves the files to a separate directory. 
Finally searchs through the generated IFO files and inserts information about the dictionary. */
function createDic($aEntries, $lang, $fname, $bookName, $author, $desc, $sDir) {
	//add \n to end to prevent tabfile from throwing an error:
	$sEntries = implode("\n", $aEntries) . "\n";
	
	//convert from HTML to Pango Markup for StarDict
	$sEntriesPango = html2pango($sEntries);
	
	//write TAB files:
	file_put_contents("$fname-$lang-html.tab", $sEntries);
	file_put_contents("$fname-$lang-pango.tab", $sEntriesPango);
	
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
			"year=2013\n" .
			"web=http://www.illa-a.org/diccionarios\n" .
			"email=amosbatto@yahoo.com\n" .
			"sametypesequence=g\n"; 
	
	$sIfoPango = "StarDict's dict ifo file\n" .
			"version=2.4.2\n" .
			"wordcount=$nWordCntPango\n" .
			"idxfilesize=$nIdxSizePango\n" .
			"bookname=$bookName\n" .
			"description=$desc\n" .
			"author=$author\n" .
			"year=2013\n" .
			"web=http://www.illa-a.org/diccionarios\n" .
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
