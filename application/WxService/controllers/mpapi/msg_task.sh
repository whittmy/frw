
# ��ʱִ�� noticetask ������
step=5 #��������������ܴ���60  

PROGRAM="/a/apps/php-5.4.24/bin/php /a/domains/other.nybgjd.com/public_html/frw/mpapi.php  mpapi ctrler noticetask"

for (( i = 0; i < 60; i=(i+step) )); do  
    $PROGRAM > /dev/null 2>&1 &  
    #echo $i; 
    sleep $step  
done  
  
exit 0  

