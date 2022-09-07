<?php
/************************************************************************
File: reversedic.php
License: Latest version of GNU General Protection License (GPL) 
		which can be found at http://www.gnu.org 
Author: Amos B. Batto (amosbatto@yahoo.com, http://www.ciber-runa.net/ab)
Created: 22 Jun 2006; Last Revised: 30 Jun 2006
 
instrans.php is a command line program which reverses the columns of dictionary
so that the definitions become the key words and vice versa. This script was
written to transform a Quechua to Spanish dictionary into a Spanish to
Quechua dictionary. The output of this program are pretty rough so you should be
prepared to edit by hand the results afterwards. It assumes that the key words 
separated from their definitions by a tab or 3 or more spaces.  It also 
assumes that the key words and definitions are separated by commas (or whatever 
string of characters are specified as the --separator) like this:

key_words1[, key_words2,...]    definition1[, definition2, definition 3,...]

Each definition will be paired with its corresponding key word(s) and put in 
alfabetic order in the output file. You can also specify strings of characters 
to ignore. For instance if you have parts of speech in your definitions, you 
specify that the abreviations for nouns, verbs, adverbs, ...etc. would
be ignored with the --ignore option. For instance, if you have the following 
dictionary file called en-es-dic.txt:

rant,rail,rave   v. despotricar
right field      s. jardin derecho, jardinero derecho
run              v. correr, funcionar, durar

and you had an ignore file called "parts-speech.txt":

v.
s.
adj.
adv.
conj.
imp.
interrog.
interj.

And you issued the following command:
php reversedic.php --ignore parts-speech.txt en-es.txt output.txt

Then the file output.txt would be created containing:

correr              run
despotricar         rant
despotricar         rail
despotricar         rave
durar               run
funcionar           run
jardin derecho      right field
jardinero derecho   right field


If reading this code, set your tab size equal to 4 spaces.
*************************************************************************/

//When this script is saved as a UTF-8 text document and run on the command line,
//causes three characters of garbage (U+00B4 U+2557 U+2510) to appear on the screen 
//in MS-DOS using PHP 5.1.4 (cli) in Windows XP Home Edition, Service Pack 2 (version 5.1)
//In Ubuntu 5.10 (breezy badger) PHP 5.0.5 (cli) it causes two mid-line dots to appear
//on the screen. I will register it as a bug.

$startTime = microtime(true);
$sOrigWDir = getcwd(); //store the original working directory, so can return to it later

//open files with the interface translations so instrans is multi-lingual
//(check if $_SERVER['PHP_SELF'] exists in Windows--if so, I can use it instead.)
$sProgramPath = pathinfo(realpath($argv[0]), PATHINFO_DIRNAME); 	
	
getArgs();	//get arguments passed by the user	

file(argv argc
processDicFile($sTransPo);
	processOrigPoFile($sOrigPo, $sTransPo, $sNewPo, $sNewPo);
}
else //if bSearch, then can be either files or directories, but otherwise both will be directories
{
	if ($bSearch)
	{
		if ($bTransDir)
		{
			processTransPoDir($sTransPo);
			makeIndexTransPo();
		}
		else
			processTransPoFile($sTransPo);
	}
	
	if ($bOrigDir)
	{
		//Make a temporary directory to hold new PO files while processing.
		$sTempDir = getcwd() . ($bDOS ? '\\' : '/') . "instrans_" . $startTime;
	
		if (!mkdir($sTempDir))
			shutdown(sprintf(__("Error: No puede crear el directorio temporario \"%s\"."), 
				$sTempDir), 31);
	}
	
	if ($bSearch)
	{
		if ($bOrigDir)
			processDir($sOrigPo, $sTransPo, $sTempDir, $sNewPo);
		else
			processOrigPoFile($sOrigPo, $sTransPo, $sNewPo, $sNewPo);
	}
	else
	{
		processDir($sOrigPo, $sTransPo, $sTempDir, $sNewPo);
	}
		
	if (file_exists($sNewPo) && is_dir($sNewPo))
	{
		if ($bDOS)
			system('rmdir /s /q ' . $sNewPo, $iRet);
		else 	//if Linux/UNIX/OS X
			system('rm -r -d ' . $sNewPo, $iRet); 
			
		if ($iRet)
			shutdown(sprintf(__("Error: No puede sobre-escribir el directorio \"%s\"."),
				$sNewPo), 32);
	}
	
	if ($bOrigDir)
		if (!rename($sTempDir, $sNewPo))
			shutdown(sprintf(__("Error: No puede renombrar el directorio \"%s\"."), 
				$sNewPo), 33);
}

if (!$bQuiet)
{
	$sOut = $bVerbose ? __("Resumen de procesamiento total:\n") : '';
	$sOut .= sprintf(__(
		"%d línea(s), %d objeto(s) PO y %d error(es) de sintaxis leido de \"%s\"\n" . 
		"%d línea(s), %d objeto(s) PO y %d error(es) de sintaxis leido de \"%s\"\n" .
		"%d línea(s) y %d objeto(s) PO escrito en \"%s\"\n"),
		$totOrigPoFileLines, $totOrigPoObjs, $totOrigPoSyntaxErr, $sOrigPo, 
		$totTransPoFileLines, $totTransPoObjs, $totTransPoSyntaxErr, $sTransPo,
		$totNewPoFileLines, $totNewPoObjs, $sNewPo);
		
	if ($bStrip)
	{
		if ($sStripStr !== null)
			if (substr(phpversion(), 0, 3) >= 5.1)
				$sOut .= sprintf(__("%d cadena(s) quitado\n"), $totStrips);
			else
				$sOut .= sprintf(__("Cadena(s) quitado en %d msgstr(s)\n"), $totStrips);
		else	// if striping ampersands
			if (substr(phpversion(), 0, 3) >= 5.1)
				$sOut .= sprintf(__("%d signo(s) \"&\" quitado\n"), $totStrips);
			else
				$sOut .= sprintf(__("Signo(s) \"&\" quitado en %d msgstr(s)\n"), 
					$totStrips);
	}
	
	if ($bReplace)
	{
		if ($sReplaceFind !== null)
			if (substr(phpversion(), 0, 3) >= 5.1)
				$sOut .= sprintf(__("%d reemplazo(s)\n"), $totReplaces);
			else
				$sOut .= sprintf(__("Reemplazo(s) en %d msgstr(s)\n"), $totReplaces);
		else	//if replacing variables
			if (substr(phpversion(), 0, 3) >= 5.1)
				$sOut .= sprintf(__("%d variable(s) reemplazada(s)\n"), $totReplaces);
			else
				$sOut .= sprintf(__("Variable(s) reemplazada(s) en %d msgstr(s)\n"), 
					$totReplaces);
	}		
	
	if ($bVerbose)
		$sOut .= sprintf(__("Tiempo de procesamiento total: %f segundos.\n"), 
			microtime(true) - $startTime);
			
	output($sOut, true);	
}

//for ($cnt = $cntTransPoObjs - 10; $cnt < $cntTransPoObjs; $cnt++)
//{	print $cnt . '=' ; var_dump($aTransPo[$cnt]->aMsgid);
//	var_dump($aTransPo[$cnt]->aMsgstr);}
//print (microtime(true) - $startTime);

return 0;
//end of program

//class holding PO object with all the formatting stripped off
class PoObj
{
	/*Each PO object has the following format:
	WHITE-SPACE
    # TRANSLATOR-COMMENTS
    #. AUTOMATIC-COMMENTS
    #: REFERENCE...
    #, FLAG...
	[domain DOMAIN-NAME]
    msgid UNTRANSLATED-STRING
	[msgid_plural UNTRANSLATED-PLURAL-STRING]
    msgstr TRANSLATED-STRING 
	[msgstrN PLURAL-TRANSLATED-STRING]
	
	the elements in [] are rarely used.*/

	//all PO elements will be arrays, because they can be multiline:
	public $aTransCom; 
	public $aAutoCom;
	public $aRef;
	public $aFlag;
	public $sDomain;	//a string because it should only be one line long.
	public $aMsgid;
	public $aMsgidPl;
	public $aMsgstr;	 
	public $aMsgstrNo;	//for ngettext() msgstr's like msgstr1 "..." & msgstr2 "..."
	//Unlike the other arrays, $aMsgstrNo includes the labels 
	//if there is a $aMsgstrNo, will also put the text of msgstr1 in $aMsgstr for searching

	//Returns a string which contains the PO object with new lines (\n)
	//Adds blank line to the end to terminate the PO object.
	public function getStr()
	{
		$sPo = '';
		
		if(is_array($this->aTransCom))
			foreach ($this->aTransCom as $s)
				$sPo .= '#  ' . $s . "\n";		
		
		if(is_array($this->aAutoCom))
			foreach ($this->aAutoCom as $s)
				$sPo .= '#. ' . $s . "\n";
		
		if(is_array($this->aRef))
			foreach ($this->aRef as $s)
				$sPo .= '#: ' . $s . "\n";
		
		if(is_array($this->aFlag))
			foreach ($this->aFlag as $s)
				$sPo .= '#, ' . $s . "\n";
		
		if(is_string($this->sDomain))
			$sPo .= 'domain "' . $this->sDomain . "\"\n";
		
		if(is_array($this->aMsgid))
		{
			$sPo .= 'msgid ';
			foreach ($this->aMsgid as $s)
				$sPo .= '"' . $s . "\"\n";
		}
		
		if(is_array($this->aMsgidPl))
		{
			$sPo .= 'msgid_plural ';
			foreach ($this->aMsgidPl as $s)
				$sPo .= '"' . $s . "\"\n";
		}

		if(is_array($this->aMsgstr))
		{
			if (is_array($this->aMsgstrNo))
			{
				foreach ($this->aMsgstrNo as $s)
					$sPo .= $s . "\n";
			}
			else
			{	
				$sPo .= 'msgstr ';
				foreach ($this->aMsgstr as $s)
					$sPo .= '"' . $s . "\"\n";
			}
		}
		
		if ($sPo != '')	//add a blank line if not empty string
			$sPo .= "\n";

		return $sPo;	
	}

	//When reading a PO object from a file or an array, pass
	//one line at a time into function addLine() which strips
	//off padding and flags ("#" "#." "#:" "#," "msgid" 
	//"msgstr" and double quotes) and adds line to a PoObj object.
	//
	//If returns a negative number, then error in sLine:
	//-1: sLine is not a string, -2: sLine is out of PO order, 
	//-3 and smaller: some other problem.
	
