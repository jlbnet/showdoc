# -*- coding: cp936 -*-

#导入smtplib和MIMEText   

import sys
sys.path.append('../lib')
sys.path.append('lib')
sys.path.append('../conf')
sys.path.append('conf')
import smtplib
from email.Header import Header
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

import settings

config = settings.get_config()

def send_mail(to_list,sub,content, files): 
    global config
#''''' 
#to_list:发给谁 
#sub:主题 
#content:内容 
#send_mail("aaa@126.com","sub","content") 
#''''' 
    mail = config["mail"]
    me = mail["user"]+"<"+mail["user"]+"@"+mail["postfix"]+">"  
    msg = MIMEMultipart('alternative')  
    msg['Subject'] = Header(sub, 'gb2312') #设置主题   
    msg['From'] = me     #发件人   
    msg['To'] = ";".join(to_list) #收件人   
    try:
        text = "html格式的邮件"
        # Record the MIME types of both parts - text/plain and text/html.
        part1 = MIMEText(text, 'plain')
        part2 = MIMEText(content, 'html', 'gb2312');

        # Attach parts into message container.
        # According to RFC 2046, the last part of a multipart message, in this case
        # the HTML message, is best and preferred.
        msg.attach(part1)
        msg.attach(part2)

        for f in files:
            #附件发送文件
            att2 = MIMEText(open(f, 'rb').read(), 'base64', 'gb2312')
            att2["Content-Type"] = 'application/octet-stream'
            att2["Content-Disposition"] = 'attachment; filename="%s"' % f
            msg.attach(att2)

        #附件发送图片
        #file1 = "C:\\hello.jpg"
        #image = MIMEImage(open(file1,'rb').read())
        #image.add_header('Content-ID','<image1>')
        #msg.attach(image)
        s = smtplib.SMTP_SSL(mail["host"], 465)
        #s.debuglevel = 5
        #s.connect('smtp.126.com', 465) #mail_host, "465"); #465-smtp-ssl, 25-smtp 
        #s.starttls();
        s.login(mail["user"]+'@'+mail["postfix"], mail["password"])
        s.sendmail(me, to_list, msg.as_string())  
        s.close()  
        return True  
    except Exception, e:  
        print str(e)  
        return False
    
if __name__ == '__main__':
    content = """\
<html>
  <head></head>
  <body>
    <p>测试结果<br>
       How are you?<br>
    </p>
  </body>
</html>
"""
    files = []
    #files.append('parse_connect.py')
    #files.append('2016-07-11_控制器掉线.csv')
    #files.append('2016-07-11_车位检测.csv')
    if send_mail(mailto_list,"周家村测试",content, files):
        print u"发送成功"
    else:  
        print u"发送失败"  
