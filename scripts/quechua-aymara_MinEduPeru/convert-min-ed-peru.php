<?php
/************************************************************************
Program: convert-min-ed-peru.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com
Project: ILLA, web: www.illa-a.org
Created: 2013-11-17, (La Paz, Bolivia)

convert-min-ed-peru.php converts the 4 Quechua and Aymara dictionaries for school children 
from the Ministry of Education of Peru to be used in StarDict, GoldenDict and SimiDic.

To call this program:
   php convert-min-ed-peru.php LANGUAGE DICTIONARY.html [DICTIONARY-ES.tab]

LANGUAGE indicates the language of the dictionary:
   CUZ (Quechua Cuzco), AYA (Quecua Ayacucho), ANC (Quechua Ancash), AYM (Aymara)

DICTIONARY.html is the name of the HTML file to convert into an electronic dictionary.
DICTIONARY.html should be just the Quechua or Aymara section of the dictionary and 
it should be saved as HTML format. 

The script will strip out all HTML tags except <P>, <I> and <FONT COLOR="..."> in the definitions.
Then it converts the text to Pango for StarDict and HTML for GoldenDict and SimiDic.

[DICTIONARY-ES.tab] is the Spanish section of the dictionary in plain text with tabs separating 
the key words and their definitions. If not included, then the script will not create the Spanish
dictionaries.

Requirements:
Assuming that using a Linux machine with UTF-8 default character set and PHP5 installed. StarDict's tools need to 
be installed, which includes the tabfile program. 

Future implementation:
In addition this script will create an .sql file to insert the dictionary entries in HTML format 
into a MySQL database named "dic" with the table "voc".

After running the script, start up MySQL as administrator in MS Windows or 
as root in Linux/UNIX:
$ su [enter root password] 

Enter MySQL:
# mysql
mysql> SOURCE [sql-file];

       In this case [sql-file] would be replaced with something like: 
       mysql> SOURCE  DicMinEdPeruQuechuaCuzco.sql

You have to run this program with PHP5 with the mysql extension.

If the "dic" MySQL database doesn't exist, you will have to create it. In Windows, 
log in as administrator, then open a DOS command window. If using Linux/UNIX, 
switch to the root user: 
$ su [enter password for root]

Then enter MySQL and create the 'dic' database:
# mysql
mysql> CREATE DATABASE dic;

To see if the 'dic' database  exists:
mysql> USE dic;

To see if the 'voc' table exists:
mysql> DESCRIBE voc;

If the 'voc' table exists, then delete the contents of it:
mysql> DELETE FROM voc;

If the 'voc' table doesn't exist, then you will have to create it:
mysql> CREATE TABLE IF NOT EXISTS voc (
	-> id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT, 
	-> lengua ENUM('Q', 'A', 'G', 'E') NOT NULL, 
	-> dic ENUM('A', 'T', 'G', 'C', '4', 'F', 'H', 'L', 'Z', 'Y', 'N', 'M') NOT NULL, 
	-> clave VARCHAR(80) NOT NULL, 
	-> definicion TEXT NOT NULL, 
	-> entrada TEXT NOT NULL, 
	-> clave_son VARCHAR(80) NOT NULL, 
	-> definicion_son TEXT NOT NULL,
	-> creado DATETIME NOT NULL, 
	-> actualizado TIMESTAMP NOT NULL, 
	-> INDEX idx_clave (clave, dic, lengua), 
	-> FULLTEXT (clave), 
	-> FULLTEXT(definicion), 
	-> FULLTEXT(clave_son), 
	-> FULLTEXT(definicion_son), 
	-> PRIMARY KEY(id)
	-> );

Note: 
lengua values: 
Q (quechua), A (aymara), G (guaraní), E (español)

dic values: 
A (AMLQ, Quechua Cuzqueño) 
T (Teofilo Laime, Quechua Boliviano)
G (Leoncio Gutierrez, Quechua Apurimeño)
C (Gregorio Callasaya, Nuevos Terminos Aimaras) 
4 (Arusimiñee, Ministerio de Educación de Bolivia)  
F (Felix Laime, Aymara)
H (Diego Gonzalez Holguín, Quechua cuzqueño historico, 1608)
L (Ludovico Bertonio, Aymara peruano historico, 1612)
Z (Ministerio de Educación de Peru, Quechua Cuzco escolar)   
Y (Ministerio de Educación de Peru, Quechua Ayacucho escolar)
N (Ministerio de Educación de Peru, Quechua Ancash escolar)
M (Ministerio de Educación de Peru, Aymara escolar)

Then check if the saphi user exists:
mysql> SHOW GRANTS FOR saphi;

If user saphi doesn't exist or there are no privileges for saphi, create them:
mysql> GRANT DELETE, SELECT, UPDATE, INSERT ON dic.voc TO saphi 
    -> INDENTIFIED BY 'secret';
[The password is not 'secret'--ask Amos Batto for it] 

Exit MySQL:
mysql> EXIT;

If in Linux/UNIX, exit the root user account:
# exit

*****************************************************************************/
ini_set('max_execution_time', 0);  //set no max time for this program to run.
ini_set('memory_limit', '-1');  //set no memory limit
ini_set('register_argc_argv', true);
ini_set('auto_detect_line_endings', 'On');
//include("./soundslike.php");
setlocale(LC_ALL, 'es_BO');

