#!/usr/bin/env python
#
# Orginal from Andre van der Vlies <andre@vandervlies.xs4all.nl> for MySQL. Changed
# and added some more functions for postgres.
#
#
# Changed by: Bas van der Vlies <basv@sara.nl>
#
# SARA API for Postgres Database
#
# Changed by: Ramon Bastiaans for Elsa
# SVN info:
#	$Id: DBClass.py 541 2004-06-29 09:19:40Z bas $
"""
This is an generic SARA python class that connects to any postgres database.
THe default one is uva_cluster_db, but that can specified as argument.

The basic usage is:
	import DBClass

	db_vars = DBClass.InitVars(DataBaseName='rc_cluster_db',
				User='root',
				Host='localhost',
				Password='',
				Dictionary='true')
	db = DBClass.DB(db_vars)

	# Get valuese from database
	print db.Get("SELECT * FROM clusters")

	# Set values into database
	bla=db.Set("DELETE FROM clusters WHERE clustername = 'bas'")
"""
from pyPgSQL import PgSQL

#
# Class to 'simplify' the declaration of 'global'
# variables....
#
class InitVars:
	Vars = {}

	def __init__(self, **key_arg):
		for (key, value) in key_arg.items():
			if value:
				self.Vars[key] = value
			else:	
				self.Vars[key] = None

	def __call__(self, *key):
		key = "%s" % key
		return self.Vars[key]

	def __getitem__(self, key):
		return self.Vars[key]

	def __repr__(self):
		return repr(self.Vars)

	def keys(self):
		barf =  map(None, self.Vars.keys())
		return barf

	def values(self):
		barf =  map(None, self.Vars.values())
		return barf
	
	def has_key(self, key):
		if self.Vars.has_key(key):
			return 1
		else:
			return 0

class DBError(Exception):
	def __init__(self, msg=''):
		self.msg = msg
		Exception.__init__(self, msg)
	def __repr__(self):
		return self.msg
	__str__ = __repr__

#
# Class to connect to a database
# and return the queury in a list or dictionairy.
#
class DB:
	def __init__(self, db_vars):

		self.dict = db_vars

		if self.dict.has_key('User'):
			self.user = self.dict['User']
		else:
			self.user = 'postgres'

		if self.dict.has_key('Host'):
			self.host = self.dict['Host']
		else:
			self.host = 'localhost'

		if self.dict.has_key('Password'):
			self.passwd = self.dict['Password']
		else:
			self.passwd = ''

		if self.dict.has_key('DataBaseName'):
			self.db = self.dict['DataBaseName']
		else:
			self.db = 'uva_cluster_db'

		# connect_string = 'host:port:database:user:password:
		dsn = "%s::%s:%s:%s" %(self.host, self.db, self.user, self.passwd)

		try:
			self.SQL = PgSQL.connect(dsn)
		except PgSQL.Error, details:
			str = "%s" %details
			raise DBError(str)

	def __repr__(self):
		return repr(self.result)

	def __nonzero__(self):
		return not(self.result == None)

	def __len__(self):
		return len(self.result)

	def __getitem__(self,i):
		return self.result[i]

	def __getslice__(self,i,j):
		return self.result[i:j]

	def Get(self, q_str):
		c = self.SQL.cursor()
		try:
			c.execute(q_str)
			result = c.fetchall()
		except PgSQL.Error, details:
			c.close()
			str = "%s" %details
			raise DBError(str)

		c.close()
		return result

	def Set(self, q_str):
		c = self.SQL.cursor()
		try:
			c.execute(q_str)
		        result = c.oidValue

		except PgSQL.Error, details:
			c.close()
			str = "%s" %details
			raise DBError(str)

		c.close()
		return result

	def Commit(self):
		self.SQL.commit()

#
# Some tests....
#
def main():
	db_vars = InitVars(DataBaseName='rc_cluster_db',
				User='root',
				Host='localhost',
				Password='',
				Dictionary='true')
	print db_vars;

	db = DB(db_vars)
	print db.Get("""SELECT * FROM clusters""")

if __name__ == '__main__':
	main()
