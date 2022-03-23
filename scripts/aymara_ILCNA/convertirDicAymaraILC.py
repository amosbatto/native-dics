#!/usr/bin/env python3

scriptHelp = '''
convertirDicAymaraILC.py es un script de Python para convertir el diccionario aymara del ILC Aymara 
para utilizarlo en GoldenDict para PCs de Linux y Windows y SimiDic en aparatos móviles de Android.

Uso:   python3 convertirDicAymaraILC.py DICCIONARIO.docx [NUEVO-ARCHIVO]
Ayuda: python3 convertirDicAymaraILC.py -h

El script utiliza el comando 'soffice --headless --convert-to "txt:Text (encoded):UTF8" DICCIONARIO.docxs'
para producir un archivo de texto plano y formeata este archivo para crear el archivo NUEVO-ARCHIVO.tab 
con codigo HTML para cursivo <i>...</i> y colores <font color="#228B22">...</font>.
El archivo TAB es pasado por el comando '/usr/lib/stardict-tools/tabfile NUEVO-ARCHIVO.tab' 
para producir los siguientes archivos que pueden ser importados en GoldenDict:
   NUEVO-ARCHIVO.tab
   NUEVO-ARCHIVO.ifo
   NUEVO-ARCHIVO.idx
   NUEVO-ARCHIVO.dict.dz
Si el NUEVO-ARCHIVO es especificado, el script utiliza el nombre del DICCIONARIO.

Después es possible usar SimidicBuilder (https://sourceforge.net/projects/simidicbuilder/) 
con el comando 'java -jar SimidicBuilder.jar' para crear los bases de datos para importar
el diccinario en SimiDic. Ver: 
https://web.archive.org/web/20160220060532/https://www.simidic.org/wiki/index.php/Crear_e_Importar_Nuevos_Diccionarios 

Autor: Amos Batto <amosbatto@yahoo.com>
Versión: 0.1 (2022-01-23)
Licencia: LGPL 3.0 o después 
Requisitos: Python 3.6 o después, LibreOffice y stardict-tools 
(he instalado stardict-tools 3.0.6+dfsg-0.3~bpo10+1 de buster-backports en Debian 11 bullseye)
'''
 
import shutil, subprocess, tempfile, re, os, os.path, sys, pathlib, shutil

tDelete = (
	r"A\. a\.",
	r"ANU",
	r"Illimani khunu qullu achachila", #photo
	r"Wich’inkha q’ara achaku millk’i tika lunthata\.", #photo
	r"Achaliri anuxa wich’ikhapasa tisikiwa", #photo
	r"Achuqalla uywaxa wich’inkha layu uywawa\.", #photo
	r"Amankaya panqara",
	r"Marka irpirinakaxa suma sarantañataki akhult’asipxiwa\.", #photo
	r"CH\. Ch\.",
	r"Chikutixa suma sarnaqañataki chiqanchi\.", #photo
	r"CHH\. Chh\.",
	r"Chhaxraña",
	r"CH’ ch’",
	r"I\. i\.",
	r" iwija" #photo
	r"J\. j\.",
	r"K\. k\.",
	r"K’ARI",
	r"K’ALLK’U",
	r"KH\. Kh\.", 
	r"K’\. k’\.",
	r"K’awna/k’anwa", #photo
	r"Luruk’u",
	r"L l",
	r"LL\. ll\.",
	r"Llataku",
	r"Manqhancha",
	r"M\. m\.", 
	r"Ñ",
	r"P\. p\.",
	r"Panqaranaka chuyma wali p’arxtayasi",
	r"P’\. p’\.",
	r"P’uyu",
	r"Q\. 	q\.",
	r"Qarwa",
	r"QH\. Qh\. ", 
	r"Q’\. q’\.",
	r"R\. r\.",
	r"S\. s\.",
	r"Sañu", 
	r"T\. t\. ",
	r"TH\. th\.",
	r"T’ t’",
	r"U. u.",
	r"W\. w\.",
	r"X\. x\.",
	r"Xaxu",
	r"Y\. y\.",
	r"Yapu"
)

