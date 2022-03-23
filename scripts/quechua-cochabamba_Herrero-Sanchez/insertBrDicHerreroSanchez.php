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
	$sFOutput = $fparts['filename'] . '-brs.html';
}

//if can't open file or empty file, then exit.
if (!($sIn = file_get_contents($sFDic)))
	exit("Error: Can't open file '$sFDic' or file empty.\n");

$sIn = preg_replace('@([a-zñáéíóú](</I>)?[\?!\.]+(</I>)?)([ \n]*(<I>)?[¿¡]?[A-ZÑÁÉÍÓÚ])@m', '\1<BR>\2', $sIn);

file_put_contents($sFOutput, $sIn);

?>