	//If returns 0, then blank line, so end of PO object. 
	//If returns positive number, then sLine was added to PO object.
	//1: Comment, 2: domain, 3: msgid, 4: msgid continued line, 
	//5: msgid_plural, 6: msgid_plural continued line, 
	//7: msgstr, 8: msgstr continued line, 9: msgstrN, 10 msgstrN continued line
	public function addLine($sLine)
	{
		global $sErr, 
			$iLastElement; //holds the value of the last element read--this helps catch syntax errors
		$iRet = null;
		
		if (!is_string($sLine))
			return $iLastElement = SYNTAX_ERR_NO_STR;	//-1	

		$sLine = trim($sLine);
			
		if ($sLine === '')
			return $iLastElement = SYNTAX_WHITESPACE;	// 0
		elseif ($sLine[0] === '#')
		{
			if (strlen($sLine) <= 1)		//if a blank translator comment
				$this->aTransCom[] = '';
			elseif ($sLine[1] === '.')		//if automatic comment
				$this->aAutoCom[] = ltrim(substr($sLine, 2));
			elseif ($sLine[1] === ':')		//if reference	
				$this->aRef[] = ltrim(substr($sLine, 2));
			elseif ($sLine[1] === ',')		//if flag
				$this->aFlag[] = ltrim(substr($sLine, 2));
			else							//if a translator comment
				$this->aTransCom[] = ltrim(substr($sLine, 1));
				
			if ($iLastElement > SYNTAX_COMMENT)
			{
				$sErr =  __("Commentario es afuera de la orden correcta de PO.");
				$iLastElement = SYNTAX_COMMENT;	//1
				return SYNTAX_ERR_ELEM_ORDER;	//-2
			}					
			else
				return $iLastElement = SYNTAX_COMMENT;	// 1
		}
		elseif ($sLine[0] === '"')	//if a continued line of a msgid or msgstr
		{
			if ($iLastElement < SYNTAX_MSGID) 
			{
				$sErr = __("Esta línea de texto es fuera de la orden PO o no tiene un msgid ni msgstr definido.");
				//if last line was a syntax_error, comment, whitespace, or domain then put line in
				//translator's comments, because don't know what to do with it but don't want
				//to loose it.
				$this->aTransCom[] = 'SYNTAX ERROR: ' . $sLine;
				return $iLastElement = SYNTAX_ERR_MSGID_NO_DEF;	//-3
			}	
			//if line doesn't terminate in double quotes, then syntax error
			elseif($sLine[strlen($sLine) - 1] != '"')
			{
				$sErr = __("Línea no termina en comillas.");
				$sLine .= '"';  //add quotes to fix line
				$iRet = SYNTAX_ERR_NO_QUOTES;	// -4
			}
			elseif ($iLastElement == SYNTAX_MSGID)
			{
				$this->aMsgid[] = substr($sLine, 1, -1);
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : 
					SYNTAX_MSGID_CONT;	// 4
			}
			elseif ($iLastElement == SYNTAX_MSGSTR)
			{			
				$this->aMsgstr[] = substr($sLine, 1, -1); //strip double quotes from ends
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : 
					SYNTAX_MSGSTR_CONT;	// 8;
			}
			elseif ($iLastElement == SYNTAX_MSGSTRN)
			{			
				//if still in the first msgstr (not in msgstr2 yet), then also add to $aMsgstr
				if (strnpos($this->strMsgstrNo(), "\nmsgstr", 6) === false) 
					$this->aMsgstr[] = substr($sLine, 1, -1);
				
				$this->aMsgstrNo[] = $sLine;
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : 
					SYNTAX_MSGSTRN_CONT; // 10;
			}
			elseif ($iLastElement == SYNTAX_MSGIDPL)
			{
				$this->aMsgidPl[] = substr($sLine, 1, -1);
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : 
					SYNTAX_MSGIDPL_CONT;	// 6
			}
			else
			{
				$sErr = "Bug in PoObj::addLine(). Please report it to amosbatto@yahoo.com";
				return -20;
			}
		}
		elseif (eregi("^msgid[ \t]*\"", $sLine))	
		{
			//if line doesn't terminate in double quotes, then syntax error
			if ($sLine[strlen($sLine) - 1] != '"') 
			{
				$sErr = 'mgsid' . __(" no está encerrado en comillas.");
				$iRet = SYNTAX_ERR_NO_QUOTES;
				$sLine .= '"';
			}
			
			$this->aMsgid[] = substr(strstr($sLine, '"'), 1, -1); 
							
			if ($iLastElement > SYNTAX_MSGID)
			{
				$sErr = 'msgid' . __(" es definido afuera de la orden correcta de PO.");	
				$iLastElement = SYNTAX_MSGID; //3
				return SYNTAX_ERR_ELEM_ORDER;
			}					
			else
			{
				$iLastElement = SYNTAX_MSGID; //3
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : SYNTAX_MSGID;			
			}
		}	
		elseif (eregi("^msgstr[ \t]*\"", $sLine))
		{	
			//if line doesn't terminate in double quotes, then syntax error
			if ($sLine[strlen($sLine) - 1] != '"') 
			{
				$sErr = 'mgsstr' . __(" no está encerrado en comillas.");
				$iRet = SYNTAX_ERR_NO_QUOTES;
				$sLine .= '"';
			}
			
			$this->aMsgstr[] = substr(strstr($sLine, '"'), 1, -1);
			
			if ($this->aMsgid === null)
			{
				$sErr = __("El msgid no es definido para este msgstr") . '.';
				$iLastElement = SYNTAX_MSGSTR; //5
				return -10;
			}						
			else
			{
				$iLastElement = SYNTAX_MSGSTR; //5
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : SYNTAX_MSGSTR;			
			}
		}		
		elseif (eregi("^msgstr[0-9]+[ \t]*\"", $sLine))	//if a msgstr1, msgstr2, ...etc.
		{	
			//if line doesn't terminate in double quotes, then syntax error
			if ($sLine[strlen($sLine) - 1] != '"') 
			{
				$sErr = 'mgsstrN' . __(" no está encerrado en comillas.");
				$iRet = SYNTAX_ERR_NO_QUOTES;
				$sLine .= '"';
			}
			
			$this->aMsgstrNo[] = $sLine;
			
			//if the aMsgstr isn't yet defined, fill it as well
			if ($this->aMsgstr === null)	
				$this->aMsgstr[] = substr(strstr($sLine, '"'), 1, -1);
					
			if($this->aMsgid === null)
			{
				$sErr = __("El msgid no es definido para este msgstr") . 'N.';
				$iLastElement = SYNTAX_MSGSTRN;
				return -10;
			}
			else
			{
				$iLastElement = SYNTAX_MSGSTRN; //9			
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : SYNTAX_MSGSTRN;
			}
		}
		elseif (eregi("^msgid_plural[ \t]*\"", $sLine))
		{
			//if line doesn't terminate in double quotes, then syntax error
			if ($sLine[strlen($sLine) - 1] != '"') 
			{
				$sErr = 'mgsid_plural' . __(" no está encerrado en comillas.");
				$iRet = SYNTAX_ERR_NO_QUOTES;
				$sLine .= '"';
			}
			
			$this->aMsgidPl[] = substr(strstr($sLine, '"'), 1, -1); 
							
			if ($iLastElement > SYNTAX_MSGIDPL)
			{
				$sErr = 'msgid_plural' . __(" es definido afuera de la orden correcta de PO.");	
				$iLastElement = SYNTAX_MSGIDPL; //5
				return SYNTAX_ERR_ELEM_ORDER;
			}					
			else
			{
				$iLastElement = SYNTAX_MSGIDPL; //5
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : SYNTAX_MSGIDPL;			
			}
		}	
		
		elseif (eregi("^domain[ \t]*\"", $sLine))
		{
			//if line doesn't terminate in double quotes, then syntax error
			if ($sLine[strlen($sLine) - 1] != '"') 
			{
				$sErr = 'domain' . __(" no está encerrado en comillas.");
				$iRet = SYNTAX_ERR_NO_QUOTES;
				$sLine .= '"';
			}
			
			$this->sDomain = substr(strstr($sLine, '"'), 1, -1);
								
			if ($iLastElement > SYNTAX_DOMAIN)
			{
				$sErr =  'domain' . __(" es definido afuera de la orden correcta de PO.");
				$iLastElement = SYNTAX_DOMAIN;
				return SYNTAX_ERR_ELEM_ORDER;
			}					
			else
			{
				$iLastElement = SYNTAX_DOMAIN;
				return $iRet == SYNTAX_ERR_NO_QUOTES ? SYNTAX_ERR_NO_QUOTES : SYNTAX_DOMAIN;
			}
		}
					
		//if reached this point, then syntax error
		$sErr = __("Línea no tiene forma conocida.");
		$this->aTransCom[] = 'SYNTAX ERROR: ' . $sLine;
		return $iLastElement = -8;	
	}
			

	//returns 0 if the msgids in the two po objects don"t match
	//returns 1 if only the msgids match
	//returns 2 if msgid and automatic comments match
	//returns 3 if msgid and reference match
	//returns 4 if msgid, reference, and automatic comments match
	public function match(PoObj $po)
	{
		if ($this->strMsgid() === $po->strMsgid())
		{
			if ($this->strRef() === $po->strRef())
			{
			  	if ($this->strAutoCom() === $po->strAutoCom())
					return 4;
				else
					return 3;
			}
			elseif ($this->strAutoCom() === $po->strAutoCom())
			{
			  	if ($this->strRef() === $po->strRef())
					return 4;
				else
					return 2;
			}	
			return 1;
		}	
		return 0;
	}

	public function clear()
	{
		$this->aTransCom = $this->aAutoCom = $this->aRef = $this->aFlag =
			$this->aMsgid = $this->aMsgstr = null;
		return;
	}
	
	public function cntLines()
	{
		return count($this->aTransCom) + count($this->aAutoCom) + count($this->aRef) + 
			count($this->aFlag) + count($this->aMsgid) + count($this->aMsgstr);
	}
	
	public function strAutoCom()
	{
		return (is_array($this->aAutoCom) ? implode('', $this->aAutoCom) : null);
	}
	
	public function strRef()
	{
		return (is_array($this->aRef) ? implode('', $this->aRef) : null);
	}
	
	public function strFlag()
	{
		return is_array($this->aFlag) ? implode('', $this->aFlag) : null;
	}
	
	//returns the msgid as a string
	public function strMsgid()
	{
		return is_array($this->aMsgid) ? implode('', $this->aMsgid) : null;
	}
	
	public function strMsgstr()
	{
		return is_array($this->aMsgstr) ? implode('', $this->aMsgstr) : null;
	}
	
	public function strMsgstrNo()	
	{
		//separate array members with "\n" because this is a string broken up by quotes and msgstr#
		return is_array($this->aMsgstrNo) ? implode("\n", $this->aMsgstrNo) : null;
	}
	
