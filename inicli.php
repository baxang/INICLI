#!/usr/bin/php
<?php
/**
 * INICLI is a command-line INIpay interface. Speak it like 'ini-klie'.
 *
 * Version : very first
 * Author  : baxang (sh@baxang.com)
 *
 * INIpay50 library should be placed in vendor/INIpay50 as it was given.
 * Otherwise you need to set your own INIFactory::$INIPAY_ROOT.
 *
 * Usage: ./inicli.php --command=[chkfake|securepay|cancel] --mid=[given mid] --admin=[given admin id] \
 *                     --params='[paramaters in JSON format]'
 */

$required_extensions = array('mcrypt', 'sockets', 'xml', 'openssl', 'mbstring' );
foreach ($required_extensions as $ext_name) {
    if ( !extension_loaded($ext_name) ) {
        echo "{$ext_name} extension not loaded.\n";
        exit(1);
    }
}

$opts = getopt(
    '',
    array('mid:', 'admin:', 'params:', 'command:')
);

$commands = array('chkfake', 'securepay', 'cancel');
if (false == in_array($opts['command'], $commands)) {
    echo "Invalid command given: {$opts['command']}\n";
    exit(1);
}

INIFactory::$INIPAY_ROOT = dirname(__FILE__) . '/vendor/INIpay50/';
INIFactory::$ADMIN       = $opts['admin'];
INIFactory::$MID         = $opts['mid'];
ob_start();
require_once INIFactory::$INIPAY_ROOT.'/libs/INILib.php';

$params = (array) json_decode($opts['params']);
$result = call_user_func_array( array('INIFactory', $opts['command']), array($params) );

ob_clean(); // eliminates a new line in INILib.php
echo $result;
exit(0);

// Command-line INIpay
class INIFactory {
    public static $INIPAY_ROOT = '/home/ts/www/INIpay50';
    public static $DEBUG       = 'true';
    public static $ADMIN       = '1111';
    public static $MID         = 'INIpayTest';
    public static $PGID        = '';

    static public function chkfake($params = array()) {
        $params_keys = array('price', 'quotabase');
        $result_keys = array('ResultCode', 'ResultMsg', 'ResultErrorCode', 'rn', 'enctype', 'encfield', 'certid');

        $inipay = self::get_instance();
        $inipay->SetField('type',       'chkfake');
        $inipay->SetField('enctype',    'asym');
        $inipay->SetField('checkopt',   'false');
        $inipay->SetField('nointerest', 'no');

        self::assign_params($inipay, $params, $params_keys);

        $inipay->startAction();

        $result = self::sanitize_result($inipay, $result_keys);

        return json_encode($result);
    }

    static public function securepay($params = array()) {
        $params_keys = array(
            'buyername',
            'buyertel',
            'buyeremail',

            'goodname',
            'price',
            'currency',

            'paymethod',
            'encrypted',
            'sessionkey',
            'enctype',
            'rn',

            'uid',
            'url',
        );
        $result_keys     = array(
            'ResultCode', 'ResultMsg', 'ResultErrorCode',

            'PayMethod', // 지불방법
            'MOID',      // 상점주문번호
            'TotPrice',  // 결제완료금액
            'TID',       // 거래번호

            // 신용카드 결제 결과 데이터
            'ApplDate',      // 이니시스 승인날짜
            'ApplTime',      // 이니시스 승인시각
            'ApplNum',       // 신용카드 승인번호
            'CARD_Quota',    // 할부기간
            'CARD_Interest', // 무이자할부 여부: 1이면 무이자 할부
            'CARD_Code',     // 신용카드사 코드
            'CARD_BankCode', // 카드발급사 코드
            'CARD_AuthType', // 본인인증 수행 여부: 00이면 수행
            'EventCode',     // 각종 이벤트 적용 여부
        );
        $inipay = self::get_instance();
        $inipay->SetField('type',    'securepay');
        $inipay->SetField('subpgip', '203.238.3.10');
        $inipay->SetField('pgid',    'INIphp'.self::$PGID);

        self::assign_params($inipay, $params, $params_keys);

        $inipay->startAction();

        $result = self::sanitize_result($inipay, $result_keys);

        return json_encode($result);
    }

    static public function cancel($tid, $code = 1, $message = '') {
        return false;
        $inipay = self::get_instance();
        $inipay->SetField('type',       'cancel');
        $inipay->SetField('tid',        $tid);
        $inipay->SetField('cancelmsg',  $message);
        $inipay->SetField('cancelcode', $code);	//취소사유코드
        $inipay->startAction();

        return $inipay;
    }

    static private function get_instance() {
        $inipay = new INIpay50;
        $inipay->SetField('inipayhome', self::$INIPAY_ROOT);
        $inipay->SetField('debug',      self::$DEBUG);
        $inipay->SetField('admin',      self::$ADMIN);
        $inipay->SetField('mid',        self::$MID);

        return $inipay;
    }

    static private function assign_params($payobject, $params, $keys) {
        foreach($keys as $k) {
            $payobject->SetField($k, mb_convert_encoding( $params[$k], 'EUC-KR', 'UTF-8' ));
        }
        if (isset($params['paymethod'])) {
            $payobject->SetField('pgid', 'INIphp' . self::get_pgid($params['paymethod']));
        }
    }

    static private function sanitize_result($payobject, $keys) {
        $result = array();
        foreach($keys as $k) {
            $result[$k] = mb_convert_encoding( trim($payobject->GetResult($k)), 'UTF-8', 'EUC-KR' );
        }
        return $result;
    }

    static private function get_pgid($paymethod) {
        $pgid = $paymethod;
        switch($paymethod){
            case(Card): 			// 신용카드
                $pgid = "CARD"; break;
            case(Account): 		// 은행 계좌 이체
                $pgid = "ACCT"; break;
            case(DirectBank): // 실시간 계좌 이체
                $pgid = "DBNK"; break;
            case(OCBPoint): 	// OCB
                $pgid = "OCBP"; break;
            case(VCard): 			// ISP 결제
                $pgid = "ISP_"; break;
            case(HPP): 				// 휴대폰 결제
                $pgid = "HPP_"; break;
            case(ArsBill): 		// 700 전화결제
                $pgid = "ARSB"; break;
            case(PhoneBill): 	// PhoneBill 결제(받는 전화)
                $pgid = "PHNB"; break;
            case(Ars1588Bill):// 1588 전화결제
                $pgid = "1588"; break;
            case(VBank):  		// 가상계좌 이체
                $pgid = "VBNK"; break;
            case(Culture):  	// 문화상품권 결제
                $pgid = "CULT"; break;
            case(CMS): 				// CMS 결제
                $pgid = "CMS_"; break;
            case(AUTH): 			// 신용카드 유효성 검사
                $pgid = "AUTH"; break;
            case(INIcard): 		// 네티머니 결제
                $pgid = "INIC"; break;
            case(MDX):  			// 몬덱스카드
                $pgid = "MDX_"; break;
        }
        return $pgid;
    }
}