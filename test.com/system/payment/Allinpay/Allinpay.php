<?php
require('php_rsa.php');
require('phpseclib/Crypt/RSA.php');
require('phpseclib/File/X509.php');
require('phpseclib/Math/BigInteger.php');

class Allinpay {
    // 请求key密钥
    protected $merchantId;
    protected $merchantKey;
    private $x509;
    private $rsa;
    private $cert;
    private $pubkey;

    public function __construct($merchantId,$merchantKey){
        $this->merchantId = $merchantId;
        $this->merchantKey = $merchantKey;
        $this->x509 = new File_X509();
        $this->rsa = new Crypt_RSA();
    }

    public function rsa_Verify($bufSignSrc,$signMsg){
        $certfile = file_get_contents('TLCert-prod.cer');
        $cert = $this->x509->loadX509($certfile);
        $pubkey = $this->x509->getPublicKey();
        $this->rsa->loadKey($pubkey); // public key
        $this->rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        $verifyResult =  $this->rsa->verify($bufSignSrc, base64_decode(trim($signMsg)));
        return $verifyResult;
    }

    public function md5_Verify($bufSignSrc,$signMsg){
        $signMsg_a = strtoupper(md5($bufSignSrc.'&'));
        if($signMsg==$signMsg_a){
            $verifyResult = '';
        }
        return $verifyResult;
    }

    public function GetSign(array $query){

        $inputCharset = $query['inputCharset'];
        $pickupUrl = $query['pickupUrl'];
        $receiveUrl = $query['receiveUrl'];
        $version = $query['version'];
        $language = $query['language'];
        $signType = $query['signType'];
        $merchantId = $this->merchantId;
        $payerName = $query['payerName'];
        $payerEmail = $query['payerEmail'];
        $payerTelephone = $query['payerTelephone'];

        $pid = $query['pid'];
        $orderNo = $query['orderNo'];
        $orderAmount = $query['orderAmount'];
        $orderCurrency = $query['orderCurrency'];
        $orderDatetime = $query['orderDatetime'];
        $orderExpireDatetime = $query['orderExpireDatetime'];
        $productName = $query['productName'];
        $productPrice = $query['productPrice'];
        $productNum = $query['productNum'];
        $productId = $query['productId'];
        $productDesc = $query['productDesc'];
        $ext1 = $query['ext1'];
        $ext2 = $query['ext2'];
        $customsExt = $query['ext2'];
        $extTL = $query['extTL'];
        $payType = $query['payType'];
        $issuerId = $query['issuerId'];
        $pan = $query['pan'];
        $tradeNature = $query['tradeNature'];

        // 生成签名字符串。
        $bufSignSrc="";
        if($inputCharset != "")
            $bufSignSrc=$bufSignSrc."inputCharset=".$inputCharset."&";
        if($pickupUrl != "")
            $bufSignSrc=$bufSignSrc."pickupUrl=".$pickupUrl."&";
        if($receiveUrl != "")
            $bufSignSrc=$bufSignSrc."receiveUrl=".$receiveUrl."&";
        if($version != "")
            $bufSignSrc=$bufSignSrc."version=".$version."&";
        if($language != "")
            $bufSignSrc=$bufSignSrc."language=".$language."&";
        if($signType != "")
            $bufSignSrc=$bufSignSrc."signType=".$signType."&";
        if($merchantId != "")
            $bufSignSrc=$bufSignSrc."merchantId=".$this->merchantId."&";
        if($payerName != "")
            $bufSignSrc=$bufSignSrc."payerName=".$payerName."&";
        if($payerEmail != "")
            $bufSignSrc=$bufSignSrc."payerEmail=".$payerEmail."&";
        if($payerTelephone != "")
            $bufSignSrc=$bufSignSrc."payerTelephone=".$payerTelephone."&";

        if($pid != "")
            $bufSignSrc=$bufSignSrc."pid=".$pid."&";
        if($orderNo != "")
            $bufSignSrc=$bufSignSrc."orderNo=".$orderNo."&";
        if($orderAmount != "")
            $bufSignSrc=$bufSignSrc."orderAmount=".$orderAmount."&";
        if($orderCurrency != "")
            $bufSignSrc=$bufSignSrc."orderCurrency=".$orderCurrency."&";
        if($orderDatetime != "")
            $bufSignSrc=$bufSignSrc."orderDatetime=".$orderDatetime."&";
        if($orderExpireDatetime != "")
            $bufSignSrc=$bufSignSrc."orderExpireDatetime=".$orderExpireDatetime."&";
        if($productName != "")
            $bufSignSrc=$bufSignSrc."productName=".$productName."&";
        if($productPrice != "")
            $bufSignSrc=$bufSignSrc."productPrice=".$productPrice."&";
        if($productNum != "")
            $bufSignSrc=$bufSignSrc."productNum=".$productNum."&";
        if($productId != "")
            $bufSignSrc=$bufSignSrc."productId=".$productId."&";
        if($productDesc != "")
            $bufSignSrc=$bufSignSrc."productDesc=".$productDesc."&";
        if($ext1 != "")
            $bufSignSrc=$bufSignSrc."ext1=".$ext1."&";

        //如果海关扩展字段不为空，需要做个MD5填写到ext2里
        if($ext2 == "" && $customsExt != "")
        {
            $ext2 = strtoupper(md5($customsExt));
            $bufSignSrc=$bufSignSrc."ext2=".$ext2."&";
        }
        else if($ext2 != "")
        {
            $bufSignSrc=$bufSignSrc."ext2=".$ext2."&";
        }

        if($extTL != "")
            $bufSignSrc=$bufSignSrc."extTL".$extTL."&";
        if($payType != "")
            $bufSignSrc=$bufSignSrc."payType=".$payType."&";
        if($issuerId != "")
            $bufSignSrc=$bufSignSrc."issuerId=".$issuerId."&";
        if($pan != "")
            $bufSignSrc=$bufSignSrc."pan=".$pan."&";
        if($tradeNature != "")
            $bufSignSrc=$bufSignSrc."tradeNature=".$tradeNature."&";
        $bufSignSrc=$bufSignSrc."key=".$this->merchantKey; //key为MD5密钥，密钥是在通联支付网关商户服务网站上设置。

       //签名，设为signMsg字段值。
        $signMsg = strtoupper(md5($bufSignSrc));
        return $signMsg;
    }

