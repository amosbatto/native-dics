#!/usr/bin/env python3

"""
quick and dirty script to modify all the Sqlite3 databases for the NuSimi dictionary
app. It adds an info.version field to each database and sets it to 1. It also 
recreates the content of the words.summary fields for the new databases, because 
SimidicBuilder doesn't eliminate the HTML tags correctly.  

USAGE:
python3 modifyNuSimiDBs.py DIRECTORY 

WHERE:
DIRECTORY:  The directory holding the Sqlite3 database files.

Author:  Amos Batto (email: amosbatto@yahoo.com, Telegram: @amosbatto)
License: public domain
Version: 0.1 (2022-03-01)
"""

import os, os.path, sys, pathlib, sqlite3, lxml.html

def main():
	dirDBs = pathlib.Path(sys.argv[1])
	dbFiles = dirDBs.glob('*.db') # filter for only .db files
	
	newDics = [
		'ay_es_in.db',                # Aymara (ILCNA)
		'tr_es_in.db', 'es_tr_in.db', # Mojeño trinitario
		'ig_es_in.db', 'es_ig_in.db', # Mojeño ignaciano
		'qu_es_in.db', 'es_qu_in.db'  # Quechua (ILCNQ)
	]  
	
	for dbFile in dbFiles:
		try:
			con = sqlite3.connect(dbFile)
			cur = con.cursor()
			cur.execute("ALTER TABLE info ADD COLUMN version INT")
			cur.execute("UPDATE info SET version=1")
			con.commit()
			
			if os.path.basename(dbFile) in newDics:
				cur = con.cursor()
				rows = cur.execute("SELECT _id, meaning FROM words").fetchall()
				cnt = 0
				print("%d rows in table %s.words" % (len(rows), dbFile))
				
				for row in rows:
					_id     = row[0]
					meaning = row[1]
					
					#convert HTML to plain text
					plainMeaning = lxml.html.fromstring(meaning).text_content()
					summary = plainMeaning[0:45]
					
					if len(plainMeaning) > 45:
						summary += '…'
					
					cur.execute("UPDATE words SET summary=? WHERE _id=?", (summary, _id))
					con.commit()
					cnt += 1 
				
				print("%d summaries inserted in table %s.words" % (cnt, dbFile))
			
			con.close()
			
		except sqlite3.Error as e:
			print("Sqlite3 error:", e.args[0])  



if __name__ == "__main__":
	main()