	//Function strip() implements the --strip option. It strips $sStripStr from the aMsgstr 
	//If $sStripStr is null (no STRING was specified by user), then it removes ampersands 
	//("&" or "&amp;") which are followed immediately by alphanumeric characters.  
	//Returns the number of strips 
	public function strip()
	{
		global $cntStrips, $bStrip, $sStripStr;
		$cnt = 0;
		
		if (!$bStrip || !is_array($this->aMsgstr))
			return 0;
		
		$s = $this->strMsgstr();
		
		if (substr(phpversion(), 0, 3) >= 5.1) //if PHP version 5.1.0 or later
		{
			if ($sStripStr === null)
			{
				//because found at array position 0 and false are confusable, need to use ===
				if (strpos($s, '&') !== false)
				{	
					if (strpos($s, '&amp;') === false)
						$this->aMsgstr = preg_replace('/&(\w)/', '$1', $this->aMsgstr, -1, $cnt);
					else
						$this->aMsgstr = preg_replace('/&amp;(\w)/', '$1', $this->aMsgstr, -1, $cnt);
				}
			}
			else	//if user specified a STRING
				$this->aMsgstr = preg_replace($sStripStr, '', $this->aMsgstr, -1, $cnt);
		} 
		else //if before PHP version 5.1.0
		{
			if ($sStripStr === null)
			{
				//because found at array position 0 and false are confusable, need to use ===
				if (strpos($s, '&') !== false)
				{	
					if (strpos($s, '&amp;') === false)
						$this->aMsgstr = preg_replace('/&(\w)/', '$1', $this->aMsgstr, -1);
					else
						$this->aMsgstr = preg_replace('/&amp;(\w)/', '$1', $this->aMsgstr, -1);
				}
			}
			else	//if user specified a STRING
			{
				$this->aMsgstr = preg_replace($sStripStr, '', $this->aMsgstr, -1);
			}
							
			if ($s != $this->strMsgstr())
				$cnt = 1;
		}
		
		$cntStrips += $cnt;
		return $cnt;
	}
	
	//Function replace() implements the --replace option. It replaces $sReplaceFind with
	//$sReplaceWithWhat in the msgstr.  If the user didn't specify what to replace, then
	//searches for programming variables and replaces them with @#@x@#@. Returns
	//a warning message if anything was replaced.  Otherwise returns false. 
	public function replace()
	{
		global $cntReplaces, $bReplace, $sReplaceFind, $sReplaceWithWhat;
		$cnt = 0;
		
		if (!$bReplace || !is_array($this->aMsgstr))
			return false;
		
		$s = $this->strMsgstr();
			
		if ($sReplaceFind === null)
		{
			if (is_array($this->aFlag) && strpos($this->strFlag(), '-format') !== false)
			{
				$sPattern = getVarPattern($this->strFlag());
				if ($sPattern === false && preg_match('/[a-z]+\-format/', $this->strFlag(), $a))
					output(sprintf(__("Aviso: \"%s\" todavía no es implementado.\n" .
						"Use opción --replace BUSQUEDA REEMPLAZO para especificar variables."), 
						$a[0])); 
				elseif ($sPattern !== '')
				{
					if (substr(phpversion(), 0, 3) >= 5.1) //if PHP version 5.1.0 or later
					{
						$this->aMsgstr = preg_replace($sPattern, DEF_VAR_REPLACE, 
							$this->aMsgstr, -1, $cnt);
				
						if ($cnt)
						{
							$cntReplaces += $cnt;
							return  $cnt == 1 ? 
								sprintf(__("%d variable reemplazado en msgstr \"%s\"\n"), $cnt, $s) :
								sprintf(__("%d variables reemplazado en msgstr \"%s\"\n"), $cnt, $s);
						}
					}
					else
					{
						$this->aMsgstr = preg_replace($sPattern, DEF_VAR_REPLACE, $this->aMsgstr, -1);
	
						if ($s != $this->strMsgstr())
						{
							$cntReplaces++;
							return  sprintf(__("Variable(s) reemplazado en msgstr \"%s\"\n"), $s);
						}
					}
				}
			}
		}
		else	//if user did specify FIND and REPLACEMENT strings
		{
			if (substr(phpversion(), 0, 3) >= 5.1) //if PHP version 5.1.0 or later
			{	
				$this->aMsgstr = preg_replace($sReplaceFind, $sReplaceWithWhat, 
					$this->aMsgstr, -1, $cnt);
				
				if ($cnt)
				{
					$cntReplaces += $cnt;
					return  $cnt == 1 ? 
						sprintf(__("%d reemplazo en msgstr \"%s\"\n"), $cnt, $s) :
						sprintf(__("%d reemplazos en msgstr \"%s\"\n"), $cnt, $s);
				}	
			}
			else	//if before PHP version 5.1.0
			{	
				$this->aMsgstr = preg_replace($sReplaceFind, $sReplaceWithWhat, 
					$this->aMsgstr, -1);
				
				if ($s != $this->strMsgstr())
				{
					$cntReplaces++;
					return  sprintf(__("Reemplazo(s) en msgstr \"%s\"\n"), $s);
				}
			}
		}

		return false;
	}
	
}	//end class PoObj	

	
//get arguments passed by the user
function getArgs()
{
	global $argc, $argv, $sDicFile, $sIgnoreFile, $sTransferFile, $sSeparator, 
		$sNewDicFile, $bQuiet, $bLog, $sLogFile, $bVerbose, $sProgramPath, 
		$bDOS;
	
	$bHelp = false; //set to true to display help screen
	
	if ($argc < 2) 
		shutdown(getHelp(), 21);
	elseif ($argc == 2 && in_array(strtoupper($argv[1]), array('--HELP', '-HELP', 
			'-H', '-?', '/?')))
		$bHelp = true;
	elseif ($argc == 2 && (strtoupper($argv[1]) == '-I' || strtoupper($argv[1]) == '--INFO'))
		$bInfo = true;
	elseif ($argc < 3)
		shutdown(__("Error: Falta argumentos.\n\n") . getHelp(), 22);
		
	 
	for ($cntArgs = 1; $cntArgs < $argc; $cntArgs++)
	{
		if ($argv[$cntArgs][0] === '-')
		{
			$sArg = strtoupper($argv[$cntArgs]);
		
			if ($sArg == '-O' || $sArg == '--OVERWRITE')
				$bOverwrite = true;
			elseif ($sArg == '-V' || $sArg == '--VERBOSE')
				$bVerbose = true;
			elseif ($sArg == '-M' || $sArg == '--MSGSTR')
				$bMsgstrIns = true;
			elseif ($sArg == '-N' || $sArg == '--NO-STRIP')
				$bNoStripVar = true;
			elseif ($sArg == '-R' || $sArg == '--RECURSIVE')
				$bRecursive = True;
			elseif ($sArg == '-P' || $sArg == '--REPLACE')
				$bReplace = true;
			elseif ($sArg == '-Q' || $sArg == '--QUIET')
				$bQuiet = True;
			elseif ($sArg == '-E' || $sArg == '--SEARCH')
				$bSearch = True;
			elseif ($sArg == '-D' || $sArg == '--DEBUG')
				$bDebug = true;
			elseif (!strncmp($sArg, '-F', 2) || !strncmp($sArg, '--INTERFACE', 11))
			{
				getSubArg($cntArgs, $sInterfaceLang, "--interface", "-f", __("IDIOMA"), "=", 25);
				$sInterfaceLang = strtolower($sInterfaceLang);
				
				if ($sInterfaceLang != DEF_SOURCE_LANG && 
						!file_exists($sProgramPath . '/locale/' . $sInterfaceLang))
				{
					output(sprintf(__("Aviso: Lengua de interfaz \"%s\" no es disponible.\n".
						"En su lugar usará lengua \"%s\".\n"), $sInterfaceLang, 
						DEF_INTERFACE_LANG), true);
						
					$sInterfaceLang = DEF_INTERFACE_LANG;
				}
				
				if ($sInterfaceLang != DEF_SOURCE_LANG)
					include_once($sProgramPath . '/locale/' . $sInterfaceLang);
			}	
			elseif (!strncmp($sArg, '-S', 2) || !strncmp($sArg, '--STATUSBAR', 11))
			{
				$bStatusBarIns = true;		
				getSubArg($cntArgs, $sMenuSrch, "--statusbar", "-s", 
					__("FRASE-MENU"), "=", 23);
								
				if ($sMenuSrch === null)	//if search strings not specified, then set defaults
				{	
					$sMenuSrch = "MENU";
					$sStatusBarSrch = "STATUSBAR";
				}
				else
				{
					if ($cntArgs + 1 >= $argc)
						shutdown(__("Error: Hay que especificar la FRASE-BARRA-DE-ESTADO " .
							"de la opción --statusbar.\n\n") . getHelp(), 24);
					else
						$sStatusBarSrch = $argv[++$cntArgs];
				}
			}
			elseif (!strncmp($sArg, '-X', 2) || !strncmp($sArg, '--STRIP', 7))
			{
				$bStrip = true;		
				getSubArg($cntArgs, $sStripStr, "--strip", "-x", __("CADENA"), "=", 23);
				
				if($sStripStr !== null)
					$sStripStr = perlStr($sStripStr);
			}
			elseif (!strncmp($sArg, '-P', 2) || !strncmp($sArg, '--REPLACE', 9))
			{
				$bReplace = true;		
				getSubArg($cntArgs, $sReplaceFind, "--replace", "-p", __("BUSQUEDA"), "=", 23);
								
				if ($sReplaceFind !== null)	//if user specified a replacement string to search for
				{	
					if ($cntArgs + 1 >= $argc)
						shutdown(__(
							"Error: Hay que especificar el REEMPLAZO de la opción --replace.\n\n") 
							. getHelp(), 24);
					
					$sReplaceWithWhat = $argv[++$cntArgs];		
					$sReplaceFind = perlStr($sReplaceFind);
				}	
			}
			elseif (!strncmp($sArg, '-C', 2) || !strncmp($sArg, '--COMMENT', 9))
			{
				$bCommentIns = true;
				getSubArg($cntArgs, $sLangComment, "--comment", "-c", __("IDIOMA"), "=", 25);
			}
			elseif (!strncmp($sArg, '-B', 2) || !strncmp($sArg, '--BILINGUAL', 11))
			{
				$bBilingualIns = true;
				getSubArg($cntArgs, $sBilingualSeparator, "--bilingual", "-b", 
					__("SEPARADOR"), "=", 24);
			}
			elseif (!strncmp($sArg, '-L', 2) || !strncmp($sArg, '--LOG', 5))
			{
				$bLog = true;
				getSubArg($cntArgs, $sLogFile, "--log", "-l", __("ARCHIVO"), "=", 24);
			}
			elseif (in_array($sArg, array('--HELP', '-HELP', '-H', '-?', '/?')))
				$bHelp = True;
			elseif ($sArg == '-I' || $sArg == '--INFO')
				$bInfo = True;
			else
			{
				shutdown(sprintf(__("Error: \"%s\" no es un argumento valido."), 
					$argv[$cntArgs]), 19);
			}
		}
		elseif ($sOrigPo === null)
			$sOrigPo = $argv[$cntArgs];
		elseif ($sTransPo === null)
			$sTransPo = $argv[$cntArgs];
		elseif ($sNewPo === null)
			$sNewPo = $argv[$cntArgs];
		else
			shutdown(__("Error: Demasiado argumentos\n\n") . getHelp(), 26);
	}
	
	
	if ($bHelp)
		shutdown (getHelp(), 41);
	elseif ($bInfo)
	{
		//change current working directory to the same directory from which this program was run.
		chdir(pathinfo(realpath($argv[0]), PATHINFO_DIRNAME));
		
		//iconv has a bug, because it can't handle the first 3 bytes of a UTF-8 text file 
		shutdown(substr(file_get_contents(__("readme-es.txt")), 3), 42);
	}
	
	//check arguments:
	if ($sOrigPo === null)
		shutdown(__("Error: Hay que especificar ORIGINAL-PO y TRASLACIONES-PO.\n\n") . 
			getHelp(), 27);
	elseif ($sTransPo === null)
		shutdown(__("Error: Hay que especificar TRASLACIONES-PO.\n\n") . 
			getHelp(), 28); 
	elseif ($bBilingualIns && $bMsgstrIns)
		shutdown(__("Error: No se puede combinar opciones --msgstr y --bilingual.\n\n") . 
			getHelp(), 29);
			
	//check to see whether sOrigPo and sTransPo exist.
	if (myRealPath($sOrigPo) === false)
		shutdown(sprintf(__(
			"Error: Archivo o directorio de ORIGINAL-PO \"%s\" no existe."), 
			$sOrigPo), 33); 
		
	if (myRealPath($sTransPo) === false)
		shutdown(sprintf(__(
			"Error: Archivo o directorio de TRASLACIONES-PO \"%s\" no existe."),
			$sTransPo), 34);
	
	//make sure that they have complete path names, because processDir() changes the current
	//working directory 
	$sOrigPo = myRealPath($sOrigPo);	
	$sTransPo = myRealPath($sTransPo);
	
	if ($sNewPo === null)
		$sNewPo = $sOrigPo;
	
	if (myRealPath($sNewPo) === false)
	{
		if (is_dir($sOrigPo)) //if a file, don't worry about it because won't be changing working dir
		{
			//will create the full path of directories if they don't exist
			if (!mkdir($sNewPo, 0777, true))	
				shutdown (sprintf(__("Error: No puede crear el directorio NUEVO-PO \"%s\"."),
					$sNewPo), 35);
			
			$sNewPo = myRealPath($sNewPo);
		}
	}
	else
		$sNewPo = myRealPath($sNewPo);		
			
			
	if (!$bSearch && (is_dir($sTransPo) != is_dir($sOrigPo)))
	{
		if (is_dir($sOrigPo))
			shutdown(sprintf(__("Error: \"%s\" es un directorio, pero \"%s\" es un archivo.\n" .
				"Deben ser el mismo tipo.\n\n"), $sOrigPo, $sTransPo) .	getHelp(), 30);
		else
			shutdown(sprintf(__("Error: \"%s\" es un archivo, pero \"%s\" es un directorio.\n" .
				"Deben ser el mismo tipo.\n\n"), $sOrigPo, $sTransPo) . getHelp(), 31);
	}	
	
	//if none of the types of insertion are set, then set bMsgstrIns to true since it's the default
	if (!$bBilingualIns && !$bCommentIns && !$bMsgstrIns && !$bStatusBarIns)
		$bMsgstrIns = true;
	//make sure that some kind of insertion is taking place with the bStatusBarIns
	elseif ($bStatusBarIns && !$bBilingualIns && !$bCommentIns && !$bMsgstrIns)
		$bMsgstrIns = true;
		
	//if user didn't set $sLangComment, then set it to file name or directory name
	if ($bCommentIns && $sLangComment === null) 
	{
		//if directory, then get the last directory in path, 
		//Ex: get "es-ES" from "/home/user/strings/es-ES/"
		if (is_dir($sTransPo)) 
		{
			$sTemp = rtrim($sTransPo, '\\/');
			$pos = strrpos($sTemp, '\\') > strrpos($sTemp, '/') ? 
				strrpos($sTemp, '\\') : strrpos($sTemp, '/');
			$sLangComment = ltrim(substr($sTemp, $pos), '\\/');
		}
		else
			$sLangComment =  basename($sTransPo, '.po');
	}
			
	if ($bDebug)
	{
		print "n\$argc:"; 			var_dump($argc);
		print '$argv:'; 			var_dump($argv);
		print '$sOrigPo:'; 			var_dump($sOrigPo);
		print '$sTransPo:'; 		var_dump($sTransPo);
		print '$sNewPo:'; 			var_dump($sNewPo);
		print '$bLog:'; 			var_dump($bLog);
		print '$sLogFile:'; 		var_dump($sLogFile);
		print '$bMsgstrIns:'; 		var_dump($bMsgstrIns);
		print '$bCommentIns:'; 		var_dump($bCommentIns);
		print '$sLangComment:';		var_dump($sLangComment);
		print '$bOverwrite:'; 		var_dump($bOverwrite);
		print '$bQuiet:'; 			var_dump($bQuiet);
		print '$bVerbose:'; 		var_dump($bVerbose); 
		print '$bDebug:'; 			var_dump($bDebug); 
		print '$bBilingualIns:'; 	var_dump($bBilingualIns);
		print '$sBilingualSeparator:'; var_dump($sBilingualSeparator); 
		print '$bStatusBarIns:'; 	var_dump($bStatusBarIns);
		print '$sMenuSrch:'; 		var_dump($sMenuSrch); 
		print '$sStatusBarSrch'; 	var_dump($sStatusBarSrch); 
		print '$bRecursive:'; 		var_dump($bRecursive); 
		print '$sInterfaceLang:';	var_dump($sInterfaceLang); 
		print '$bDOS:';				var_dump($bDOS);				
		print '$sCharSet:';			var_dump($sCharSet);
		print '$sProgramPath:';		var_dump($sProgramPath);
		print '$bReplace:'; 		var_dump($bReplace); 
		print '$sReplaceFind:'; 	var_dump($sReplaceFind); 
		print '$sReplaceWithWhat:';	var_dump($sReplaceWithWhat); 
		print '$bStrip:';			var_dump($bStrip);				
		print '$sStripStr:';		var_dump($sStripStr);
			
		//if want to see $aGetText, also uncomment exit(0) to avoid messy output. 
		//print '$aGetText: ';		var_dump($aGetText);
		//exit(0);  
	}
	
	return;
}

