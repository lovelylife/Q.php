<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Original Author <author@example.com>                        |
// |          Your Name <you@example.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id:$
//////////////////////////////////////////////////////////
class smtp {
    private $mailcfg = array();
    private $error_msg = '';
    function __construct($mailcfg) {
        $this->mailcfg = $mailcfg;
    }
    function send($mail) {
        $mailcfg = $this->mailcfg;
        if (!$fp = fsockopen($mailcfg['server'], $mailcfg['port'], $errno, $errstr, 30)) {
            return $this->error("($mailcfg[server]:$mailcfg[port]) CONNECT - Unable to connect to the SMTP server, please check your \"mail_config.php\".");
        }
        stream_set_blocking($fp, true);
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != '220') {
            return $this->error("$mailcfg[server]:$mailcfg[port] CONNECT - $lastmessage");
        }
        fputs($fp, ($mailcfg['auth'] ? 'EHLO' : 'HELO') . " " . $mailcfg['auth_username'] . "\r\n");
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != 220 && substr($lastmessage, 0, 3) != 250) {
            return $this->error("($mailcfg[server]:$mailcfg[port]) HELO/EHLO - $lastmessage");
        }
        while (1) {
            if (substr($lastmessage, 3, 1) != '-' || empty($lastmessage)) {
                break;
            }
            $lastmessage = fgets($fp, 512);
        }
        if ($mailcfg['auth']) {
            fputs($fp, "AUTH LOGIN\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 334) {
                return $this->error("($mailcfg[server]:$mailcfg[port]) AUTH LOGIN - $lastmessage");
            }
            fputs($fp, base64_encode($mailcfg['auth_username']) . "\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 334) {
                return $this->error("($mailcfg[server]:$mailcfg[port]) USERNAME - $lastmessage");
            }
            fputs($fp, base64_encode($mailcfg['auth_password']) . "\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 235) {
                return $this->error("($mailcfg[server]:$mailcfg[port]) PASSWORD - $lastmessage");
            }
            $email_from = $mailcfg['from'];
        }
        fputs($fp, "MAIL FROM: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from) . ">\r\n");
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != 250) {
            fputs($fp, "MAIL FROM: <" . preg_replace("/.*\<(.+?)\>.*/", "\\1", $email_from) . ">\r\n");
            $lastmessage = fgets($fp, 512);
            if (substr($lastmessage, 0, 3) != 250) {
                return $this->error("($mailcfg[server]:$mailcfg[port]) MAIL FROM - $lastmessage");
            }
        }
        $email_to = $mail['to'];
        foreach (explode(',', $email_to) as $touser) {
            $touser = trim($touser);
            if ($touser) {
                fputs($fp, "RCPT TO: <$touser>\r\n");
                $lastmessage = fgets($fp, 512);
                if (substr($lastmessage, 0, 3) != 250) {
                    fputs($fp, "RCPT TO: <$touser>\r\n");
                    $lastmessage = fgets($fp, 512);
                    return $this->error("($mailcfg[server]:$mailcfg[port]) RCPT TO - $lastmessage");
                }
            }
        }
        fputs($fp, "DATA\r\n");
        $lastmessage = fgets($fp, 512);
        if (substr($lastmessage, 0, 3) != 354) {
            return $this->error("($mailcfg[server]:$mailcfg[port]) DATA - $lastmessage");
        }
        $str_header = "MIME-Version: 1.0\r\n";
        $str_header.= "Content-Type: text/html; charset=UTF-8\r\n";
        $str = $str_header . "To: $email_to\r\nFrom: $email_from\r\nSubject: " . $mail['subject'] . "\r\n\r\n" . $mail['content'] . "\r\n.\r\n";
        fputs($fp, $str);
        fputs($fp, "QUIT\r\n");
        return true;
    }
    function get_error() {
        return $this->error_msg;
    }
    function error($msg) {
        $this->error_msg.= $msg;
        return false;
    }
}
/*
$mailcfg['server'] = 'smtp.exmail.qq.com'; 
$mailcfg['port'] = '25'; 

$mailcfg['auth'] = 1; 
$mailcfg['from'] = 'no-reply <no-reply@wayixia.com>'; 

$mailcfg['auth_username'] = 'no-reply@wayixia.com';
$mailcfg['auth_password'] = ''; 
$stmp=new smtp($mailcfg); 

$mail=array(
  'to'=>'life.qm@gmail.com',
  'subject'=>'测试标题',
  'content'=>'邮件内容<a href="http://www.php.net">PHP面向对象</a>'); 

if(!$stmp->send($mail)){ 
  echo $stmp->get_error(); 
} else { 
  echo 'mail succ!'; 
}
*/
?>
