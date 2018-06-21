# -*- coding:utf8 -*-
from __future__ import with_statement
import json

#读配置文件
def get_settings():
    """Parses the settings from config file
    """
    with open("./conf/config.conf") as config:
        return json.load(config)
    