/*function getSubArg() is used when you need to check if an argument passed by the
user has a sub-argument as well. For instance, in the --bilingual option, you can 
check if the "= SEPARATOR" sub-argument was passed.  
Parameters: 
&$cntArgs is a reference to the argument count variable
&$subArgVar the variable which will contain the sub-argument
$sLongArg is the long name of the argument. Ex: "--bilingual"
$sShortArg is the short name of the argument. Ex: "-b"
$sSubArgName is the name of the subargument. Ex: "SEPARADOR"
$sSubArgFlag is the string which identifies a subargument. Ex: "="
$iErrorNo is the error number for the program to return if the subArgument 
wasn't given correctly. Example function call: 
getSubArg($CntArgs, $sBilingualSeparator, "--bilingual", "-b", "SEPARADOR", "=", 24); */				
function getSubArg(&$cntArgs, &$subArgVar, $sLongArg, $sShortArg, $sSubArgName, 
			$sSubArgFlag, $iErrorNo)
{
	global $argv, $argc;
	$sArg = $argv[$cntArgs];
	
	if (!strncasecmp($sArg, $sShortArg, 2))
		$sArg = substr($argv[$cntArgs], 2); 
	else
		$sArg = substr($argv[$cntArgs], strlen($sLongArg));
	
	//if substr goes past the end of the string, then returns false 				
	if (is_string($sArg) && strlen($sArg) > 0) //if the string continues 
	{
		if ($sArg[0] != $sSubArgFlag)
		{
			if (ctype_alnum($sArg[0]))
				shutdown(sprintf(__("Error: \"%s\" no es una opción valida."), $sArg) 
					. getHelp(), $iErrorNo);
			else
				shutdown(sprintf(__(
					"Error: Necesita usar \"%s\" para especificar el %s de la opción %s\n\n"), 
					$sSubArgFlag, $sSubArgName, $sLongArg) . getHelp(), $iErrorNo);	
		}			
		elseif (strlen($sArg) == 1)
			$subArgVar = $argv[++$cntArgs];
		else
			$subArgVar = trim(substr($sArg, 1), '"');
	}
	//If end of the arguments or if didn't specify a subargument for the option.
	//If the last argument then probably an error but allow user to put 
	//optiones after the filenames like: instrans.php qu-BO.po es-ES.po -b
	elseif ($argc <= $cntArgs + 1 || $argv[$cntArgs + 1][0] != $sSubArgFlag)
		return;
	//if did specify a subargument, then get it
	elseif (strlen($argv[++$cntArgs]) > 1)
		$subArgVar = trim(substr($argv[$cntArgs], 1), '"');
	elseif ($argc <= $cntArgs + 1)
		return;
	else
		$subArgVar = $argv[++$cntArgs];
		
	return;
}

	
//Function findAutoComMatch() searches through the array aTransPo for a PO
//Object with an Automatic Comment of $sSearchAutoCom. This is useful when 
//looking to match Menus with Status Bar PO objects. Returns -1 if not found,
//or the position in the aTransPo where it was found. 
function findAutoComMatch($sSearchAutoCom)
{
	global $aTransPo; 
	
	$lenTransPo = count($aTransPo);
	$cntTransPo = 0;
	
	//Loop to search through the aTransPo array
	for ($cntTransPo = 0; $cntTransPo < $lenTransPo; $cntTransPo++)
	{
		//3rd parameter (true) so that it will do strict matching, so NULL won't equal ''.
		if (is_array($aTransPo[$cntTransPo]->aAutoCom) &&
				in_array($sSearchAutoCom, $aTransPo[$cntTransPo]->aAutoCom, true))
		{
			return $cntTransPo;
		}
	}
	
	return -1;
}  

//Function findPoMatch() searches through the array aTransPo for a PO object
//that matches oPo and returns the position in the array where it was found
//Returns -1 if not found.
//iSearchFirst is the first position in the array aTransPo to search.  If 
//match not found at position iSearchFirst, then will search sequentially 
//through the entire array for a match. 

