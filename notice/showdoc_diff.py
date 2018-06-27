#!/usr/bin/python
# -*- coding:utf8 -*-

"""
showdoc 文件修改检查，钉钉群发通知
@author jlbnet
@date 2018-6-19
"""

import time
import datetime
import os
import sys
import base64
import difflib
sys.path.append('lib')
from lib import settings
from lib import sqlitedb
from lib import http
import logging
import logging.config
import traceback
import version

config_file = "./conf/logging.conf"
logging.config.fileConfig(config_file)
logger = logging.getLogger("rotatelog")
logger.handlers[1].suffix = '%Y-%m-%d.log' #auto set rotated log file name suffix daily

dtformat = '%Y-%m-%d %H:%M:%S'
cfg = settings.get_settings()
 
def decode(s):
    #$page_content = gzuncompress(base64_decode($content)); php ==> base64.b64decode -> decode('zlib') -> decode("utf8")
    npd = base64.b64decode(s)
    result = npd.decode('zlib')
    sn = result.decode("utf8")
    sn = sn.replace("&quot;", '"')
    return sn
        
def main():
  logger.info(u"start check showdoc diff, ver: %s" % (version.version))
  #print cfg
  qry = sqlitedb.DBQuery(cfg['db'])
  today = datetime.datetime.now()
  #stoday = datetime.datetime.strftime(today, '%Y-%m-%d')
  #today = datetime.datetime.strptime(stoday, '%Y-%m-%d')
  yesterday = datetime.datetime.now() + datetime.timedelta(days=-1);
  syesterday = datetime.datetime.strftime(yesterday, '%Y-%m-%d')
  #syesterday = '2018-06-22'

  yesterday = datetime.datetime.strptime(syesterday, '%Y-%m-%d')
  #print today, yesterday
  today = int(time.mktime(today.timetuple()))
  yesterday = int(time.mktime(yesterday.timetuple()))
  #print today, yesterday
  #找出昨天修改过的所有文件
  rows = qry.opensql("select page_history_id, page_id, item_id,author_username,page_title,page_content, addtime from page_history where addtime between %d and %d order by page_history_id desc" % (yesterday, today));
  #print rows
  pagelist = {}
  for r in rows:
    page_id = r[1]
    item_id = r[2]
    if page_id in pagelist.keys(): continue #按时间顺序倒排，已经找到最后一次修改，则之前的修改跳过
    pagelist[page_id] = {}
    page_title = r[4]
    #print page_id,  page_title
    #找上一天的最后一次修改记录
    old = qry.opensql("select page_history_id, page_id, item_id,author_username,page_title,page_content, addtime from page_history where addtime < %d order by page_history_id desc limit 1" % (yesterday));
    if old == [] or old == (): #没有找到
        #pagelist[page_id] = {}
        pass
    else: #有旧版本，生成差异
        o = old[0]
        new = {}
        new['item_id'] = r[2] #item_id
        new['page_title'] = r[4] #page_title
        new['time'] = datetime.datetime.fromtimestamp(r[6]) #addtime
        new['author'] = r[3] #author
        np = r[5] #new page_content
        op = o[5] #old page_content
        
        #print decode(np)
        #diff = difflib.unified_diff(decode(np).splitlines(1), decode(op).splitlines(1))
        #diffstr = ''.join(diff) #生成字符串比较diff文件。但内容太长，不适合批量发送
        #return
        if not item_id in pagelist.keys():
            pagelist[item_id] = {}
        pagelist[item_id][page_id] = new
  #print pagelist 
  text = ''
  atTel = []
  atUser = []
  for item_id in pagelist.keys():
     sql = 'select item_id,item_name from item where item_id=%d' % item_id
     rows = qry.opensql(sql)
     item_name = ''
     if rows !=[] and rows != ():
         r = rows[0]
         item_name = r[1]
         text = text + u'### %s\n' % item_name
         #print item_name
         for page_id in pagelist[item_id].keys():
            if pagelist[item_id][page_id] == {}: continue #无差异
            page = pagelist[item_id][page_id]
            #print page
            #print page_id, page['page_title'], page['author'], datetime.datetime.strftime(page['time'], '%Y-%m-%d %H:%M:%S')
            #增加跳转链接
            text = text + u"- **[%s](http://192.168.10.114:5000/showdoc/index.php?s=/%d&page_id=%d)** \n ###### %s, 修改时间: %s\n" %(page['page_title'], page['item_id'], page_id, page['author'],datetime.datetime.strftime(page['time'], '%Y-%m-%d %H:%M:%S'))
            
            #查找关注的用户，取出用户名，加入 @
            sql = 'select u.uid, username,email,tel from page_user p inner join user u on p.uid=u.uid and p.page_id=%d' % page_id
            #print sql
            rows = qry.opensql(sql)
            if rows == [] or rows == (): continue #无人关注
            for r in rows:
                #取出关注列表中的用户
                username = r[1]
                email = r[2] #邮件
                tel = r[3] #tel 手机号，@用
                if tel == None: continue #没有设置手机号，跳过
                if not tel in atTel:
                    atTel.append(tel)
                #if not username in atUser:
                #    atUser.append(username)
  #print '---------------------------------------------'
  #print text
  #print atTel
  #text = ''
  if text == '': return
  #https://open-doc.dingtalk.com/docs/doc.htm?spm=a219a.7629140.0.0.karFPe&treeId=257&articleId=105735&docType=1
  url = cfg['url'] + cfg['token']
  at = '\n'
  for t in atTel:
      at = at + '@%s' % t
  text = text + at;
  msg = {
     "msgtype": "markdown",
     "markdown": {
        "title":u"showdoc文档修改",
        "text": u'#### showdoc文档修改\n ' + text
     },
     "at": {
        "atMobiles": atTel, 
     "isAtAll": False
     }
    }
  #print msg
  f = http.post(url, msg)
  #logger.info(u"发送钉钉群消息结果: %d" % f.status_code)
  logger.info(u"stop check showdoc")

if __name__ == "__main__":
  main()
