<?php
/**
 * 生成二维码
 * User: qubojie
 * Date: 2018/6/29
 * Time: 上午11:41
 */
namespace app\common\controller;

use Endroid\QrCode\QrCode;
use think\Controller;

class MakeQrCode extends Controller
{
    public function make($val = array())
    {
        $qrCode = new QrCode();

        $val = array(
            "prefix" => 'j',
            "value"  => '测试券'
        );

        $val = json_encode($val);

        $qrCode->setText($val);
        $qrCode->setSize(300);
        $qrCode->setErrorCorrectionLevel('high');
        $qrCode->setForegroundColor(array('r' => 0,'g' => 0, 'b' => 0,'a' => 0));
        $qrCode->setBackgroundColor(array('r' => 255,'g' => 255, 'b' => 255,'a' => 0));
        $qrCode->setLabel('屈博杰');
        $qrCode->setLabelFontSize(16);
        $qrCode->writeFile('./qrcode/123.png');
    }

    public function createQrCode($savePath,$qrData = 'PHP QR Code :)', $qrLevel = 'L', $qrSize = 4, $savePrefix = 'qrcode')
    {
        if (!isset($savePath)) return '';
        //设置生成png图片的路径
        $PNG_TEMP_DIR = $savePath;
        //检测并创建生成文件夹
        if (!file_exists($PNG_TEMP_DIR)) {
            mkdir($PNG_TEMP_DIR);
        }
        $filename = $PNG_TEMP_DIR . 'test.png';
        $errorCorrectionLevel = 'L';
        if (isset($qrLevel) && in_array($qrLevel, ['L', 'M', 'Q', 'H'])) {
            $errorCorrectionLevel = $qrLevel;
        }
        $matrixPointSize = 4;
        if (isset($qrSize)) {
            $matrixPointSize = min(max((int)$qrSize, 1), 10);
        }
        if (isset($qrData)) {
            if (trim($qrData) == '') {
                die('data cannot be empty!');
            }
            //生成文件名 文件路径+图片名字前缀+md5(名称)+.png
            $filename = $PNG_TEMP_DIR . $savePrefix . md5($qrData . '|' . $errorCorrectionLevel . '|' . $matrixPointSize) . '.png';
            //开始生成
            \PHPQRCode\QRcode::png($qrData, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

        }else{
            \PHPQRCode\QRcode::png('PHP QR Code :)',$filename, $errorCorrectionLevel, $matrixPointSize, 2);
        }

        if (file_exists($PNG_TEMP_DIR . basename($filename)))
            return basename($filename);
        else
            return FALSE;
    }
}