//Note:
//In most cases, rOrigPoFile and rTransPoFile will have the exact same order
//of PO frases, so match will be found at iSearchFirst. If the two files
//don"t have the same order, then this program will run very slow, because
//have to search sequentially through the entire file.Maybe I should sort 
//aTransPo for faster searching in a later revision of this code.
function findPoMatch(PoObj $oPo, $iSearchFirst = 0)
{
	global $aTransPo, $bSearch, $aIndexTransPo;
	
	if ($aTransPo === NULL || $aTransPo[0] === NULL)
		return -1;
	
	//check that $iSearchFirst is within bounds
	if (!is_int($iSearchFirst) || $iSearchFirst < 0 || count($aTransPo) <= $iSearchFirst)
		$iSearchFirst = 0;	
	
	if ($aTransPo[$iSearchFirst]->match($oPo) == 4)
		return $iSearchFirst;
		
	$pos = srchIndexTransPo($oPo->strMsgid());
	
	if ($pos === false)
		return -1;
	
	$sIndexVal = substr($oPo->strMsgid(), 0, INDEX_TRANS_PO_LEN);
	
	$lastMatchPos = null; 
	$lastMatchVal = 0;
	$endIdx = count($aIndexTransPo);
	
	for (; $pos < $endIdx && $sIndexVal == $aIndexTransPo[$pos][0]; $pos++)
	{	
		$curMatchVal = $aTransPo[($aIndexTransPo[$pos][1])]->match($oPo);
			
		//only match the msgid in --search mode but try to match the msgid, 
		//automatic comments and reference in normal mode
		if ($curMatchVal == 4 || ($bSearch && $curMatchVal > 0))
			return $aIndexTransPo[$pos][1];
		elseif ($lastMatchVal < $curMatchVal)
		{
			$lastMatchVal = $curMatchVal;
			$lastMatchPos = $aIndexTransPo[$pos][1];
		}
	}
	
	if ($lastMatchVal > 0)	//if some match was found
		return $lastMatchPos;
	else
		return -1;
}
		
//print function which checks whether in verbose and log-to-file mode
//$sMsg is the string to print to screen or write to the log file
//$Set bAlways to true if you want to make sure that $sMsg gets outputted
function output($sMsg, $bAlways = false)
{
	global $bLog, $bVerbose, $sLogFile, $rLogFile, $sCharSet;
	
	if ($bLog)
	{
		if(fputs($rLogFile, $sMsg, 3000) === false)
			shutdown(sprintf(__("Error escribiendo al archivo log \"%s\"."), 
				$sLogFile), 20);
	}
	elseif ($bVerbose || $bAlways)
		print $sCharSet == 'UTF-8' ? $sMsg : iconv('UTF-8', $sCharSet, $sMsg);
			
	return;
}
		

//Function to shutdown the program and delete temporary files if necessary
function shutdown($sMessage = '', $iRetVal = 0)
{
	global $bLog, $rLogFile, $rTempFile, $sTempFile, $sCharSet, 
		$bDebug, $bDOS, $sProgramPath, $rTempDir, $sTempDir;
	
	if ($bLog && is_resource($rLogFile))
		fputs($rLogFile, "\n" . $sMessage . "\n");
	else	
		print "\n" . iconv("UTF-8", $sCharSet, $sMessage) . "\n";
	
	//get rid of temporary file if exists
	if (isset($sTempFile) && file_exists($sTempFile) && !$bDebug) 
	{
		if (is_resource($rTempFile))
			fclose($rTempFile);
			
		//Can"t figure out the setting in php.ini to allow the unlink command to work.
		@unlink($sTempFile);
	}
		
	//get rid of temporary directory if exists
	if (isset($sTempDir) && file_exists($sTempDir) && !$bDebug) 
	{
		if (is_resource($rTempDir))
			closedir($rTempDir);
			
		chdir($sProgramPath); //in case instrans is inside the temporary directory
	
		if ($bDOS)
			@system('rmdir /s /q ' . $sTempDir);
		else 	//if Linux/UNIX/OS X
			@system('rm -r -d ' . $sTempDir); 			
	}

	exit($iRetVal);
}	

//Pass through the TransPoFile and pull out PO Objects and put them in the 
//aTransPo array. If in bSearch mode,then print report at end.
function processTransPoFile($sTransPoFile)
{
	global $iLastElement, $bVerbose, $bSearch, $sErr, $bTransDir, $aTransPo, 
		$totTransPoFileLines, $totTransPoObjs, $totTransPoSyntaxErr,
		$cntTransPoFileLines, $cntTransPoObjs, $cntTransPoSyntaxErr;
		
	//set counters to start position
	$cntTransPoFileLines = $cntTransPoSyntaxErr = 0;
	
	if ($bSearch)
		$cntTransPoObjs = $totTransPoObjs; //adding to the existing $aTransPo array
	else
		$cntTransPoObjs = 0;	

	$rTransPoFile = fopen($sTransPoFile, 'rt');
	
	if ($rTransPoFile === false)
		shutdown(sprintf(__("Error: No puede abrir el archivo \"%s\"."), $sTransPoFile), 5);
	
	//if not in Search Mode (--search), then clear the old array of PO objects read 
	//from the lasttime processTransPoFile was called.
	if (!$bSearch)
		$aTransPo = null; 
		
	if ($aTransPo !== null)
		$bCreateNewObj = true;	//create a new PO object when encountering the first non-blank line
	else	//if array is empty, then create the first array element.
	{
		$aTransPo[] = new PoObj(); 
		$bCreateNewObj = false;
	}
		
	//loop to pull one line at a time out of file rTransPoFile and then put
	//the lines into array aTransPo.
	while(true)
	{
		$sLine = fgets($rTransPoFile);
	
		if ($sLine === false)
		{
			if (feof($rTransPoFile))
				break;
			else
				shutdown(sprintf(__("Error leyendo archivo \"%s\"."), $sTransPoFile), 7);
		}
	
		$cntTransPoFileLines++;
		
		if (trim($sLine) === '')
		{
			$bCreateNewObj = true;
			$iLastElement = SYNTAX_WHITESPACE; //set this so PoObj::addLine() knows to restart PO order
			continue;
		}
		
		if ($bCreateNewObj === true)
		{
			//checks whether the PO object has both a msgid and msgstr defined
			if (!is_array($aTransPo[$cntTransPoObjs]->aMsgstr) && 
				is_array($aTransPo[$cntTransPoObjs]->aMsgid) != 
				is_array($aTransPo[$cntTransPoObjs]->aMsgstr))
			{
				$cntTransPoSyntaxErr++;
				$sOut = sprintf(__(
					"Error de sintaxis en línea %d del archivo \"%s\"\n" .
					"No hay un msgstr definido para el msgid \"%s\"\n\n"), 
					$cntTransPoFileLines - 1, $sTransPoFile, 
					implode('', $aTransPo[$cntTransPoObjs]->aMsgid));
				output($sOut);
			}
			
			$bCreateNewObj = false;
			$aTransPo[] = new PoObj(); 
			$cntTransPoObjs++;
		}
	
		//addLine() returns SYNTAX_ERR_ELEM_ORDER or a smaller negative number if a syntax error
		if ($aTransPo[$cntTransPoObjs]->addLine($sLine) <= SYNTAX_ERR_ELEM_ORDER) //syntax_err_elem_order = -2
		{
			$cntTransPoSyntaxErr++;
			output(sprintf(__(
				"Error de sintaxis en línea %d del archivo \"%s\"\n%s\nLínea: %s\n\n"), 
				$cntTransPoFileLines, $sTransPoFile, $sErr, rtrim($sLine)));
		}
	}
	
	$realCnt = $bSearch ? ($cntTransPoObjs - $totTransPoObjs) : $cntTransPoObjs;
	
		
	//if the first transPo Object was never filled
	if($realCnt == 0 && $aTransPo[cntTransPoObjs]->aMsgid === null)
	{
		if ($bTransDir)
			output(sprintf(__("Aviso: Archivo \"%s\" no contiene ningunos objetos PO.\n"),
				$sTransPoFile), true);
		else	//if only processing 1 file, shutdown because can't do anything w/o PO objects
			shutdown(sprintf(__("Error: Archivo \"%s\" no contiene ningunos objetos PO."), 
				$sTransPoFile), 8);
	}
	elseif ($bSearch && $bVerbose)
	{ 
		output(sprintf(__(
			"%d líneas, %d objetos PO y %d errores de sintaxis leido del archivo \"%s\"\n\n"),
			$cntTransPoFileLines, $realCnt, $cntTransPoSyntaxErr, $sTransPoFile));
	}
	
	if (!$bSearch || !$bTransDir)	//if $bSearch, then make the index when done with all the TransPo files.
		makeIndexTransPo();
	
	//add to total counters:
	$totTransPoFileLines += $cntTransPoFileLines;
	$totTransPoSyntaxErr += $cntTransPoSyntaxErr;
	$totTransPoObjs = $bSearch ?  $cntTransPoObjs : ($totTransPoObjs + $cntTransPoObjs);
	
	$iLastElement = 0; //reset to zero for the next file
	fclose($rTransPoFile);
	return $cntTransPoObjs;
}