$sPath = './';

$help = "convert-min-ed-peru.php converts the 4 Quechua and Aymara dictionaries for school children \n" . 
	"from the Ministry of Education of Peru to be used in StarDict, GoldenDict and SimiDic. \n" .
	"\n" .
	"To call this program:\n" .
   "   php convert-min-ed-peru.php LANGUAGE DICTIONARY.html [DICTIONARY-ES.tab]\n" .
	"\n" .
	"DICTIONARY-FILE is the name of the HTML file to convert into an electronic dictionary. \n" .
	"DICTIONARY-FILE should be just the Quechua or Aymara section of the dictionary and \n" .
	"it should be saved to HTML format. \n" .
	"\n" .
	"LANGUAGE indicates the language of the dictionary: \n" .
	"CUZ (Quechua Cuzco), AYA (Quecua Ayacucho), ANC (Quechua Ancash), AYM (Aymara)\n" .
	"\n" .
	"[DICTIONARY-ES.tab] is the Spanish section of the dictionary in plain text with tabs \n" .
	"separating the key words and their definitions. If not included, then the script will not \n" .
	"create the Spanish dictionaries.\n";
	
if ($argc <= 1 or $argv[1] == '-h' or stristr($argv[1], 'help'))
	exit($help);

if ($argc < 3)
	exit("Error: Too few parameters.\n\n" . $help);
elseif ($argc > 4)
	exit("Error: Too many parameters.\n\n" . $help);

$sLang = strtoupper($argv[1]);

if (!in_array($sLang, ['QUZ', 'AYA', 'ANC', 'AYM'])) 
	exit("Error: Language '$sLang' is unknown.\n\n$help");
	
$sFDic = $argv[2];

$fparts = pathinfo($sFDic);
$sFName = $fparts['filename'];

if ($argc == 4) { 
	$sEsTab = $argv[3];
	$fparts = pathinfo($sEsTab);
	$sFNameEs = $fparts['filename'];
}
else {
	$sFEsTab = '';
}
			
$sFHtml  = $sFName . '-html.tab';
$sFPango = $sFName . '-pango.tab';
/*
$sFSql =  $sFName . '.sql';
$fSql = fopen($sFSql, 'w');
fwrite($fSql, "use dic;\n");
*/
$sIn = file_get_contents($sFDic);
$sOutHtml = '';
$iParas = $iDefs = 0;	//set paragraph and definition counters to zero

$aParagraphs = preg_split('/(<\/P>){0,1}\s*<P[^>]*>/im', $sIn, NULL, PREG_SPLIT_NO_EMPTY); 

#remove all text before first paragraph (like the header and the <body> tag)
if (preg_match('/<P[^>]*>/im', $aParagraphs[0], $aMatch, PREG_OFFSET_CAPTURE))
	$aParagraphs[0] = mb_substr($aParagraphs[0], $aMatch[0][1] + mb_strlen($aMatch[0][0]));
 