dGrammar = { 
	r"\((Ajayu aru|Pacha aru|K'ila aru|Om|Suf\.n|Suf\.v\.)\)": r'<font color="#228B22">(\1)</font>',     # ForestGreen
	r"\[(Am|May|Aa)(\.:|:|\.)\s+(.+?)\]":                      r'<font color="#800080">[\1.: \3]</font>', # purple
	r"// (.+?)($| / |\b\d\.)":                                 r'<font color="#0000FF">// \1</font>\2',     # blue
	r"\b(\d)\.":                                               r"<b>\1.</b>",
	r" / ":                                                    r' <font color="#228B22">/</font> '       # ForestGreen
}

tItalics = (
	r"A los asustados jalar de un mechón de cabello",
	r"A mi opinión, con palabras contrarias han cambiado",
	r"Aceite",
	r"Al asustado jalar de un mechón de cabello",
	r"alcohol",
	r"Alguien alumbrando",
	r"Alonso de Mendoza",
	r"aluminio",
	r"arveja",
	r"avion",
	r"bicicleta",
	r"Bolivia",
	r"Bolivia, Perú, Ecuador",
	r"brocados",
	r"café",
	r"calcio, fosforo",
	r"Capital",
	r"castellano",
	r"cemento",
	r"cerveza",
	r"Chile",
	r"Colocar el hueso deslocado",
	r"Consonante lateral alveolar",
	r"Consonante oclusiva bilabial simple",
	r"Copacabana",
	r"corcho, plastaformo",
	r"Cristobal Colón",
	r"Curar al asustado.",
	r"despacho",
	r"Durazno",
	r"duro, seco",
	r"Egipto",
	r"El abuelo sabe muy bien colocar a su lugar el hueso",
	r"El abuelo sana las luxaciones",
	r"Él que cura luxasiones",
	r"empresas",
	r"España",
	r"español",
	r"espejo",
	r"eucalipto",
	r"gelatina",
	r"helado",
	r"herencia",
	r"hierro",
	r"hilo",
	r"Hollín",
	r"iglesia",
	r"Isla del Sol",
	r"Jugando al balón se ha luxado",
	r"lente o anteojo",
	r"Lima",
	r"lisos",
	r"Luxarse.",
	r"machon",
	r"mantel",
	r"manzana",
	r"maquina",
	r"máquina",
	r"navidad",
	r"Nuestra Señora de La Paz",
	r"Obligar, cambiar de opinión",
	r"pantalón, buzo",
	r"pantalón, pollera",
	r"Para cocinar romper los palos",
	r"patrón",
	r"Pedro de La Gasca",
	r"pera",
	r"Perú",
	r"pico",
	r"Quitar la rama del árbol, romper los palos",
	r"Curar el dolor de la cabeza",
	r"San Juan",
	r"sarten",
	r"silla de rueda",
	r"tela",
	r"todos santos",
	r"tractor",
	r"Tractor",
	r"ventana",
	r"Viacha",
	r"vino",
	r"Yungas",
	r"zapato"
) 

