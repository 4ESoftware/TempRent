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

class RepEngine(LummetryObject):
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
  
  def _get_report_1(self, name_only=False):
    """
    Past activity graph: see audit dataset line graph 

    Returns
    -------
    Full qualified path.

    """
    if name_only:
      return "Past activity graph"
    # plt.style.use('ggplot')
    sns.set()
    last_days = 30
    df_audit = self.load_data('audit')
    df = df_audit.loc[:,['logTime', 'type','id']]
    df['date'] = df.logTime.dt.floor('d')
    df.drop(columns=['logTime'], inplace=True)
    df_counts = df.groupby(['date','type']).count().unstack().fillna(0).unstack().reset_index().sort_values('date')
    df_counts['count'] = df_counts[0]
    df_counts = df_counts.loc[:,['date','type','count']]
    df_counts['types'] = df_counts['type'].apply(lambda x: 'success' if x==1 else 'failure')
    df_type1 = df_counts[df_counts.type == 1]
    df_type2 = df_counts[df_counts.type == 2]
    
    dates = df_type1.date.dt.strftime('%Y-%m-%d').tolist()
    # vals1 = df_type1[0].values
    # vals2 = df_type2[0].values
    plt.figure(figsize=(13,8))
    x = list(range(0, last_days))[:len(dates)]
    # _len = len(x)
    # y1 = vals1[-_len:]
    # y2 = vals2[-_len:]
    
    # plt.bar(x, y1, label='successes')    
    # plt.bar(x, y2, label='failures')    
    # plt.xlabel('Days', fontsize=18)
    # plt.ylabel('Count of actions', fontsize=18)
    ax = sns.barplot(x='date', hue='types', y='count', data=df_counts)
    plt.xticks(x, dates)
    plt.title('Last {} days activity history ({:%Y-%m-%d  %H:%M})'.format(
      last_days, datetime.now()), fontsize=30)
    plt.legend(title='Activity type')
    for p in ax.patches:
      ax.annotate(
        "%.2f" % p.get_height(), 
        (p.get_x() + p.get_width() / 2., p.get_height()),
        ha='center', va='center', fontsize=11, color='gray', rotation=90, 
        xytext=(0, 20),textcoords='offset points'
        )    
    return self._save_png(1)
    
  
  def _get_report_2(self, name_only=False):
    """
    Pie/Bar report with number of tenders for each tag

    Returns
    -------
    Full qualified path.

    """
    if name_only:
      return "Tenders per tag"
    
    sql = (
      "SELECT COUNT(project_id) cnt, tag FROM " +
      "  (SELECT tags.project_id, tags.keyword_id, keywords.value tag" + 
      "   FROM tags, keywords WHERE tags.keyword_id=keywords.id) subq" +
      " GROUP BY tag")
    df = self._load_sql(sql)
    df = df.set_index('tag')
    sns.set()
    df.plot.pie(y='cnt', figsize=(21,18))
    plt.title('Density of tags in projects ({:%Y-%m-%d  %H:%M})'.format(
      datetime.now()), fontsize=30)
    plt.legend(
      ncol=5, 
      loc='lower center', 
      bbox_to_anchor=(0., -0.2, 1., .102),
      title='Type of project property (tag)')
    # plt.subplots_adjust(left=0.1, bottom=-2.1, right=0.75)
    return self._save_png(2)
  
  def _get_report_3(self, name_only=False):
    """
    Number of bids aggregated at week-day level

    Returns
    -------
    Full qualified path.

    """
    if name_only:
      return "Bids and values"
    
    df = self._load_sql('SELECT COUNT(*) cnt, SUM(price) vals, DATE(created_at) dt FROM bids GROUP BY dt')
    df = df.sort_values('dt')
    vals = df['vals'].values
    plt.figure(figsize=(13,8))
    ax = sns.barplot(x='dt', y='cnt', data=df)
    plt.title('Bids per day with overall values ({:%Y-%m-%d  %H:%M})'.format(
      datetime.now()), fontsize=30)    
    plt.xlabel('Date of the bids', fontsize=18)
    plt.ylabel('Number of bids', fontsize=18)
    for i, p in enumerate(ax.patches):
      ax.annotate(
        "{:,.1f} lei".format(vals[i]), 
        (p.get_x() + p.get_width() / 2., p.get_height()),
        ha='left', va='top', fontsize=12, color='black', rotation=90, 
        xytext=(0, 10),textcoords='offset points'
        )    
    return self._save_png(3)
  
  def get_avail_reports(self):
    max_rep = 100
    avail = {}
    for i in range(max_rep+1):
      if hasattr(self, self._rep_base + str(i)):
        fnc = getattr(self, self._rep_base + str(i))
        avail[i] = fnc(True)
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
    
  eng.get_report(3)
  # eng.shutdown()
  l.P(eng.get_avail_reports())