<?php

require 'Slim/Slim.php';
require 'autoload.php';
require 'config.php';

use OSS\OssClient;
use OSS\Core\OssException;
\Slim\Slim::registerAutoloader();
class Common
{
    const endpoint = ENDPOINT;
    const accessKeyId = ACCESS_ID;
    const accessKeySecret = ACCESS_KEY;
    const bucket = BUCKET;

    /**
     * 根据Config配置，得到一个OssClient实例
     *
     * @return OssClient 一个OssClient实例
     */
    public static function getOssClient()
    {
        try {
            $ossClient = new OssClient(self::accessKeyId, self::accessKeySecret, self::endpoint, false);
        } catch (OssException $e) {
            printf(__FUNCTION__ . "creating OssClient instance: FAILED\n");
            printf($e->getMessage() . "\n");
            return null;
        }
        return $ossClient;
    }

    public static function getBucketName()
    {
        return self::bucket;
    }

    /**
     * 工具方法，创建一个存储空间，如果发生异常直接exit
     */
    public static function listObjects($bucket,$prefix='')
	{
		$ossClient = self::getOssClient();
	    if (is_null($ossClient)) exit(1);
	    $prefix = $prefix;
	    $delimiter = '/';
	    $nextMarker = '';
	    $maxkeys = 1000;
	    $options = array(
	        'delimiter' => $delimiter,
	        'prefix' => $prefix,
	        'max-keys' => $maxkeys,
	        'marker' => $nextMarker,
	    );
	    try {
	        $listObjectInfo = $ossClient->listObjects($bucket, $options);
	    } catch (OssException $e) {
	        return $e->getMessage();
	    }
	    $objectList = $listObjectInfo->getObjectList(); // 文件列表
	    $prefixList = $listObjectInfo->getPrefixList(); // 目录列表
	    if (!empty($objectList)) {
	        foreach ($objectList as $objectInfo) {
	            $arr['file'][]=$objectInfo->getKey();
	        }
	    }
	    if (!empty($prefixList)) {
	        foreach ($prefixList as $prefixInfo) {
	            $arr['dir'][]=$prefixInfo->getPrefix();
	        }
	    }
	    $arr['address']=BUCKET.'.'.ENDPOINT;
	    $json=serialize($arr);
	    return $json;
	}
}

$app = new \Slim\Slim();

$app->get('/',function (){
	$data=Common::listObjects(BUCKET);
   	$data=unserialize($data);
   	$data=json_encode($data);
   	echo $data;
});
$app->get('/dir/:dirs',function ($dirs){
	$data=Common::listObjects(BUCKET,$dirs.'/');
   	$data=unserialize($data);
   	$data=json_encode($data);
   	echo $data;
});
$app->run();
