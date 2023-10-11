<?php
include("C:/xampp/htdocs/seeddms/inc/inc.Settings.php");
include("C:/xampp/htdocs/seeddms/inc/inc.Utils.php");
include("C:/xampp/htdocs/seeddms/inc/inc.Language.php");
include("C:/xampp/htdocs/seeddms/inc/inc.Init.php");
include("C:/xampp/htdocs/seeddms/inc/inc.DBInit.php");
include("C:/xampp/htdocs/seeddms/inc/inc.ClassNotificationService.php");
include("C:/xampp/htdocs/seeddms/inc/inc.ClassEmailNotify.php");
include("C:/xampp/htdocs/seeddms/inc/inc.LogInit.php");

$notifier = new SeedDMS_NotificationService($logger);
$notifier->addService(new SeedDMS_EmailNotify($dms, $settings->_smtpSendFrom, $settings->_smtpServer, $settings->_smtpPort, $settings->_smtpUser, $settings->_smtpPassword), 'email');
$arr = array();

//get
$db = $dms->getDB();
$query = "SELECT d.*, DATE_FORMAT(FROM_UNIXTIME(d.expires), GET_FORMAT(DATE, 'ISO')) as expired, nf.*, gdp.parentName as projectName, tf.name as category,
CASE
    WHEN datediff(DATE_FORMAT(FROM_UNIXTIME(d.expires), GET_FORMAT(DATE, 'ISO')), CURDATE()) = 7 THEN 'H-7' 
    WHEN datediff(DATE_FORMAT(FROM_UNIXTIME(d.expires), GET_FORMAT(DATE, 'ISO')), CURDATE()) = 30 THEN 'H-30' 
END
AS `exp`
FROM tbldocuments d
            JOIN tblnotify nf ON d.id = nf.target
            JOIN getdocumentparent gdp ON CAST(SUBSTR( d.folderList, 4, LOCATE(':', d.folderList, 4) - 4)AS INT)  = gdp.id
            JOIN tblfolders tf ON d.folder = tf.id
        WHERE expires != 0 and datediff(DATE_FORMAT(FROM_UNIXTIME(d.expires), GET_FORMAT(DATE, 'ISO')), CURDATE()) = 7 OR datediff(DATE_FORMAT(FROM_UNIXTIME(d.expires), GET_FORMAT(DATE, 'ISO')), CURDATE()) = 30
        AND targetType = 2";
$resArr = $db->getResultArray($query);
if (is_bool($resArr) && $resArr == false)
    return false;

    $subject = "expired_document_email_subject";
    $message = "expired_document_email_body";
    $params = array();
    $params['status'] = '';
    $params['assignnotif'] = '';

    try {
        if (count($resArr) > 0) {
            foreach ($resArr as $row) {
                $params['exp'] = $row['exp'];
                $params['name'] = $row['name'];
                $params['category'] = $row['category'];
                $params['projectname'] = $row['projectName'];
                $params['sitename'] = $settings->_siteName;
                
                if ($row["userID"] != -1) {
                    $u = $dms->getUser($row["userID"]);
                    if ($u && (!$u->isDisabled() || $incdisabled)){
                        $params['username'] = $u->getFullName();
                        $notifier->toIndividual('Administrator', $u, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
                    }
                } else { //if ($row["groupID"] != -1)
                    $g = $dms->getGroup($row["groupID"]);
                    if ($g){
                        $params['username'] = $g->getUsers()[0]->getFullName();
                        $notifier->toGroup('Administrator', $g, $subject, $message, $params, SeedDMS_NotificationService::RECV_NOTIFICATION);
                    }
                }
            }
            print 'Email Sent!';
        } else {
            print 'Do Not have Expired Documents';
        }
    } catch (\Throwable $th) {
        print 'Something Went Wrong!!!';
    }


