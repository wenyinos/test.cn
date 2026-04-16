<?php
/**
 * IP API 配置文件
 * 部署后修改 IP_API_URL 为实际的 API 地址
 */

// 主 API 服务地址
if (!defined('IP_API_URL')) {
    define('IP_API_URL', 'http://host_api.snam.cn/ip_api/');
}

// 主 API 超时时间（秒）
if (!defined('IP_API_TIMEOUT')) {
    define('IP_API_TIMEOUT', 2);
}

// 第三方备用 API（ip-api.com，免费 45次/分钟，返回省市）
if (!defined('IP_API_FALLBACK_URL')) {
    define('IP_API_FALLBACK_URL', 'http://ip-api.com/json/');
}

// 备用 API 超时时间（秒）
if (!defined('IP_API_FALLBACK_TIMEOUT')) {
    define('IP_API_FALLBACK_TIMEOUT', 3);
}

// 是否启用 IP 归属地查询
if (!defined('IP_LOCATION_ENABLED')) {
    define('IP_LOCATION_ENABLED', true);
}
