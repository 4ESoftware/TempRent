# -*- coding: utf-8 -*-
"""
Created on Wed Jul 15 07:21:25 2020

@author: Andrei
"""


# -*- coding: utf-8 -*-
"""
Created on Thu Jun 18 14:04:52 2020

@author: Andrei
"""
import pandas as pd

from libraries import Logger
from libraries import LummetryObject

import mysql.connector as mysql
from time import time

import matplotlib.pyplot as plt
import seaborn as sns
import os
from datetime import datetime

class ExportEngine(LummetryObject):
  def __init__(self, **kwargs):
    super().__init__(**kwargs)
    self.SERVER = self.config_data['SERVER']
    self.PORT = self.config_data['PORT']
    self.USER = self.config_data['USER']
    self.PASS = self.config_data['PASS']
    self.DB = self.config_data['DB']
    self._rep_base = '_get_report_'
    
    try:
      self.connect()
    except:
      self.P("WARNING! Couldn't connect to the mysql DB!")
    return
  
  def connect(self):
    self.db = mysql.connect(
      host=self.SERVER,
      port=int(self.PORT),
      user=self.USER,
      passwd=self.PASS,
      database=self.DB
    )
    cursor = self.db.cursor()
    ## getting all the tables which are present in 'datacamp' database
    cursor.execute("SHOW TABLES")
    tables = cursor.fetchall()
    self.D("Connected to '{}'. Found {} tables".format(self.DB, len(tables)))
    self._tables = tables
    return
    
  def _load_data(self, table):
    cursor = self.db.cursor()
    cursor.execute('SELECT * from '+table)
    data = cursor.fetchall()
    cols = cursor.column_names
    dct_res = {cols[i]:[x[i] for x in data] for i,c in enumerate(cols)}
    return pd.DataFrame(dct_res)
  
  def _load_sql(self, sql):
    cursor = self.db.cursor()
    cursor.execute(sql)
    data = cursor.fetchall()
    cols = cursor.column_names
    dct_res = {cols[i]:[x[i] for x in data] for i,c in enumerate(cols)}
    return pd.DataFrame(dct_res)
    
  def load_data(self, table):
    self.P("Loading table '{}'".format(table))
    t0 = time()
    df = self._load_data(table)
    t_elapsed = time() - t0
    self.P("  Done loading in {:.1f}s".format(t_elapsed))
    return df
  
  def export_all(self):
    for tbl in self._tables:
      if type(tbl) is tuple:
        tbl = tbl[0]
      self.P("Exporting '{}'...".format(tbl))
      df = self.load_data(tbl)
      self.log.save_dataframe(df, 'export_'+tbl, folder='output')
    return
    
  def shutdown(self):
    self.db.close()
    self.P("Data connection closed.")
  
  
if __name__ == '__main__':
  l = Logger("TRR", config_file='config/config.txt')
  
  eng = ExportEngine(DEBUG=True, log=l)
  
  eng.export_all()

  eng.shutdown()
