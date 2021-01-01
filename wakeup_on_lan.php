<?php
/**
 * 网络唤醒
 */
 
class WOL
{
    // 唤醒目标的 MAC 地址
    private $mac;
    // 唤醒目标的端口
    private $port;
    // 唤醒目标的ip地址
    private $ip;

    private $msg = [
        0 => '目标机器已经是唤醒的.',
        1 => '连接创建失败',
        2 => '连接配置失败',
        3 => '唤醒信号已发送!',
        4 => '唤醒信号发送失败!'
    ];

    /**
     * WOL constructor.
     * @param string $host 唤醒目标主机，可以是 ip 或域名
     * @param string $mac 唤醒目标 MAC 地址
     * @param string|int $port 端口
     */
    public function __construct($host, $mac, $port = 9)
    {
        $this->ip = $this->check_host($host);
        $this->mac = $mac;
        $this->port = $port;
    }

    public function wakeup()
    {
        // 如果设备已经是唤醒的就不做其它操作了
        if ($this->is_awake()) {
            return $this->get_error(0);
        }
        $final_mac = str_replace([':', '-'], '', $this->mac);
        $hw_addr = '';
        for ($i = 0; $i < strlen($final_mac); $i++) {
            $hw_addr .= chr(hexdec($final_mac[$i]));
        }
        $msg = chr(255) . chr(255) . chr(255) . chr(255) . chr(255) . chr(255);
        for ($n = 0; $n < 17; $n++) {
            $msg .= $hw_addr;
        }

        // 通过 UDP 发送数据包
        $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($s === false) {
            return $this->get_error(1) . $this->get_socket_error($s);
        }
        if (!socket_set_option($s, 1, 6, true)) {
            return $this->get_error(2). $this->get_socket_error($s);
        }
        $sendto = socket_sendto($s, $msg, strlen($msg), 0, $this->ip, $this->port);
        if ($sendto) {
            socket_close($s);
            return $this->get_error(3);
        }
        return $this->get_error(4). $this->get_socket_error($s);
    }

    private function is_awake()
    {
        $awake = @fsockopen($this->ip, 80, $errno, $errstr, 2);
        if ($awake) {
            fclose($awake);
        }
        return $awake;
    }

    private function get_error($code)
    {
        if (array_key_exists($code, $this->msg)) {
            return $this->msg[$code];
        }
        return '未知错误';
    }

    private function get_socket_error($socket)
    {
        $error_no = socket_last_error($socket);
        return ' ['. $error_no. ', '. socket_strerror($error_no). ']';
    }

    private function check_host($host)
    {
        // ipv4
        $isIpv4 = preg_match_all('/^((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$/', $host);
        // ipv6
        $isIpv6 = preg_match_all('^([\da-fA-F]{1,4}:){6}((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^::([\da-fA-F]{1,4}:){0,4}((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:):([\da-fA-F]{1,4}:){0,3}((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){2}:([\da-fA-F]{1,4}:){0,2}((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){3}:([\da-fA-F]{1,4}:){0,1}((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){4}:((25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(25[0-5]|2[0-4]\d|[01]?\d\d?)$|^([\da-fA-F]{1,4}:){7}[\da-fA-F]{1,4}$|^:((:[\da-fA-F]{1,4}){1,6}|:)$|^[\da-fA-F]{1,4}:((:[\da-fA-F]{1,4}){1,5}|:)$|^([\da-fA-F]{1,4}:){2}((:[\da-fA-F]{1,4}){1,4}|:)$|^([\da-fA-F]{1,4}:){3}((:[\da-fA-F]{1,4}){1,3}|:)$|^([\da-fA-F]{1,4}:){4}((:[\da-fA-F]{1,4}){1,2}|:)$|^([\da-fA-F]{1,4}:){5}:([\da-fA-F]{1,4})?$|^([\da-fA-F]{1,4}:){6}:$', $host);
        if ($isIpv6 || $isIpv4) {
            return $host;
        }
        $ip = gethostbyname($host);
        return $ip;
    }
}

$ip = '192.168.1.101';
$mac = 'xx-xx-xx-xx-xx-xx';
$app = new WOL($ip, $mac);
echo $app->wakeup();
