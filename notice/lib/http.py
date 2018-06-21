#!/usr/bin/python     
#-*-coding:utf-8-*-     
      
import requests
import time
from datetime import datetime, timedelta
import json

def post(url, data):
    session = requests.Session()
    header = {'Content-Type': 'application/json'}
    f = session.post(url,headers=header,json=data)
    #print f.status_code
    #print f.text
    #print f.content
    
if __name__ == '__main__':
    url = "https://oapi.dingtalk.com/robot/send?access_token=e2188473acffc4d7b047ed528dc94ab4fcfd9d1dc34fcf129b142e12f95e6ecf"
    data = {
     "msgtype": "markdown",
     "markdown": {
        "title":"showdoc文档修改",
        "text":"##   \n > 9度，@1825718XXXX 西北风1级，空气良89，相对温度73%\n\n  > ###### 10点20分发布 [天气](http://www.thinkpage.cn/) "
     },
     "at": {
        "atMobiles": [
            
        ], 
     "isAtAll": False
     }
    }
    post(url, data)