$sLastP = $aParagraphs[count($aParagraphs) - 1];

//remove all text after last paragraph like the closing </body> and </html> tags
if (preg_match('/(<\/P>|<\/BODY>)/i', $sLastP, $aMatch, PREG_OFFSET_CAPTURE))
	$aParagraphs[count($aParagraphs) - 1] = substr($sLastP, 0, $aMatch[0][1]);

for (; $iParas < count($aParagraphs); $iParas++) {
	$sPara = trim($aParagraphs[$iParas]);
	$sPara = preg_replace("/ *\n */", ' ', $sPara);       //remove all hard returns
	//$sPara = str_replace('\t', ' &nbsp; &nbsp; ', $sPara); //replace all tabs with 5 spaces
	$sPara = strip_tags($sPara, '<i><em><font>');          //remove all tags which aren't font and italics tags
	$sPlainText = trim(strip_tags($sPara));
	
	if (empty($sPlainText)) {
		print "Warning: Empty line $iParas.\n";
		continue;
	}
	
	//if line starts with - or a number then it is an example and should be added to the previous paragraph
	if (preg_match('/^[\-0-9]/', $sPlainText)) { 
		$sOutHtml .= '<br>' . $sPara;
		continue;
	}
	
	$iKey = mb_strpos($sPlainText, '. ');

	if ($iKey === false) {
		print "\nWarning: Discarding paragraph $iParas:\n$sPara\n";
		continue;
	}
	
	$sKey = mb_strtolower(mb_substr($sPlainText, 0, $iKey), 'UTF-8');
	//remove any font tag that starts the entry
	$sDef = preg_replace('/^<FONT[^>]*>([^<]*)<\/FONT>/i', '\1', $sPara, 1);
	
	//error check that the starting font tag isn't the only problem, before stripping the key from the definition	
	if (mb_stripos($sDef, $sKey, 0, 'UTF-8') !== 0)
		print("Error to fix in source file: Key \"$sKey\" doesn't start entry $iParas:\n$sDef\n");
	
	$sDef = trim(mb_substr($sDef, mb_strlen($sKey) + 1)); //remove the keyword from the definition
	//place grammar abbreviations in dark green (#228B22) for Pango (StarDict) and green for HTML (GoldenDict and SimiDic)
	$sDef = preg_replace('/\((r|s|p|h|ja)\)\. /', '<FONT COLOR="green">(\1).</FONT> ', $sDef); 
	//place definition numbers in bold
	$sDef = preg_replace('/([1-6])\. /', '<b>\1.</b> ', $sDef);
	
	$sOutHtml .= ($iParas == 0 ? '' : "\n") . "$sKey\t$sDef";
	
	/*
	$sIns = sprintf("INSERT INTO voc SET lengua = '%s', dic = 'L', clave = '%s', " .
		"clave_son = '%s', definicion = '%s', definicion_son = '%s', " .
		"entrada = '%s', creado = '%s';\n\n", $sLang, sqlPrep($sKey), 
		sqlPrep(soundsLike($sKey)), sqlPrep($sDef), sqlPrep(soundsLike($sDef)), 
		sqlPrep($sDef), date('Y-m-d H:i:s'));

	fwrite($fSql, $sIns);
	*/	
	$iDefs++;
}

//add \n to end to prevent tabfile from throwing an error:
$sOutHtml .= "\n";

//sanitize output by removing any <font> tags which aren't "color" and convert all tags to lowercase 
$sOutHtml = preg_replace('/<(\/?)(EM|I)>/i', '<\1i>', $sOutHtml);
$sOutPango = preg_replace('/<FONT +COLOR *=([^>]+)>(.*)<\/FONT>/iU', '<span fgcolor=\1>\2</span>', $sOutHtml);
$sOutPango = preg_replace('/<\/?FONT[^>]*>/i', '', $sOutPango);      //strip all <font> tags that don't have "color" attribute
$sOutHtml = str_replace('<span fgcolor', '<font color', $sOutPango); //convert back to HTML
$sOutHtml = str_replace('</span>', '</font>', $sOutHtml);
$sOutPango = str_replace('<br>', '\n', $sOutPango);
$sOutPango = str_replace('="green">', '="#228B22">', $sOutPango);


