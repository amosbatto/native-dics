#!/usr/bin/env python3
'''
convertChipayaDic is a python script to convert the Chipaya dictionary from CSV format 
into four .tab files (Chipaya, Spanish, Aymara and English), which are used to 
create StarDict dictionaries and can be used to import the dictionary into NuSimi 
with SimiDic-Builder. 

USE:   
python3 convertChipayaDic.py DICTIONARY.csv [NEW-DICTIONARY.tab]

OPTIONS:
-d CHAR, --delimiter CHAR
           The character separating fields in the CSV file. By default, set to tab.

-q CHAR, --quote-char CHAR
           The character used to quote text in fields in the CSV file. By default 
           set to " (double quotation mark).

WHERE:
DICTIONARY.csv     The Chipaya dictionary in CVS format, with tabs as field delimiters 
                   and strings with new lines enclosed in "..."
                
NEW-DICTIONARY.tab The new tab file generated by the script. If not specified
                   then it will use the same filename as the CVS file in the same
                   directory, but with the extension of ".tab".

Autor: Amos Batto <amosbatto@yahoo.com>
Versión: 0.1 (2022-09-04)
Licencia: GPL 3.0 o después
'''
 
import shutil, os, os.path, sys, argparse, pathlib, csv, re
 
def main ():
	
	parser = argparse.ArgumentParser(
		description = """
		dicChipaya is a python script to convert the Chipaya dictionary from CSV format 
		into a .tab file, which is used to create a StarDict dictionary and can be used 
		to import the dictionary into NuSimi with SimiDic-Builder. 
		"""
	)
	parser.add_argument(
		"-d", "--delimiter",
		default = "\t",
		help = "The character separating fields in the CSV file. By default, set to tab.",
	)
	parser.add_argument(
		"-q", "--quote-char",
		default = '"',
		help = 'The character used to quote text in fields in the CSV file. '\
			'By default set to " (double quotation mark).'
	)
	parser.add_argument(
		"DICTIONARY",
		type  = pathlib.Path,
		help = "The Chipaya dictionary in CSV format, with tabs as field delimiters "\
			"and strings with new lines enclosed in \"...\""
	)
	parser.add_argument(
		'NEW_DICTIONARY',
		nargs = '?', 
		type  = pathlib.Path,
		help  = """Optional. The filename of new tab file generated by the script with '_cap-es.tab',
				'_es-cap.tab', '_ay-cap.tab' and '_en-cap.tab' added to end. If not specified,
				then it will use the same basename as the CVS file with those extensions added."""
	)
	 
	args = parser.parse_args()
	
	csvFileName = str(args.DICTIONARY)
	# Counts the number of entries in the dictionary
	entriesCAP = entriesES = entriesAY = entriesEN = 0 
	
	if not os.path.exists(csvFileName):
		print(f"CSV file '{csvFileName}' doesn't exist or doesn't have permission to open.")
		parser.print_help() 
		exit(1);
	
	if not args.NEW_DICTIONARY:
		newFileName = csvFileName[ : -len(pathlib.Path(csvFileName).suffix) ] + ".tab"
	else:
		newFileName = str(args.NEW_DICTIONARY)
		
	
	chipayaAlphabet = ['', 'A', 'C', "C'", 'CH', "CH'", "c̈H", "c̈H'", "E", "I", "J", "K",
		"K'", "L", "LL", "M", "N", "Ñ", "O", "P", "P'", "Q", "Q'", "R", "S", "T",  "T'", "TS",
		"TS'", "U", "W", "Y", "Z", "z̈"]
		
	
	#Existing files will be truncated if the script has write permissions:
	tabFileCAP = open(newFileName + "_cap-es.tab", "w")
	tabFileES  = open(newFileName + "_es-cap.tab", "w")
	tabFileAY  = open(newFileName + "_ay-cap.tab", "w")
	tabFileEN  = open(newFileName + "_en-cap.tab", "w")
	
	with open(csvFileName, "r") as csvFile:
		csvReader = csv.DictReader(csvFile, delimiter=args.delimiter, quotechar=args.quote_char, 
			fieldnames=('chipaya', 'aymara', 'castellano', 'english'))
		
		#skip the first row which is assumed to be a header row
		next(csvReader)
		
		for row in csvReader:
			
			#skip rows whose first field is empty or a letter in the Chipaya Alphabet
			if not row['chipaya'] or row['chipaya'].strip() in chipayaAlphabet:
				continue
			
			chipaya    = stripDef(row['chipaya'])
			aymara     = stripDef(row['aymara'])
			castellano = stripDef(row['castellano'])
			english    = stripDef(row['english'])
			
			entryCAP = f"{chipaya}\t<font color=green>ESP:</font> {castellano}\\n"\
				f"<font color=blue>AYM:</font> {aymara}\\n"\
				f"<font color=orange>ENG:</font> {english}\n"
			tabFileCAP.write(entryCAP)
			entriesCAP += 1
			
			if castellano:
				entryES = f"{castellano}\t<font color=red>CHP:</font> {chipaya}\\n"\
					f"<font color=blue>AYM:</font> {aymara}\\n"\
					f"<font color=orange>ENG:</font> {english}\n"
				tabFileES.write(entryES)
				entriesES += 1
				
			if aymara:
				entryAY = f"{aymara}\t<font color=red>CHP:</font> {chipaya}\\n"\
					f"<font color=green>ESP:</font> {castellano}\\n"\
					f"<font color=orange>ENG:</font> {english}\n"
				tabFileAY.write(entryAY)
				entriesAY += 1
					
			if english:
				entryEN = f"{english}\t<font color=red>CHP:</font> {chipaya}\\n"\
					f"<font color=green>ESP:</font> {castellano}\\n"\
					f"<font color=blue>AYM:</font> {aymara}\n"
				tabFileEN.write(entryEN)
				entriesEN += 1
			
			
	tabFileCAP.close()
	tabFileES.close()
	tabFileAY.close()
	tabFileEN.close()
	
	print(f"Entries written in Chipaya dictionary:\n"
		f"{newFileName}_cap-es.tab: {entriesCAP}, {newFileName}_es-cap.tab: {entriesES}, "
		f"{newFileName}_ay-cap.tab: {entriesAY}, {newFileName}_en-cap.tab: {entriesEN}")
	
	dics = {
		'cap-es' : "Chipaya–ES,AY,EN (Olson et al.)",
		'es-cap' : "Español–Chipaya (Olson et al.)",
		'ay-cap' : "Aymara–Chipaya (Olson et al.)", 
		'en-cap' : "English–Chipaya (Olson et al.)"
	}
	
	bookInfo = \
		"description=Ronald D. Olson, Ulpian Ricardo López García et al. (2022) Diccionario Chipaya<br>"+\
		"Descargar otros diccionarios en http://www.illaa.org/index.php/diccionarios\n"+\
		"sametypesequence=g"
	
	
	for lang in dics:
		dirName = lang + '_' + pathlib.Path(newFileName).stem
		
		if os.path.exists(dirName):
			shutil.rmtree(dirName, ignore_errors=True)
		
		os.mkdir(dirName)
		baseName = newFileName + '_' + lang
		newPath = dirName + os.sep + baseName 
		os.rename(baseName + ".tab", newPath + ".tab")
		
		os.system('/usr/lib/stardict-tools/tabfile "' + newPath + '.tab"')
		
		if not os.path.exists(newPath + ".ifo"):
			sys.exit("Error creating the StarDict files: " + newPath + ".*")
		
		with open(newPath + '.ifo', 'r+') as ifoFile:
			ifoContents = ifoFile.read()
			newIfoContent = f"bookname={dics[lang]}\n{bookInfo}"
			ifoContents = re.sub(r"bookname=.+", newIfoContent, ifoContents, flags=re.S)
			ifoFile.seek(0)
			ifoFile.write(ifoContents)
			ifoFile.truncate()
			


def stripDef(s):
	"""strips numbers from the front of definitions and leading/trailing spaces"""
	s = s.strip()
	return re.sub(r"^\d\.? ?", "", s)



if __name__ == "__main__":
    main()
