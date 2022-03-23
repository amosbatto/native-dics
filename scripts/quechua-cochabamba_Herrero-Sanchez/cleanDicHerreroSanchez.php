<?php
/************************************************************************
Program: CleanDicHerreroSanchez.php
Author:  Amos B. Batto, email: amosbatto@yahoo.com
Project: ILLA, web: www.illa-a.org
Created: 2014-01-27, (La Paz, Bolivia)
License: Public domain 
*****************************************************************************/

$help = <<<HELP
cleanDicHerreroSanchez.php cleans up the HTML code which is produced by 
FineReader 8, so it can be edited in a word processor. The script converts
all divs and table cells <td> into paragraphs <p>. It removes all 
formatting except underlining <u>, italics <i>, and superposition <sup>. 

To call this program:
   php cleanDicHerreroSanchez.php DICTIONARY.htm
   
For help:	
	php convertDicHerreroSanchez.php -h

DICTIONARY.htm is the filename of the "Diccionario Quechua: Estructura 
semántica del quechua cochabambino contemporáneo" by Joaquín Herrero
S.J. and Federico Sánchez de Lozada saved in HTML (UTF-8 encoding) which is 
produced by FineReader 8.

OUTPUT is the optional filename for the generated files. If not included, 
then the generated files will have the same filename as DICTIONARY.htm with
"-cleaned.htm" attached to end.

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
	$sFOutput = $argv[2];
}
else {
	$sFOutput = $sFDic;
	//strip out the path and the extension:	
	$fparts = pathinfo($sFOutput);
	$sFOutput = $fparts['filename'] . '-cleaned.html';
}

//if can't open file or empty file, then exit.
if (!($sIn = file_get_contents($sFDic)))
	exit("Error: Can't open file '$sFDic' or file empty.\n");
	
//remove header:
$nBodyPosition = strpos($sIn, '<body>');
$sIn = substr($sIn, $nBodyPosition);
	
//$sIn = preg_replace('/<div[^>]*>/', '<p>', $sIn);	
//$sIn = str_replace('</div>', '</p>', $sIn);

$sIn = strip_tags($sIn, '<p><i><u><sup>');

//replace all tabs and non-breaking spaces with normal spaces and
//replace all multiple spaces with single space
$sIn = str_replace("\t", ' ', $sIn); 
$sIn = str_replace("&nbsp;", ' ', $sIn);
//$sIn = preg_replace('/ {2,}/', ' ', $sIn);
$sIn = preg_replace('/<P[^>]*>/mi', '<P>', $sIn); 


$aParasOut = array();
$aParasIn = preg_split('@<P>[ \n]*(</P>)?@mi', $sIn);
print count($aParasIn) . "\n";

for ($i = 0; $i < count($aParasIn); $i++) {
	$sPara = trim(strip_tags($aParasIn[$i], '<i><u><sup>'));
	$sParaStripped = trim(strip_tags($sPara));
	
	if (empty($sParaStripped))
		continue;
	
	//if a new entry, all starting letters should be in uppercase before [.?!]
	if ($i == 0 or preg_match('/^[¿¡]?[A-ZÑÁÉÍÓÚÜ][\'A-ZÑÁÉÍÓÚÜ0-9 ]*[\.\?!]/m', $sParaStripped)) {
		$aParasOut[] = $sPara;
	}
	else {
		$sPrevPara = array_pop($aParasOut);
		
		//if the line starts with dash or number then add to previous
		//paragraph, separated by a line break <br>
		if (preg_match('/^[\-0-9]/', $sParaStripped)) {
			$aParasOut[] = $sPrevPara . "<br>\n" . $sPara;
		}
		//if starts with uppercase letter and previous paragraph ends with [.?!], then
		//add to previous paragraph, separated by line break <br>
		elseif (preg_match('/^[¿¡]?[A-ZÑÁÉÍÓÚ]/', $sParaStripped) and 
			preg_match('/[\.\?!]$/', trim(strip_tags($sPrevPara)))) 
		{
			$aParasOut[] = $sPrevPara . "<br>\n" . $sPara;
		}
		//otherwise add this paragraph to the previous paragraph
		else {
			//if last character of previous paragraph terminates with dash, 
			//then eliminate dash and add current paragraph without space to previous paragraph
			if (preg_match('/-$/', $sPrevPara)) {
				$sPrevPara = preg_replace('/\-$/', '', $sPrevPara); 
				$aParasOut[] = $sPrevPara . $sPara;
			}
			else {
				$aParasOut[] = $sPrevPara . ' ' . $sPara;
			}
		}
	}
}
	
$header = <<<HEADER
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"
	"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="generator" content="Bluefish 2.2.3" >
<meta name="author" content="Amos Batto" >
<meta name="description" content="">
<meta name="keywords" content="Diccionario">

<title>Diccionario Quechua</title>
</head>
<body>

HEADER;
$fOutput = fopen($sFOutput, 'w');
fwrite($fOutput, $header);

foreach ($aParasOut as $sParaOut) {
	//throw out empty paragraphs
	//if (trim($sParaOut) == '')
	//	continue;		
	
	fwrite($fOutput, "<p>" . $sParaOut ."</p>\n");  
}

fwrite($fOutput, "\n</body>\n</html>");
fclose($fOutput);

?>