<?php
/**
 * 壁纸获取
 */
 
set_time_limit(0);
date_default_timezone_set('Asia/Shanghai');

class SetWallPaper
{
	/**
	 * 获取当天 Bing 首页图
	 */
    public function bingPic()
    {
        $res = $this->request('http://cn.bing.com/HPImageArchive.aspx?idx=0&n=1');
        $xml = simplexml_load_string($res);
        $url = $xml->xpath('/images/image/url');
        if(!empty($url)){
            $pic_file = $url[0];
            $pic_file = str_replace('1366x768', '1920x1080', $pic_file);
            $pic_file = 'https://www.bing.com'. $pic_file;
            $ext = $this->getExt($pic_file);
            $stream = file_get_contents($pic_file);
            $filename = 'bing_'. date('YmdHis'). mt_rand(1000, 9999). $ext;
            file_put_contents('./bing/'. $filename, $stream);
        }
    }

	/**
	 * 获取 collection/winter 的十五张横向图
	 */
    public function unsplashPic()
    {
		// access_key 在 unsplash 开发者中心获取
        $auth = 'Client-ID access_key';
        $url = 'https://api.unsplash.com/search/photos?page=1&per_page=15&query=wallpaper&orientation=landscape&collections=3178572';
        $header = array(
            'Authorization: '. $auth
        );
        $result = $this->request($url, $header);
        $body = json_decode($result);
        if(property_exists($body, 'errors')){
            var_dump($result);
            return;
        }
        $images = $body->results;
        foreach ($images as $_img){
            $stream = file_get_contents($_img->urls->raw);
            $filename = 'unsplash_'. date('YmdHis'). mt_rand(1000, 9999). '.jpg';
            file_put_contents('./unsplash/'. $filename, $stream);
        }
    }

	/**
	 * 由已知文件名获取文件后缀
	 * @param string $filename 文件名
	 * @return string
	 */
    protected function getExt($filename)
    {
        if(empty($filename)){
            return '';
        }
        $ext_index = strrpos($filename, '.');
        if($ext_index === false){
            return '';
        }
	preg_match('/\.\w+/', mb_substr($filename, $ext_index), $matches);
        if(empty($matches)) {
            return '';
        }
        return $matches[0];
    }

	/**
	 * 请求
	 * @param string $url 请求地址
	 * @param array $headers 请求头
	 * @return string
	 */
    protected function request($url, $headers = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

$app = new SetWallPaper();
$app->bingPic();
$app->unsplashPic();
exit;
