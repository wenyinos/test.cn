<?php
/**
 * IP API 配置文件
 * 部署后修改 IP_API_URL 为实际的 API 地址
 */

// IP API 服务地址
if (!defined('IP_API_URL')) {
    define('IP_API_URL', 'http://host_api.snam.cn/ip_api/');
}

// API 超时时间（秒）
if (!defined('IP_API_TIMEOUT')) {
    define('IP_API_TIMEOUT', 2);
}

// 是否启用 IP 归属地查询
if (!defined('IP_LOCATION_ENABLED')) {
    define('IP_LOCATION_ENABLED', true);
}