//Function to process a single Original PO. Goes throught the file and pulls out
//each PO object, then searches to see whether it exists in the transPo file
//$sNewPoFile is the name of the new PO file to be written--if a dirctory, then this 
//is in a temporary directory
//$sFinalPoFile is the name that the new PO file will have when program finishes and
//renames the temporary directory to $sNewPo 
function processOrigPoFile($sOrigPoFile, $sTransPoFile, $sNewPoFile, $sFinalPoFile)
{
	global $sTempFile,
	$totAmpersandStrips, $totStrips, $totReplaces, $totOrigPoFileLines,  
	$totNewPoFileLines, $totOrigPoObjs, $totNewPoObjs, 
	$totOrigPoSyntaxErr, $totNewPoSyntaxErr, //$totFraseInserts, //not implemented yet
	//variables set by getArgs():
	$bMsgstrIns, $bCommentIns, $bOverwrite, $bVerbose, $bQuiet, $bRecursive, 
	$bNoStripVar, $bBilingualIns, $sBilingualSeparator, $bStatusBarIns,
	$sMenuSrch, $sStatusBarSrch, $sLangComment, $bOrigDir, $bSearch, 
	$bStrip, $sStripStr, $bReplace, $bReplaceFind,
	//variables set by ::strip(), ::replace, & ::getLine():	
	$sErr, $cntStrips, $cntReplaces, 
	//variables set by processTransPoFile():
	$aTransPo, $cntTransPoFileLines, $cntTransPoObjs, $cntTransPoSyntaxErr;
	

	$funcTime = microtime(true); //start time so can calculate time to process file
	
	//Set counters to 0. These will be added to the global counters when done processing the file
	$cntStrips = $totStrips; 
	$cntReplaces = $totReplaces;
	$cntOrigPoFileLines = 0;
	$cntNewPoFileLines = 0;
	$cntOrigPoObjs = 0;
	$cntNewPoObjs = 0;
	$cntOrigPoSyntaxErr = 0; 
	$cntNewPoSyntaxErr = 0;
	//$cntFraseInserts = 0;		//not implemented yet

	//Open the original PO file for reading
	$rOrigPoFile = fopen($sOrigPoFile, 'rt');
	
	if ($rOrigPoFile === false)
		shutdown(sprintf(__("Error: No puede abrir archivo \"%s\" para leerlo."),
			$sOrigPoFile), 9);
	
	//Open a temporary file for writing the new PO file	
	$sTempFile = tempnam(getcwd(), 'instrans_temp_');
	
	if($sTempFile === false)
		shutdown(__("Error: No puede crear un archivo temporario."), 10);
	
	$rTempFile = fopen($sTempFile,'wt'); 
	
	if ($rTempFile === false)	
		shutdown(__("Error: No puede abrir un archivo temporario para escribirlo."), 11);
		
	$curPo = new PoObj(); //holds current PO object pulled from the original PO file
	 
	//Loop to extract each PO object from the original PO file and
	//put it in curPo. Then searches for a matching PO object 
	//in array aTransPo (which contains the PO objects found in rTransPoFile).
	//Then copies the translation from aTransPO and insert it into curPo.
	do
	{
		$sLine = fgets($rOrigPoFile);
		
		if ($sLine === false && !feof($rOrigPoFile))
			shutdown(sprintf(__("Error leyendo archivo \"%s\"."), $sOrigPoFile), 12);
		else
			$cntOrigPoFileLines++;
	
		$retVal = $curPo->addLine($sLine);
	
		if ($retVal <= -2)
		{
			$cntOrigPoSyntaxErr++;
			
			if ($sOrigPoFile == $sNewPoFile)
				$sOut = sprintf(__(
					"Error de sintaxis en línea %d del archivo \"%s\"\n%s\nLínea: %s\n\n"), 
					$cntNewPoFileLines + $curPo->cntLines(), $sOrigPoFile, $sErr, rtrim($sLine));
			else 
				$sOut = sprintf(__("Error de sintaxis en línea %d del archivo \"%s\"\n" .
					"%s\nLínea: %s\nChequee línea %d del archivo \"%s\".\n\n"), 
					$cntOrigPoFileLines, $sOrigPoFile, $sErr, rtrim($sLine), 
					$cntNewPoFileLines + $curPo->cntLines(), $sFinalPoFile);
			
			output($sOut);
		}
					
		if ($retVal === 0 || feof($rOrigPoFile)) //if the end of the PO Object
		{
			if ($curPo->aMsgid === null)
			{
				$linesIns = $curPo->cntLines();
				if ($linesIns > 0)
				{
					if (fputs($rTempFile, $curPo->getStr(), $linesIns * 256 ) === false)
						shutdown(sprintf(__("Error escribiendo al archivo temporario \"%s\"."),
							$sTempFile), 15);
						
					$cntNewPoFileLines += $linesIns;
				}
				
				if (feof($rOrigPoFile))
					break;
				else
					continue;
			}
			
			//checks whether the PO object has both a msgid and msgstr defined
			if (is_array($curPo->aMsgid) != is_array($curPo->aMsgid) && !is_array($curPo->aMsgid))
			{
				$cntOrigPoSyntaxErr++;
						
				if ($sOrigPoFile === $sNewPoFile)
					$sOut .= sprintf(__("Error de sintaxis en línea %d del archivo \"%s\"\n" .
						"No hay un msgstr definido para el msgid \"%s\"\n\n"), 
						$cntNewPoFileLines + $curPo->cntLines(), $sOrigPoFile, 
						implode('', $curPo->aMsgid));
				else 
					$sOut = sprintf(__("Error de sintaxis en línea %d de archivo \"%s\"\n" . 
						"No hay un msgstr definido para el msgid \"%s\"\n" . 
						"Chequee línea %d del archivo \"%d\".\n\n"), 
						$cntOrigPoFileLines - 1, $sOrigPoFile, implode('', $curPo->aMsgid), 
						$cntNewPoFileLines + $curPo->cntLines(), $sFinalPoFile);
				
				output($sOut);	
			}
			
			$cntOrigPoObjs++;
			
			if ($bStatusBarIns)
			{
				if (is_array($curPo->aAutoCom))
				{
					$retVal = myStrPos($curPo->aAutoCom, $sStatusBarSrch);
				
					//if Automatic Comment contains the Status Bar identifier
					if ($retVal !== false) 
					{
						$posPo = findAutoComMatch(str_replace($sStatusBarSrch, 
							$sMenuSrch, $curPo->aAutoCom[$retVal[0]]));
					}
					else
						$posPo = -1; // signal that there was no match found
				}
				else $posPo = -1;
			}
			else 
				$posPo = findPoMatch($curPo, $bSearch ? 0 : $cntOrigPoObjs);
					
	
			if ($posPo >= 0) //if match found
			{
				if ($bCommentIns)	//if inserting translation as a comment
				{
					$aNew = null;
					
					if (is_array($aTransPo[$posPo]->aMsgstr))
					{
						foreach($aTransPo[$posPo]->aMsgstr as $st)
							$aNew[] = '[' . $sLangComment . 
								($bStatusBarIns ? '] MENU: "' : '] "') . $st . '"';
					}
	
					if ($bOverwrite)
					{
						if (is_array($curPo->aTransCom))
						{
							foreach($curPo->aTransCom as $st)
							{
								//only keep comments that don't match the form:
								// [FILENAME] "TRANSLATION" or 
								// [FILENAME] MENU: "TRANSLATION if bStatusBarIns 
								if (!ereg($bStatusBarIns ? "^\[.+\] MENU: \".*\"$" : 
										"^\[.+\] \".*\"$", $st)) 
									$aNew[] = $st;
							}
						}
					}
					else	//if not overwriting comments
					{
						if (is_array($curPo->aTransCom))
							foreach($curPo->aTransCom as $st)
								$aNew[] = $st;
					}
					
					$curPo->aTransCom = $aNew;
				}
				
				if($bBilingualIns) 
				{
					if (is_array($aTransPo[$posPo]->aMsgstr) && $aTransPo[$posPo]->strMsgstr() != '')
					{
						$cntArray = count($curPo->aMsgstr);
						
						//if curPo doesn't have a msgstr
						if ($cntArray == 0 || ($cntArray == 1 && $curPo->aMsgstr == '')) 
						{
							//clear the msgstr so start autoincrementing from zero
							$curPo->aMsgstr = null; 
							
							foreach($aTransPo[$posPo]->aMsgstr as $st)
								$curPo->aMsgstr[] = $st;
						}
						else // if curPo does have an existing msgstr
						{
							$s1 = $curPo->strMsgstr();
							
							if ($bStrip)
								$aTransPo[$posPo]->strip();
							
							if ($bReplace)
							{	
								$sWarning = $aTransPo[$posPo]->replace();
							
								if ($sWarning !== false)
								{
									$sOut = sprintf(__("Archivo \"%s\" línea %d:\n"), 
										$sFinalPoFile, $cntNewPoFileLines + $curPo->cntLines());
									output($sOut . $sWarning);
								}
							}
							
							//Set the separator between the two msgstr according to 
							//whether the existing msgstr is spaces, has a new line 
							//character, or is a normal msgstr
							if (trim($s1) == '') 				//only space or empty
								$sSeparator = '';	
							elseif (strpos($s1, '\n') === false) //no new line char.
								$sSeparator = $sBilingualSeparator;
							else  				//if contains new line character
								$sSeparator = '\n';
						
							//append to the same line as the existing msgstr
							if ($sSeparator != '\n' && strlen($s1) < 40)
							{
								$curPo->aMsgstr = null; //clear the array
								$curPo->aMsgstr[] = $s1 . $sSeparator . 
									$aTransPo[$posPo]->strMsgstr();
							}
							else //if appending extra lines
							{
								foreach($aTransPo[$posPo]->aMsgstr as $st)
								{
									$curPo->aMsgstr[] = $sSeparator . $st;
									//set to empty string so only insert separator for the first iteration
									$sSeparator = ''; 
								}
							}
						}
					}
				}
				elseif($bMsgstrIns) 	//if inserting translation as the msgstr
				{
					if ($bOverwrite || $curPo->aMsgstr === null || 
						($curPo->aMsgstr[0] === '' && count($curPo->aMsgstr) == 1))
					{	
						$curPo->aMsgstr = null;	
						
						if (is_array($aTransPo[$posPo]->aMsgstr))
						{
							if ($bStatusBarIns)
							{
								if ($bStrip)
									$aTransPo[$posPo]->strip();
								
								if ($bReplace)
								{
									$sWarning = $aTransPo[$posPo]->replace();
								
									if ($sWarning !== false)
										output(sprintf(__("Archivo \"%s\" línea %d:\n"), 
											$sFinalPoFile, $cntNewPoFileLines + 
											$curPo->cntLines()) . $sWarning); 
								}
							}
								
							foreach($aTransPo[$posPo]->aMsgstr as $st)
								$curPo->aMsgstr[] = $st;
						}
					}
				}
			}
			//+1 for extra line of whitespace after each PO object	
			$linesIns = $curPo->cntLines() + 1;
			
			if (fputs($rTempFile, $curPo->getStr(), $linesIns * 256 ) === false)
				shutdown(sprintf(__("Error escribiendo al archivo temporario \"%s\"."), 
					$sTempFile), 15);
			
			//+1 for extra line of whitespace after each PO object	
			$cntNewPoFileLines += $linesIns;	 
			$curPo->clear();	
			$cntNewPoObjs++;		
		}
	} while (!feof($rOrigPoFile));
				
	fclose($rOrigPoFile);
	fclose($rTempFile);
	
	//if the sNewPoFile exists, then delete it.
	if (file_exists($sNewPoFile))
	{
		if (!unlink($sNewPoFile))	
			shutdown(sprintf(__("Error: No puede borrar el archivo \"%s\" para reemplazarlo."),
				$sFinalPoFile), 16);
	}
	
	if (!rename($sTempFile, $sNewPoFile))
		shutdown(__("Error: No puede dar un nuevo nombre a un archivo temporario."), 17);
	
	if (!$bQuiet && $bOrigDir)
	{	
		if ($bVerbose)
		{
			$sOut = sprintf(__("Resumen de \"%s\":\n" .
				"%d línea(s), %d objeto(s) PO y %d error(es) de sintaxis leido del archivo \"%s\"\n"),  
				$sFinalPoFile, $cntOrigPoFileLines, $cntOrigPoObjs, 
				$cntOrigPoSyntaxErr, $sOrigPoFile);
				
			if (!$bSearch)
				$sOut .= sprintf(__(
					"%d línea(s), %d objeto(s) PO y %d error(es) de sintaxis leido del archivo \"%s\"\n"),
					$cntTransPoFileLines, $cntTransPoObjs, $cntTransPoSyntaxErr, $sTransPoFile);
			
			$sOut .= sprintf(__("%d línea(s) y %d objeto(s) PO escrito en el archivo \"%s\"\n"), 
				$cntNewPoFileLines, $cntNewPoObjs, $sFinalPoFile);
			
			if ($bStrip)
			{
				if ($sStripStr !== null)
					if (substr(phpversion(), 0, 3) >= 5.1)
						$sOut .= sprintf(__("%d cadena(s) quitado\n"), $cntStrips);
					else
						$sOut .= sprintf(__("Cadena(s) quitado en %d msgstrs\n"), $cntStrips);
				else	// if striping ampersands
					if (substr(phpversion(), 0, 3) >= 5.1)
						$sOut .= sprintf(__("%d signos \"&\" quitado\n"), $cntStrips);
					else
						$sOut .= sprintf(__("Signo(s) \"&\" quitado en %d msgstrs\n"), 
							$cntStrips);
			}
			
			if ($bReplace)
			{
				if ($sReplaceFind !== null)
					if (substr(phpversion(), 0, 3) >= 5.1)
						$sOut .= sprintf(__("%d reemplazos\n"), $cntReplaces);
					else
						$sOut .= sprintf(__("Reemplazos en %d msgstrs\n"), $cntReplaces);
				else	//if replacing variables
					if (substr(phpversion(), 0, 3) >= 5.1)
						$sOut .= sprintf(__("%d variables reemplazadas\n"), $cntReplaces);
					else
						$sOut .= sprintf(__("Variables reemplazadas en %d msgstrs\n"), 
							$cntReplaces);
			}		
				
			$sOut .= sprintf(__("Tiempo de procesamiento: %f segundos.\n\n"), 
				microtime(true) - $funcTime);
			output($sOut);
		}
		else
		{
			$sOut = sprintf(__("%d objetos PO escrito en el archivo \"%s\"\n"), 
				$cntNewPoObjs, $sFinalPoFile);
			output($sOut, true);
		}
	}
	
	//Add to the global counters:
	$totReplaces += $cntReplaces; 
	$totStrips += $cntStrips;
	$totOrigPoFileLines += $cntOrigPoFileLines;
	$totNewPoFileLines += $cntNewPoFileLines;
	$totOrigPoObjs += $cntOrigPoObjs;
	$totNewPoObjs += $cntNewPoObjs;
	$totOrigPoSyntaxErr += $cntOrigPoSyntaxErr;
	$totNewPoSyntaxErr += $cntNewPoSyntaxErr;
	//$totFraseInserts += $cntFraseInserts; //not implemented yet
	
	return 0;
}

