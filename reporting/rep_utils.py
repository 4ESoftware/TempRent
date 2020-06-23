# -*- coding: utf-8 -*-
"""
Created on Thu Jun 18 14:04:52 2020

@author: Andrei
"""
import pandas as pd

from libraries_pub import Logger
from libraries_pub import LummetryObject

import mysql.connector as mysql
from time import time

import matplotlib.pyplot as plt
import os

class RepEngine(LummetryObject):
  def __init__(self, **kwargs):
    super().__init__(**kwargs)
    self.SERVER = self.config_data['SERVER']
    self.PORT = self.config_data['PORT']
    self.USER = self.config_data['USER']
    self.PASS = self.config_data['PASS']
    self.DB = self.config_data['DB']
    self._rep_base = '_get_report_'
    self.connect()
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
    return
    
  def _load_data(self, table):
    cursor = self.db.cursor()
    cursor.execute('SELECT * from '+table)
    data = cursor.fetchall()
    cols = cursor.column_names
    dct_res = {cols[i]:[x[i] for x in data] for i,c in enumerate(cols)}
    return pd.DataFrame(dct_res)
    
  def load_data(self, table):
    self.P("Loading table '{}".format(table))
    t0 = time()
    df = self._load_data(table)
    t_elapsed = time() - t0
    self.P("  Done loading in {:.1f}s".format(t_elapsed))
    return df
  
  def _get_report_1(self):
    """
    Past activity graph: see audit dataset line graph 

    Returns
    -------
    Full qualified path.

    """
    last_days = 30
    df_audit = self.load_data('audit')
    counts = df_audit.resample('D', on='logTime').id.count()
    dates = counts.index.strftime('%Y-%m-%d').tolist()
    vals = counts.values
    plt.figure(figsize=(13,8))
    y = vals[-last_days:]
    x = list(range(1, last_days+1))[:len(vals)]
    plt.bar(x, y)    
    plt.xticks(x, dates)
    plt.xlabel('Days')
    plt.ylabel('Count of actions')
    plt.title('Last {} days activity history'.format(last_days))
    return self._save_png(1)
    
  
  def _get_report_2(self):
    """
    Pie/Bar report with number of tenders for each tag

    Returns
    -------
    Full qualified path.

    """
    return self._save_png(2)
  
  def _get_report_3(self):
    """
    Number of bids aggregated at week-day level

    Returns
    -------
    Full qualified path.

    """
    return self._save_png(3)
  
  def get_avail_reports(self):
    max_rep = 100
    avail = []
    for i in range(max_rep+1):
      if hasattr(self, self._rep_base + str(i)):
        avail.append(i)
    return avail
  
  def get_report(self, report_id):
    avails = self.get_avail_reports()
    if report_id not in avails:
      self.P("ERROR: must supply a valid report number {}".format(avails))
      return None
    rep_name = '_get_report_'+str(report_id)
    rep_fun = getattr(self, rep_name)
    return rep_fun()
    
  def _save_png(self, report_id):
    fn = self.log.save_plot(plt, label='REP_{:02d}'.format(report_id))
    return fn
    
  def shutdown(self):
    self.db.close()
    self.P("Data connection closed.")
  
  
if __name__ == '__main__':
  l = Logger("TRR", config_file='config/config.txt')
  
  eng = RepEngine(DEBUG=True, log=l)
    
  eng.get_report(1)
  # eng.shutdown()