file_put_contents($sFHtml, $sOutHtml);
file_put_contents($sFPango, $sOutPango);

print "Paragraphs: $iParas\tDictionary Definitions: $iDefs\n";

//run StarDict's tabfile to create the dictionaries for StarDict and GoldenDict
system("tabfile $sFHtml");
system("tabfile $sFPango");

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

switch ($sLang) {
	case 'QUZ': 	
		$sBookName   = "Quechua Cuzco (Min.Edu.Perú)";
		$sBookNameES = "Castellano-Quechua Cuzco (Min.Edu.Perú)";
		$sAuthor     = "Nonato Rufino Chuquimamani Valer";
		$sDesc       = "Yachakuqkunapaq Simi Qullqa, Qusqu Qullaw, Chichwa Simipi, Ministerio de Educación, Lima, Perú, 2005, 218pp";
		$sDir        = 'qu-cuzco-min-ed-peru';
		$sDirES      = 'es_qu-cuzco-min-ed-peru';
		break;
	case 'AYA':
		$sBookName   = "Quechua Ayacucho (Min.Edu.Perú)";
		$sBookNameES = "Castellano-Quechua Ayacucho (Min.Edu.Perú)";
		$sAuthor     = "G. Palomino Rojas y G. R. Quintero Bendezú";
		$sDesc       = "Yachakuqkunapa Simi Qullqa, Ayakuchu Chanka, Qichwa Simipi, Ministerio de Educación, Lima, Perú, 2005, 145pp";
		$sDir        = 'qu-ayacucho-min-ed-peru';		
		$sDirES      = 'es_qu-ayacucho-min-ed-peru';
		break;
	case 'ANC':
		$sBookName   = "Quechua Ancash (Min.Edu.Perú)";
		$sBookNameES = "Castellano-Quechua Ancash (Min.Edu.Perú)";
		$sAuthor     = "Leonel Alexander Menacho López";
		$sDesc       = "Yachakuqkunapa Shimi Qullqa, Anqash Qichwa Shimichaw, Ministerio de Educación, Lima, Perú, 2005, 131pp";
		$sDir        = 'qu-ancash-min-ed-peru';
		$sDirES      = 'es_qu-ancash-min-ed-peru';		
		break;
	case 'AYM':
		$sBookName   = "Aymara (Min.Edu.Perú)";
		$sBookNameES = "Castellano-Aymara (Min.Edu.Perú)";
		$sAuthor     = "N. Apaza Suca, D. Condori Cruz, M. N. Ramos Rojas";
		$sDesc       = "Yatiqirinaka Aru Pirwa, Qullawa Aymara Aru, Ministerio de Educación, Lima, Perú, 2005, 141pp";
		$sDir        = 'ay-min-ed-peru';		
		$sDirES      = 'es_ay-min-ed-peru';
		break;
}
			
$sIfoHtml = "StarDict's dict ifo file\n" .
		"version=2.4.2\n" .
		"wordcount=$nWordCntHtml\n" .
		"idxfilesize=$nIdxSizeHtml\n" .
		"bookname=$sBookName\n" .
		"description=$sDesc\n" .
		"author=$sAuthor\n" .
		"year=2005\n" .
		"web=http://www.illa-a.org\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=g\n"; 

$sIfoPango = "StarDict's dict ifo file\n" .
		"version=2.4.2\n" .
		"wordcount=$nWordCntPango\n" .
		"idxfilesize=$nIdxSizePango\n" .
		"bookname=$sBookName\n" .
		"description=$sDesc\n" .
		"author=$sAuthor\n" .
		"year=2005\n" .
		"web=http://www.illa-a.org\n" .
		"email=amosbatto@yahoo.com\n" .
		"sametypesequence=g\n"; 
		
		
file_put_contents($sFName . '-html.ifo', $sIfoHtml);
file_put_contents($sFName . '-pango.ifo', $sIfoPango);

//print "|" . $sIfoPango . "|\n";

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