#Replacements in the dictionary text. Mainly used to add bold.
dReplace = {
	r"–MA\. ":                    r"-MA. ",
	r"// Utapa, sutipa\.":        r"// Uta<b>pa</b>, suti<b>pa</b>.",
	r"/ Inakt’añaxa irnaqawina":  r"/ <b>Inakt’aña</b>xa irnaqawina",
	r"\. Utama\.":                r". Uta<b>ma</b>.",
	r"\. Sartama\. ":             r". Sarta<b>ma</b>. "
}

 
def main():
	
	if len(sys.argv) < 2 or len(sys.argv) > 4 :
		sys.exit("Error: Número incorrecto de argumentos.\n\n" + scriptHelp)
	
	if sys.argv[1] == '-h' or sys.argv[1] == '--help': 
		sys.exit(scriptHelp)
	
	dicFileName = sys.argv[1] #get DICCIONARIO.docx
	dicBaseName = os.path.splitext(dicFileName)[0]  #get path without file extension.
	
	
	if len(sys.argv) == 3:
		dicNewBaseName = sys.argv[2]
	else:
		dicNewBaseName = dicBaseName 
	
	try:
		subprocess.run( ("soffice", "--headless", "--convert-to", "txt:Text (encoded):UTF8", dicFileName) )
	except subprocess.CalledProcessError as e:
		sys.exit(e.output)
	
	
	with open(dicBaseName+".txt", "r") as dicFile:
		dicContents = dicFile.read()
	
	try:
		dicTabFile = open(dicNewBaseName+".tab", "w")
	except Exception as e:
		sys.exit(e); 
	
	#eliminate strings that can't be used in electronic dictionaries:
	#For some reason using string.replace() deletes the "Ñ" characters so need to use re.sub():
	for sDel in tDelete:
		dicContents = re.sub(r"^\s*" + sDel + r"\s*$", '', dicContents, flags=re.M)
	
	for sFind, sReplace in dReplace.items():
		dicContents = re.sub(sFind, sReplace, dicContents)
	
	for sItalic in tItalics:
		dicContents = re.sub(sItalic, "<i>"+sItalic+"</i>", dicContents)
	
	#Make all apostrophes straight:
	dicContents = re.sub("’", "'", dicContents)
	
	#Replace all tabs with spaces:
	dicContents = re.sub(r"\t", " ", dicContents)
	
	#eliminate empty lines: 
	dicContents = re.sub(r'^\s*$', '', dicContents, flags=re.M)
	dicContents = re.sub(r'\n{2,}', '\n', dicContents, flags=re.M)
	
	
	#Separate the key word(s) from the definitions with tabs:
	dicContents = re.sub(r"^([A-ZÑÄÏÜ¡'/ ]+)\. +", r"\1\t", dicContents, flags=re.M)
	dicContents = re.sub(r"^([A-ZÑÄÏÜ¡'/ ]+)(…|!|\?)\.? +", r"\1\2\t", dicContents, flags=re.M)
	dicContents = re.sub(r"^([A-ZÑÄÏÜ¡'/ ]+) +\[", r"\1\t[", dicContents, flags=re.M)
		
	for sFind, sReplace in dGrammar.items():
		dicContents = re.sub(sFind, sReplace, dicContents, flags=re.I|re.M)
		
	dicContents = re.sub(r"(\s)(adj|adv|sm|ar|a|intr|impers|tr|prnl|st|s|am|lm|ml|vl|ks)\.", 
		r'\1<font color="#228B22">\2.</font>', dicContents)
	
	lContents = dicContents.split("\n")
	countDicEntries = 0
	newEntry = '';
	
	for line in lContents:
		lLine = line.split("\t")
		if len(lLine) <= 1 and countDicEntries > 0:
			newEntry += "\\n" + line
			continue
		elif len(lLine) == 2:
			if newEntry:
				dicTabFile.write(newEntry+"\n")
				countDicEntries += 1
			
			newEntry = lLine[0].lower() + "\t" + lLine[1]
	
	#write last line in dictionary:
	if newEntry:
		dicTabFile.write(newEntry+"\n")
		countDicEntries += 1
	
	dicTabFile.close()
	print("%d entradas escritas en el archivo '%s'." % (countDicEntries, dicNewBaseName+".tab"))
	
	os.system('/usr/lib/stardict-tools/tabfile "'+dicNewBaseName+'.tab"')
	
	if not os.path.exists(dicNewBaseName + '.ifo'):
		sys.exit("Error creando los archivos GoldenDict/StarDict.")
	
	bookname = "bookname=Aymara (ILCNA)\n"+ \
		"description=Instituto de Lengua y Cultura de la Nación Aymara-ILCNA (2021) Aru Pirwa Aymara, El Alto, Bolivia.\n"+ \
		"website=https://github.com/amosbatto/native-dics\n"+ \
		"sametypesequence=g"
	
	
	with open(dicNewBaseName + '.ifo', 'r+') as ifoFile:
		ifoContents = ifoFile.read()
		ifoContents = re.sub(r"bookname=.+", bookname, ifoContents, flags=re.S)
		ifoFile.seek(0)
		ifoFile.write(ifoContents)
		ifoFile.truncate()
	
	base = pathlib.Path(dicNewBaseName).stem
	
	if os.path.exists(dicNewBaseName):
		shutil.rmtree(dicNewBaseName, ignore_errors=True)
		
	os.mkdir(dicNewBaseName)
	os.rename(dicNewBaseName+".tab", dicNewBaseName + os.sep + base+".tab")
	os.rename(dicNewBaseName+".ifo", dicNewBaseName + os.sep + base+".ifo")
	os.rename(dicNewBaseName+".idx", dicNewBaseName + os.sep + base+".idx")
	os.rename(dicNewBaseName+".dict.dz", dicNewBaseName + os.sep + base+".dict.dz")


if __name__ == "__main__":
    main()