//A recursive function for --search mode to go through all the files in 
//TRANSLATIONS-PO directory and fill the aTransPo array with every Po object found.
function processTransPoDir($sTransPoDir)
{
	global $bRecursive, $bDOS;
	
	$sOldCWD = getcwd();
	$rTransPoDir = opendir($sTransPoDir);
	chdir($sTransPoDir); //readdir() returns only filenames, so need to change working dir
	
	if (!$rTransPoDir)
		shutdown(sprintf(__("Error: No puede abrir el directorio \"%s\"."), 
			$sTransPoDir), 20);
		
	while (true)
	{
		$sNextInDir = readdir($rTransPoDir);
		 
		if ($sNextInDir === false)
			break;
		elseif ($sNextInDir == '.' || $sNextInDir == '..')
			continue;
		elseif (is_dir($sNextInDir))
		{
			if ($bRecursive)
			{	
				processTransPoDir($sTransPoDir . ($bDOS ? '\\' : '/') . $sNextInDir);		
			}
		}	
		elseif (isPoFile($sNextInDir))
		{
			processTransPoFile($sTransPoDir . ($bDOS ? '\\' : '/') . $sNextInDir);
		}	
	}
	
	chdir($sOldCWD);
	closedir($rTransPoDir);
	return 0;
}			

//function that can be called recursively to process a directory.  It copies files
//that aren't PO files to the new directory $sNewPoDir and calls the function 
//processPoFile() when it encounters a PO file.  If it encounters another directory
//and the --recursive option is set, then it calls itself to process that directory.
function processDir($sOrigPoDir, $sTransPoDir, $sTempPoDir, $sNewPoDir)
{
	global $bRecursive, $bDOS, $bSearch;
	
	$cSlash = $bDOS ? '\\' : '/';
	$sOldCWD = getcwd();
	$rOrigPoDir = opendir($sOrigPoDir);
	chdir($sOrigPoDir); //readdir() returns only filenames, so need to change working dir
	
	if (!$rOrigPoDir)
		shutdown(sprintf(__("Error: No puede abrir el directorio \"%s\"."), 
			$sOrigPoDir), 20);
		
	while (true)
	{
		$sNextInDir = readdir($rOrigPoDir);
		 
		if ($sNextInDir === false)
			break;
		elseif ($sNextInDir == '.' || $sNextInDir == '..')
			continue;
		elseif (is_dir($sNextInDir))
		{
			if ($bRecursive)
			{	
				if (!mkdir($sTempPoDir . $cSlash . $sNextInDir))
					shutdown(sprintf(__("Error: No puede crear directorio \"%s\"."),
						$sNewPoDir . $cSlash . $sNextInDir), 22);
				
				processDir($sOrigPoDir . $cSlash . $sNextInDir, $sTransPoDir . $cSlash . $sNextInDir, 
					$sTempPoDir . $cSlash . $sNextInDir, $sNewPoDir . $cSlash . $sNextInDir);		
			}
		}	
		elseif (isPoFile($sNextInDir))
		{
			if (file_exists($sTransPoDir . $cSlash . $sNextInDir) || $bSearch)
			{
				//if in --search mode, then have already processed the TransPoFile
				if (!$bSearch)
					processTransPoFile($sTransPoDir . $cSlash . $sNextInDir);
					
				processOrigPoFile($sOrigPoDir . $cSlash . $sNextInDir, $sTransPoDir . 
					$cSlash . $sNextInDir, $sTempPoDir . $cSlash . $sNextInDir, 
					$sNewPoDir . $cSlash . $sNextInDir);
			}
			else //if a matching file in directory TransPo is not found, then copy just it over to the new directory
			{
				if (!copy($sOrigPoDir . $cSlash . $sNextInDir, $sTempPoDir . $cSlash . $sNextInDir)) 
					shutdown(sprintf(__("Error: No puede copiar archivo \"%s\"."), $sNextInDir), 21);
			}
				
		}	
		else //if not a PO file, then just copy it over to the sNewPoDir
		{	
			if (!copy($sOrigPoDir . $cSlash . $sNextInDir, $sTempPoDir . $cSlash . $sNextInDir)) 
				shutdown(sprintf(__("Error: No puede copiar archivo \"%s\"."), $sNextInDir), 21);
		}	
	}
	
	chdir($sOldCWD);
	closedir($rOrigPoDir);
	return 0;
}

//function to check whether a file is a PO file. Returns true if PO file, otherwise false
function isPoFile($sFile)
{
	$sExt = strtoupper(pathinfo($sFile, PATHINFO_EXTENSION));
	
	if (($sExt == 'PO' || $sExt == 'POT' || $sExt == 'POX') && is_readable($sFile))
	{
		//later write code to examine first 20 lines and check if really a PO file
		return true;
	}
	else
		return false;
}
	
function getHelp()
{
	global $sProgramPath;
	$sHelp = substr(file_get_contents($sProgramPath . '/' . __("help-es.txt")),3);
	
	if ($sHelp === false)
		output(__("Error: No puede abrir el archivo \"help-es.txt\".\n"), true);
		
	return $sHelp;
}	

function makeIndexTransPo()
{
	global $aIndexTransPo, $aTransPo;
	$aIndexTransPo = null; //clear old index
	
	//if $aTransPo is empty, make a blank index, so won't get errors if try to access $aIndexTransPo	
	if (!is_array($aTransPo))
	{
		$aIndexTransPo = array('' => 0);
		return;
	}
	
	$totObjs = count($aTransPo);
	
	for ($cnt = 0; $cnt < $totObjs; $cnt++)
	{
		$aIndexTransPo[$cnt][0] = substr((string)$aTransPo[$cnt]->strMsgid(), 0, 10);
		$aIndexTransPo[$cnt][1] = $cnt;
	}
		
	sort($aIndexTransPo, SORT_REGULAR);
	return;
}

//Searches for string sFind in the index $aIndexTransPo.  If found, returns the first 
//position in $aIndexTransPo where $sFind was found. In not found, returns false.
function srchIndexTransPo($sFind)
{
	global $aIndexTransPo;
	
	if (!is_string($sFind))
		return false;
	
	if (strlen($sFind) > INDEX_TRANS_PO_LEN)
		$sFind = substr($sFind, 0, INDEX_TRANS_PO_LEN);
		
	$begin = 0;
	$end = count($aIndexTransPo) - 1; //there is a least one element in array, so safe to subtract 1
	
	//$mid = int ($end/2); // error, because calls non-existant function int()
	//$mid = (int) $end/2; // error, because associates int with $end, so $mid is assigned a float value.
	$mid = (int) ($end / 2);
	
	//loop to find $sFind in array $aIndexTransPo.
	while (true)
	{
		$cmp = strcmp($sFind, $aIndexTransPo[$mid][0]);
		
		//print "begin =$begin, end = $end, mid =$mid, $cmp = strcmp($sFind, {$aIndexTransPo[$mid][0]})\n";
		
					
		if ($cmp == 0)
		{
			break;
		}
		elseif ($cmp > 0)
		{
			if ($begin == $mid)
			{
				if ($mid == $end)
					break;
				else 
					$mid = $end;
			}
				
			$begin = $mid;
			$mid = $begin + (int)(($end - $begin) / 2);
		}
		else //if $cmp < 0
		{
			if ($end == $mid)
			{
				if ($mid == $begin)
					break;
				else
					$mid = $begin;
			}
			
			$end = $mid;
			$mid = $begin + (int)(($end - $begin) / 2);
		}
	}
	
	//Because there could be more than one occurance of an index item, deincrement until 
	//get to the first occurance of $sfind in index 
	while($mid - 1 != 0 && $sFind == $aIndexTransPo[$mid - 1][0])
		$mid--;
		
	if ($aIndexTransPo[$mid][0] == $sFind)
		return $mid;
	else
		return false;
}

