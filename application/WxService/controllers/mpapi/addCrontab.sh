
PROGRAM="/a/domains/other.nybgjd.com/public_html/frw/application/WxService/controllers/mpapi/msg_task.sh"
CRONTAB_CMD="* * * * * $PROGRAM"
(crontab -l 2>/dev/null | grep -Fv $PROGRAM; echo "$CRONTAB_CMD") | crontab - 
COUNT=`crontab -l | grep $PROGRAM | grep -v "grep"|wc -l ` 
if [ $COUNT -lt 1 ]; then 
        echo "fail to add crontab $PROGRAM" 
        exit 1 
fi
crontab -l
exit 0