    public function VerifySign(array $query){
        $merchantId=$query["merchantId"];
        $version=$query['version'];
        $language=$query['language'];
        $signType=$query['signType'];
        $payType=$query['payType'];
        $issuerId=$query['issuerId'];
        $paymentOrderId=$query['paymentOrderId'];
        $orderNo=$query['orderNo'];
        $orderDatetime=$query['orderDatetime'];
        $orderAmount=$query['orderAmount'];
        $payDatetime=$query['payDatetime'];
        $payAmount=$query['payAmount'];
        $ext1=$query['ext1'];
        $ext2=$query['ext2'];
        $payResult=$query['payResult'];
        $errorCode=$query['errorCode'];
        $returnDatetime=$query['returnDatetime'];
        $signMsg=$query["signMsg"];


        $bufSignSrc="";
        if($merchantId != "")
            $bufSignSrc=$bufSignSrc."merchantId=".$merchantId."&";
        if($version != "")
            $bufSignSrc=$bufSignSrc."version=".$version."&";
        if($language != "")
            $bufSignSrc=$bufSignSrc."language=".$language."&";
        if($signType != "")
            $bufSignSrc=$bufSignSrc."signType=".$signType."&";
        if($payType != "")
            $bufSignSrc=$bufSignSrc."payType=".$payType."&";
        if($issuerId != "")
            $bufSignSrc=$bufSignSrc."issuerId=".$issuerId."&";
        if($paymentOrderId != "")
            $bufSignSrc=$bufSignSrc."paymentOrderId=".$paymentOrderId."&";
        if($orderNo != "")
            $bufSignSrc=$bufSignSrc."orderNo=".$orderNo."&";
        if($orderDatetime != "")
            $bufSignSrc=$bufSignSrc."orderDatetime=".$orderDatetime."&";
        if($orderAmount != "")
            $bufSignSrc=$bufSignSrc."orderAmount=".$orderAmount."&";
        if($payDatetime != "")
            $bufSignSrc=$bufSignSrc."payDatetime=".$payDatetime."&";
        if($payAmount != "")
            $bufSignSrc=$bufSignSrc."payAmount=".$payAmount."&";
        if($ext1 != "")
            $bufSignSrc=$bufSignSrc."ext1=".$ext1."&";
        if($ext2 != "")
            $bufSignSrc=$bufSignSrc."ext2=".$ext2."&";
        if($payResult != "")
            $bufSignSrc=$bufSignSrc."payResult=".$payResult."&";
        if($errorCode != "")
            $bufSignSrc=$bufSignSrc."errorCode=".$errorCode."&";
        if($returnDatetime != "")
            $bufSignSrc=$bufSignSrc."returnDatetime=".$returnDatetime;

        return $bufSignSrc;
    }
}