//Returns the perl regular expression search pattern to find variables in code.
//parameter $sFlg is the Flag comment from a PO object. Searches for the language
//specifier in $sFlg. If a language is unsupported, it returns false. If a
//no-LANG-format is found or it isn't a language format, it returns an empty string ('')
//So far I have only implemented a few of the possible languages
function getVarPattern($sFlg)
{	
	$sPattern = '';
	
	/*C format strings are described in POSIX (IEEE P1003.1 2001), section
	XSH 3 fprintf(),
	`http://www.opengroup.org/onlinepubs/007904975/functions/fprintf.html'.
	See also the fprintf(3) manual page,
	`http://www.linuxvalley.it/encyclopedia/ldp/manpage/man3/printf.3.php',
	`http://informatik.fh-wuerzburg.de/student/i510/man/printf.html'.

	   Although format strings with positions that reorder arguments, such
	as
	     "Only %2$d bytes free on '%1$s'."
	which is semantically equivalent to:
	     "'%s' has only %d bytes free."

	are a POSIX/XSI feature and not specified by ISO C 99, translators can
	rely on this reordering ability: On the few platforms where `printf()',
	`fprintf()' etc. don't support this feature natively, `libintl.a' or
	`libintl.so' provides replacement functions, and GNU `<libintl.h>'
	activates these replacement functions automatically.

	As a special feature for Farsi (Persian) and maybe Arabic,
	translators can insert an `I' flag into numeric format directives.  For
	example, the translation of `"%d"' can be `"%Id"'.  The effect of this
	flag, on systems with GNU `libc', is that in the output, the ASCII
	digits are replaced with the `outdigits' defined in the `LC_CTYPE'
	locale facet.  On other systems, the `gettext' function removes this
	flag, so that it has no effect.

	Note that the programmer should _not_ put this flag into the
	untranslated string.  (Putting the `I' format directive flag into an
	MSGID string would lead to undefined behaviour on platforms without
	glibc when NLS is disabled.)	
	
	I have poorly implemented this because C is so bloody complicated*/
	if (strpos($sFlg, 'c-format') !== false)
	{
		if (strpos($sFlg, 'no-c-format') === false)
			$sPattern = '/%([\$+\-#\'.0-9IlLh]*[aAbcCdieEgGfmuosSpxX])/';
	}
	/*Objective C format strings are like C format strings. They support an additional 
	format directive: "$@",which when executed consumes an argument of type `Object *'.
	I don't have a clue whether they mean %$@s or just $@ alone, so I won't implement it. */
	elseif (strpos($sFlg, 'objc-format') !== false)
	{
		if (strpos($sFlg, 'no-obj-format') === false)
		
			$sPattern = '/%([$+\-#\'.0-9lLh]*[aAbcCdieEgGfmuosSpxX])/';
	}
	/*Shell format strings, as supported by GNU gettext and the `envsubst'
	program, are strings with references to shell variables in the form
	`$VARIABLE' or `${VARIABLE}'.  References of the form
	`${VARIABLE-DEFAULT}', `${VARIABLE:-DEFAULT}', `${VARIABLE=DEFAULT}',
	`${VARIABLE:=DEFAULT}', `${VARIABLE+REPLACEMENT}',
	`${VARIABLE:+REPLACEMENT}', `${VARIABLE?IGNORED}',
	`${VARIABLE:?IGNORED}', that would be valid inside shell scripts, are
	not supported.  The VARIABLE names must consist solely of alphanumeric
	or underscore ASCII characters, not start with a digit and be nonempty;
	otherwise such a variable reference is ignored.*/
	elseif (strpos($sFlg, 'sh-format') !== false)
	{
		if (strpos($sFlg, 'no-sh-format') === false)
			$sPattern = '/\$(\{?\w+\}?)/';
	}
	/*Python format strings are described in Python Library reference /
	2. Built-in Types, Exceptions and Functions / 2.2. Built-in Types /
	2.2.6. Sequence Types / 2.2.6.2. String Formatting Operations.
	`http://www.python.org/doc/2.2.1/lib/typesseq-strings.html'.	*/
	elseif (strpos($sFlg, 'python-format') !== false)
	{
		if (strpos($sFlg, 'no-python-format') === false)
			$sPattern = false;
	}
	/*Lisp format strings are described in the Common Lisp HyperSpec, chapter
	22.3 Formatted Output,
	`http://www.lisp.org/HyperSpec/Body/sec_22-3.html'.	*/
	elseif (strpos($sFlg, 'lisp-format') !== false)
	{
		if (strpos($sFlg, 'no-lisp-format') === false)
			$sPattern = false;
	}
	/*Emacs Lisp format strings are documented in the Emacs Lisp reference,
	section Formatting Strings,
	`http://www.gnu.org/manual/elisp-manual-21-2.8/html_chapter/elisp_4.html#SEC75'\
	Note that as of version 21, XEmacs supports numbered argument
	specifications in format strings while FSF Emacs doesn't.	*/
	elseif (strpos($sFlg, 'elisp-format') !== false)
	{
		if (strpos($sFlg, 'no-elisp-format') === false)
			$sPattern = false;
	}
	/*librep format strings are documented in the librep manual, section
	Formatted Output,
	`http://librep.sourceforge.net/librep-manual.html#Formatted%20Output',
	`http://www.gwinnup.org/research/docs/librep.html#SEC122'.	*/
	elseif (strpos($sFlg, 'librep-format') !== false)
	{
		if (strpos($sFlg, 'no-librep-format') === false)
			$sPattern = false;
	}
	/*Scheme format strings are documented in the SLIB manual, section
	Format Specification.	*/
	elseif (strpos($sFlg, 'scheme-format') !== false)
	{
		if (strpos($sFlg, 'no-scheme-format') === false)
			$sPattern = false;
	}
	/*Smalltalk format strings are described in the GNU Smalltalk
	documentation, class `CharArray', methods `bindWith:' and
	`bindWithArguments:'.
	`http://www.gnu.org/software/smalltalk/gst-manual/gst_68.html#SEC238'.
	In summary, a directive starts with `%' and is followed by `%' or a
	nonzero digit (`1' to `9').	*/
	elseif (strpos($sFlg, 'smalltalk-format') !== false)
	{
		if (strpos($sFlg, 'no-smalltalk-format') === false)
			$sPattern = '/%([%1-9])/';
	}
	/*Java format strings are described in the JDK documentation for class
	`java.text.MessageFormat',
	`http://java.sun.com/j2se/1.4/docs/api/java/text/MessageFormat.html'.
	See also the ICU documentation
	`http://oss.software.ibm.com/icu/apiref/classMessageFormat.html'.	*/
	elseif (strpos($sFlg, 'java-format') !== false)
	{
		if (strpos($sFlg, 'no-java-format') === false)
			$sPattern = false;
	}
	/*C# format strings are described in the .NET documentation for class
	`System.String' and in
	`http://msdn.microsoft.com/library/default.asp?url=/library/en-us/cpguide/html/
	cpConFormattingOverview.asp'.	*/
	elseif (strpos($sFlg, 'csharp-format') !== false)
	{
		if (strpos($sFlg, 'no-csharp-format') === false)
			$sPattern = false;
	}
	/*awk format strings are described in the gawk documentation, section
	Printf, `http://www.gnu.org/manual/gawk/html_node/Printf.html#Printf'.	*/
	elseif (strpos($sFlg, 'awk-format') !== false)
	{
		if (strpos($sFlg, 'no-awk-format') === false)
			$sPattern = false;
	}
	/*Where is this documented?	*/
	elseif (strpos($sFlg, 'object-pascal-format') !== false)
	{
		if (strpos($sFlg, 'no-object-pascal-format') === false)
			$sPattern = false;
	}
	/*YCP sformat strings are described in the libycp documentation
	`file:/usr/share/doc/packages/libycp/YCP-builtins.html'.  In summary, a
	directive starts with `%' and is followed by `%' or a nonzero digit
	(`1' to `9').	*/
	elseif (strpos($sFlg, 'ycp-format') !== false)
	{
		if (strpos($sFlg, 'no-ycp-format') === false)
			$sPattern = '/%([%1-9])/';
	}
	/*Tcl format strings are described in the `format.n' manual page,
	`http://www.scriptics.com/man/tcl8.3/TclCmd/format.htm'.	*/
	elseif (strpos($sFlg, 'tcl-format') !== false)
	{
		if (strpos($sFlg, 'no-tcl-format') === false)
			$sPattern = false;
	}
	/*There are two kinds format strings in Perl: those acceptable to the
	Perl built-in function `printf', labelled as `perl-format', and those
	acceptable to the `libintl-perl' function `__x', labelled as
	`perl-brace-format'.

	   Perl `printf' format strings are described in the `sprintf' section
	of `man perlfunc'.

	   Perl brace format strings are described in the
	`Locale::TextDomain(3pm)' manual page of the CPAN package libintl-perl.
	In brief, Perl format uses placeholders put between braces (`{' and
	`}').  The placeholder must have the syntax of simple identifiers.	*/
	elseif (strpos($sFlg, 'perl-format') !== false)
	{
		if (strpos($sFlg, 'no-perl-format') === false)
			$sPattern = false;
	}
	elseif (strpos($sFlg, 'perl-brace-format') !== false)
	{
		if (strpos($sFlg, 'no-perl-brace-format') === false)
			$sPattern = false;
	}
	/*PHP format strings are described in the documentation of the PHP
	function `sprintf', in `phpdoc/manual/function.sprintf.html' or
	`http://www.php.net/manual/en/function.sprintf.php'.
	swapping: ([0-9]$)?
	sign: [+\-]? 
	padding: ('\S)?
	alignment: \-?
	width: [0-9]*
	precision: (\.[0-9]+)?
	type:[bcdeufFosxX]	*/
	elseif (strpos($sFlg, 'php-format') !== false)
	{
		if (strpos($sFlg, 'no-php-format') === false)
			$sPattern = "/%(([0-9]\$)?[+\-]?('\S)?\-?[\.0-9]*[bcdeufFosxX])/";
	}
	/*These format strings are used inside the GCC sources.  In such a format
	string, a directive starts with `%', is optionally followed by a size
	specifier `l', an optional flag `+', another optional flag `#', and is
	finished by a specifier: `%' denotes a literal percent sign, `c'
	denotes a character, `s' denotes a string, `i' and `d' denote an
	integer, `o', `u', `x' denote an unsigned integer, `.*s' denotes a
	string preceded by a width specification, `H' denotes a `location_t *'
	pointer, `D' denotes a general declaration, `F' denotes a function
	declaration, `T' denotes a type, `A' denotes a function argument, `C'
	denotes a tree code, `E' denotes an expression, `L' denotes a
	programming language, `O' denotes a binary operator, `P' denotes a
	function parameter, `Q' denotes an assignment operator, `V' denotes a
	const/volatile qualifier.	*/
	elseif (strpos($sFlg, 'gcc-internal-format') !== false)
	{
		if (strpos($sFlg, 'no-gcc-internal-format') === false)
			$sPattern = '/%([l+#.0-9]*[csidouxHDFTACELOPQV])/';
	}
	/*Qt format strings are described in the documentation of the QString
	class `file:/usr/lib/qt-3.0.5/doc/html/qstring.html'.  In summary, a
	directive consists of a `%' followed by a digit. The same directive
	cannot occur more than once in a format string.	*/
	elseif (strpos($sFlg, 'qt-format') !== false)
	{
		if (strpos($sFlg, 'no-qt-format') === false)
			$sPattern = '/%([0-9])/';
	}
	
	return $sPattern;
}

//Transforms a string into a string that can be used in a Perl regular expression
//for searches. Checks to see whether already a Perl string, before transforming it.
function perlStr($str)
{
	if (!is_string($str))
		return false;
	//check to see if already a perl regular expression
	elseif (strlen($str) > 0 && ereg('^[[:space:]]*/.*/[igmsxUX]*[[:space:]]*$', $str))
		return $str;
		
	return '/' . preg_quote($str) . '/';
}
	
		
?>
