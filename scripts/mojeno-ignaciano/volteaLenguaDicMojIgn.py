# volteaLenguaDicMojIgn.py voltea la lengua en el Diccionario Mojeño Ignaciano (2021) por 
# el Instituto de Lengua y Cultura Mojeño Ignaciano “Salvador Chappy Muibar”
# de mojeño-castellano a castellano-mojeño.
# 
# Para ejecutarlo: python3 volteaLenguaDicMojIgn.py ARCHIVO-MOJEÑO.tab ARCHIVO-NUEVO-CASTELLANO.tab
#
# Autor: Amos Batto <amosbatto@yahoo.com>
# Licencia: LGPL 3.0 (https://www.gnu.org/licenses/lgpl-3.0.en.html)  
# Código: https://github.com/amosbatto/native-dics
'''
He normalizando muchas entradas en el diccionario para funcionar con este script para voltear 
el diccionario de mojeño-castelleño a castellano-mojeño, para que se pueda buscar en castellano 
en GoldenDict y SimiDic. El texto necesita mucha revisión para eliminar los errores en la conversión 
de mojeño-castelleño a castellano-mojeño.

El script asume que todo el texto antes del primer punto (.) es la definición de castellano, y 
cada término de castellano es separado por una coma (,) o un punto y coma (;). 
Se asume que que todo el texto después del primero punto en la entrada son ejemplos de uso, 
y no puede ser volteado. 

El script funciona bien para voltear la siguiente entrada de mojeño-castelleño:
-pusisika (v) humear; evaporizar. Tipusisika eta yuku. El fuego está humeando. Nupusisi’aka taicha eta taijurewa eta sache, evapora mi calentura por el sol caliente.

El script analiza la entrada en esta forma:

mojeño    | gramatica | definición1 | definición2 | Ejemplos de uso
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-pusisika | (v)       | humear;     | evaporizar. | Tipusisika eta yuku. El fuego está humeando. Nupusisi’aka taicha eta taijurewa eta sache, evapora mi calentura por el sol caliente.

Entonces va a crear dos entradas en el diccionario castellano-mojeño:

humear; evaporizar (v) -pusisika. Tipusisika eta yuku. El fuego está humeando. Nupusisi’aka taicha eta taijurewa eta sache, evapora mi calentura por el sol caliente.
evaporizar; humear (v) -pusisika. Tipusisika eta yuku. El fuego está humeando. Nupusisi’aka taicha eta taijurewa eta sache, evapora mi calentura por el sol caliente.

Sin embargo mi script no funciona muy bien con las siguientes entradas, porque no hay 
un punto para separar las definiciones españolas de los ejemplos de uso.
-puyusi (n) rodilla, pimechanu eta pipuyusi móstrame tu rodilla.
-putunuku (v) comer rápido; comer apurado; Ena tirimaikara’iana, los bailadores comen ligerito.
-putuwa’u (n) rapidez, urgencia. Actuar rápido; apúrate a hacerlo; putuwa’u piajucha, apúrate a escribir.

El script analiza estas entradas así:

Mojeño   | gramatica | definición1  | definición2                                | definición3          | definición4                   | Ejemplos de uso
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
-puyusi  | (n)       | rodilla,     | pimechanu eta pipuyusi móstrame tu rodilla.|                      | 
-putunuku| (v)       | comer rápido;| comer apurado,                             | Ena tirimaikara’iana,| los bailadores comen ligerito.|
-putuwa’u| (n)       | rapidez,     | urgencia.                                                                                         | Actuar rápido; apúrate a hacerlo; putuwa’u piajucha, apúrate a escribir. 

Entonces, va a crear las siguientes entradas erroneas en el diccionario castellano-mojeño:

rodilla, pimechanu eta pipuyusi móstrame tu rodilla (n) -puyusi.
pimechanu eta pipuyusi móstrame tu rodilla, rodilla (n) -puyusi.

comer rápido; comer apurado, Ena tirimaikara’iana, los bailadores comen ligerito (v) -putunuku. 
comer apurado, comer rápido; Ena tirimaikara’iana, los bailadores comen ligerito (v) -putunuku. 
Ena tirimaikara’iana, comer rápido; comer apurado, los bailadores comen ligerito (v) -putunuku. 
los bailadores comen ligerito, comer rápido; comer apurado, Ena tirimaikara’iana (v) -putunuku. 

rapidez, urgencia (n) -putuwa’u. Actuar rápido; apúrate a hacerlo; putuwa’u piajucha, apúrate a escribir.
urgencia, rapidez (n) -putuwa’u. Actuar rápido; apúrate a hacerlo; putuwa’u piajucha, apúrate a escribir.


Para evitar estos errores, he normalizado muchas entradas la siguiente forma con un punto para 
terminar las definiciones españolas y una coma o punto y coma para separar cada definición:
-puyusi (n) rodilla. Pimechanu eta pipuyusi. Móstrame tu rodilla.
-putunuku (v) comer rápido; comer apurado. Ena tirimaikara’iana. Los bailadores comen ligerito.
-putuwa’u (n) rapidez, urgencia; actuar rápido; apúrate a hacerlo. Putuwa’u piajucha. Apúrate a escribir.

Con las definiciones normalizadas, mi script puede crear las siguientes entradas correctas en el diccionario castellano-mojeño:

rodilla (n) -puyusi. Pimechanu eta pipuyusi. Móstrame tu rodilla.

comer rápido; comer apurado (v) -putunuku. Ena tirimaikara’iana. Los bailadores comen ligerito.
comer apurado, comer rápido (v) -putunuku. Ena tirimaikara’iana. Los bailadores comen ligerito.

rapidez, urgencia; actuar rápido; apúrate a hacerlo (n) -putuwa’u. Putuwa’u piajucha. Apúrate a escribir.
urgencia, rapidez; actuar rápido; apúrate a hacerlo (n) -putuwa’u. Putuwa’u piajucha. Apúrate a escribir.
actuar rápido; apúrate a hacerlo, rapidez, urgencia (n) -putuwa’u. Putuwa’u piajucha. Apúrate a escribir.
apúrate a hacerlo, rapidez, urgencia; actuar rápido (n) -putuwa’u. Putuwa’u piajucha. Apúrate a escribir.


Todavía hay cosas que el script no puede resolver, que requiere un editor humano para corregir. Por ejemplo:

-senere'i (n) la vejiga.
->
la vejiga (n) -senere'i.

Hay que borrar "la " para que el usuario pueda encontrar la palabra buscando "vejiga":

vejiga (n) -senere'i.

Otro ejemplo:

-seresírare (n) donde siempre se cava (mina).
->
donde siempre se cava (mina) (n) -seresírare.

Hay que editarlo y crear dos entradas para que el usuario pueda buscar "mina" y "cavarse" para encontrar la palabra:

mina (donde siempre se cava) (n) -seresírare. 
cavarse siempre (lugar), mina (n) -seresírare. 
'''
 
