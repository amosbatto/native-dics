#!/usr/bin/env python3

scriptHelp = '''
volteaLenguaDicMojTrin.py es un script de Python3 que voltea la lengua del diccionario
del Instituto de Lengua y Cultura Mojeño Trinitario "José Santos Noco Guaji" (2021) 
Taechirawkoriono Vechjiriiwo Trinranono: Diccionario Idioma Trinitario, Santa Cruz, Bolivia, 130pp.
Cambia la lengua de mojeño-castellano a castellano-mojeño. Produce dos archivos TAB 
con colores agregados para los diccionarios mojeño-castellano y castellano-mojeños y 
pasa estos archivos por tabfile de StarDict-Tools para producir los archivos de GoldenDict.

Uso: python3 volteaLenguaDicMojTrin.py MOJ-ES-DIC.tab ES-MOJ-NUEVO-DIC.tab MOJ-ES-DIR ES-MOJ-DIR  

Donde:
MOJ-ES-DIC.tab:  Achivo de format TAB que contiene el diccionario mojeño-castellano en texto plano.
ES-MOJ-NUEVO-DIC.tab: Nombre del nuevo archivo generado con la lengua volteada a castellano-mojeño.
MOJ-ES-DIR: Nombre del directorio generado con el diccionario mojeño-castellano.  
ES-MOJ-DIR: Nombre del directorio generado con el diccionario castellano-mojeño.

Autor: Amos Batto <amosbatto@yahoo.com>
Versión: 0.1 (2022-02-06)
Licencia: LGPL 3.0 (https://www.gnu.org/licenses/lgpl-3.0.en.html)  
'''
 
import re, sys, pathlib, os, os.path, shutil
 
def main():
	if len(sys.argv) != 5:
		sys.exit("Error en el número de argumentos.\n" + scriptHelp)
		
	mojTabFileName = sys.argv[1]
	esTabFileName  = sys.argv[2]
	mojDirName     = sys.argv[3]
	esDirName      = sys.argv[4]
	
	mojBookName = "bookname=Mojeño Trinitario–Español (ILC Trinitario JSNG)\n"
	esBookName  = "bookname=Español–Mojeño Trinitario (ILC Trinitario JSNG)\n"
	
	
	with open(mojTabFileName, "r") as mojTabFile:
		mojContents = mojTabFile.readlines()
	
	cntMojLines = cntEsEntries = cntMojEntries = 0;
	
	with open(esTabFileName, "w") as esFile:
		
		for entry in mojContents:
			cntMojLines += 1
			
			if entry.strip() == '':
				continue
			
			#RegEx to get key word(s) and definition from each entry in dictionary
			matchEntry = re.search(r'^(.*?)\t(.*)$', entry)
			
			if not matchEntry:
				print("No encuentra la palabra clave y definición en línea %d" % cntMojEntries)
				continue
			else:
				cntMojEntries += 1
			
			keyWord = matchEntry.group(1)
			restOfDef = definition = matchEntry.group(2)
			usageExamples = grammar = ''
			
			#RegEx to get grammatical part from definition if it exists
			matchDef = re.search(r'^\s*(.*?:)\s*(.*)', definition)
			
			if matchDef:
				grammar = matchDef.group(1);
				restOfDef = matchDef.group(2); 
			
			#RegEx to separate the definition (ending with dot, ?, ! or : followed by a space 
			#or end of line) from the rest of the definition
			matchSepDefs = re.search(r'^(.*?)([.?!:])( |$)(.*)', restOfDef)
			
			if not matchSepDefs:
				print("No encuentra definición(es) en línea %d que terminan con punto, ?, ! o :." % cntEsEntries)
				defs = restOfDef
			else:
				defs = matchSepDefs.group(1)
				moreInfo = matchSepDefs.group(4)
				
				if matchSepDefs.group(2) == '!' or matchSepDefs.group(2) == '?' : 
					defs += matchSepDefs.group(2)
			
			newEntry = defs + "\t" + moreInfo 
			
			if moreInfo:
				newEntry += " "
			
			newEntry += grammar + " " + keyWord
			 
			esFile.write(newEntry + "\n")
			cntEsEntries += 1
	
	print ("%d entradas en diccionario mojeño %s\n%d entradas creadas en diccionario castellano %s" % 
		(cntMojEntries, mojTabFileName, cntEsEntries, esTabFileName))


if __name__ == "__main__":
	main()
