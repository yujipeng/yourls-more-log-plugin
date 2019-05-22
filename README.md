Title of your plugin
====================

more-log-info Plugin for [YOURLS](http://yourls.org) `tested on version 1.7.4`. 

more-log-statistic Plugin for [YOURLS](http://yourls.org) `tested on version 1.7.4`. 


Description
-----------
插件主要用于增加log表字段，已经增加相应的统计功能

Installation
------------
1. 下载当前目录，放置到 user/plugin/ 目录之下.
2. 在插件管理中进行激活 

License
-------
Free software. Do whatever the hell you want with it.

This Plugin is released under the MIT license.

One more thing
--------------

增加配置文件在 user/config.php 中，举例如下：

```
// more-log-info 新增字段的配置
$more_log_info_column_list = [
    'tag'   => ' ADD `tag` varchar(255) not null default "" comment "记录位置标签" ',
    'cid'   => ' ADD `cid` varchar(255) not null default "" comment "记录渠道来源" ',
    'csr_id'   => ' ADD `csr_id` varchar(255) not null default "" comment "记录唯一用户标识" ',
];

// more-log-statistic 可统计字段的配置
$more_log_statistic_column_list = [
    'tag'  , 'cid'  , 'csr_id'   ,
];
```

api 增加统计相关接口参数
```
action = statistic ; 必传
type = shorturl ; 必选 统计类型 可选值  shorturl , tag , cid
date = 2019-01-01 ; 非必选 ，统计日期，格式 Y-m-d
tag = xxxx ; 非必选，查询条件之一
cid = xxxx ; 非必选，查询条件之一
rows = 10 ; 非必选，默认为10
distinct = 0 ; 非必选，默认为0 可选值 0 / 1 是否按照用户排重
format = json ; 非必选，可选项 jsonp json xml simple
```