import re, sys
 
def main():
	if len(sys.argv) != 3 :
		sys.exit("Error: Hay que especificar el diccionario en formato .tab y el nuevo archivo que será creado.\n\n" +
			"Uso: python3 volteaLenguaDicMojIgn.py ARCHIVO-MOJEÑO.tab ARCHIVO-NUEVO-CASTELLANO.tab")
	
	mojenoFileName  = sys.argv[1]
	espanolFileName = sys.argv[2]
	
	
	with open(mojenoFileName, "r") as mojenoFile:
		contents = mojenoFile.readlines()
	
	cntNewEntries = cntEntries = 0;
	
	with open(espanolFileName, "w") as espanolFile:
		
		for entry in contents:
			cntEntries += 1
			
			#RegEx to get key word(s) and definition from each entry in dictionary
			matchEntry = re.search(r'^(.*?)\t(.*)$', entry)
			
			if not matchEntry:
				print("No encuentra la palabra clave y definición en línea %d" % cntEntries)
				break;
			
			keyWord = matchEntry.group(1)
			restOfDef = definition = matchEntry.group(2)
			usageExamples = grammar = ''
			
			#RegEx to get grammatical part from definition if it exists
			matchDef = re.search(r'^\s*(\(.*?\))\s*(.*)', definition)
			
			if matchDef:
				grammar = matchDef.group(1) + ' ';
				restOfDef = matchDef.group(2); 
			
			#RegEx to separate the definitions (ending with dot, ? or ! followed by a space 
			#or end of line) from the examples of usage
			matchSepDefs = re.search(r'^(.*?)([.?!])( |$)(.*)', restOfDef)
			
			if not matchSepDefs:
				print("No encuentra definición(es) en línea %d que terminan con punto, ? o !" % cntEntries)
				defs = restOfDef
			else:
				defs = matchSepDefs.group(1)
				usageExamples = matchSepDefs.group(4)
				
				if matchSepDefs.group(2) != '.': 
					defs += matchSepDefs.group(2)
			
			matchParens = re.search(r'\((.*?)\)', defs)	
			
			if matchParens:
				insideParens = matchParens.group(1)
				#Replace commas with € inside parentheses:
				insideParens = re.sub(',', '€', insideParens)
				defs = defs[:matchParens.span(1)[0]] + insideParens + defs[matchParens.span(1)[1]:] 
			
			#separate the definitions by comma or semicolon:
			lDefs = re.findall(r' *(.+?)(;|,|$)', defs)
			semicolons = commas = 0
			
			for tDef in lDefs:
				if tDef[1] == ',':
					commas += 1;
				elif tDef[1] == ';':
					semicolons += 1;
			
			#if all commas or semicolons, then use that separator. If mixed, then use the separator for definition or ";" if none.
			if commas > 0 and semicolons == 0: 
				separator = ','
			elif semicolons > 0 and commas == 0: 
				separator = ';'
			else:
				separator = '';
			
			#Stupid designers of Python didn't include a normal "for" loop
			i = 0;
			while i < len(lDefs):
				ii = 0
				defsPart = lDefs[i][0]
				
				while ii < len(lDefs):
					if ii != i:
						sep = lDefs[ii][1];
						if sep == '' and separator != '':
							sep = separator
						else:
							sep = ';'
						
						defsPart += sep + ' ' + lDefs[ii][0]
					
					ii += 1
				
				if matchParens:
					#Replace € with commas inside parentheses:
					defsPart = re.sub('€', ',', defsPart)
				
				newEntry = defsPart + "\t" + grammar + keyWord
				
				if keyWord[-1] != '!' and keyWord[-1] != '?':
					newEntry += '.'
				
				if usageExamples != '':
					newEntry += ' ' + usageExamples
				
				espanolFile.write(newEntry + "\n")
				cntNewEntries += 1
				i += 1
	
	print ("%d entradas en diccionario mojeño %s\n%d entradas creadas en diccionario castellano %s" % 
		(cntEntries, mojenoFileName, cntNewEntries, espanolFileName))


if __name__ == "__main__":
	main()
