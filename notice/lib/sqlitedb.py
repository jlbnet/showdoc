# -*- coding:utf8 -*-

"""
sqlite数据库管理
"""

import sqlite3

class DBQuery():
    """sqlite数据库访问工具类"""
    def __init__(self, dbfile): #构造函数
        self.conn = sqlite3.connect(dbfile, check_same_thread=False)
        self.cur = self.conn.cursor()
        
    def __del__(self): #析构函数
        self.cur.close()
        self.conn.close()

    def init(self):
        """初始化数据库表结构"""
        sql = ['drop table if exists access',
               """
create table access(
id integer primary key autoincrement,
tm datetime,status char(3),upserver varchar(50),uptime float, request varchar(255), param varchar(255)
)""",
               #'select * from access',
               'vacuum',
               ]
        for s in sql:
            self.execute(s);
        self.commit();

    def execute(self, sql):
        """执行指定语句"""
        #print sql
        try:
            return self.cur.execute(sql)
        except Exception, e:
            print 'sqlite execute error:', e
            print sql
            return False

    def opensql(self, sql):
        """查询数据库"""
        #print sql
        try:
            x = self.execute(sql)
            if x: #执行成功
                return self.cur.fetchall()
            else:
                return []
        except Exception, e:
            print 'opensql error:', e
            return []

    def commit(self):
        """提交到数据库"""
        self.conn.commit()

if __name__ == '__main__':
    x = DBQuery()
    x.init();
    x.execute('insert into access(tm, status,upserver,uptime,request, param) values("2016-04-30 12:13:00","200","192.1:8181",1.3,"POST", "json")');
    x.commit();
    print x.opensql('select * from access');
    x = ''
