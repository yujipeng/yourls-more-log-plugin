Title of your plugin
====================

more-log-info Plugin for [YOURLS](http://yourls.org) `tested on version 1.7.4`. 


Description
-----------
插件主要用于增加log表字段，已经增加相应的统计功能

Installation
------------
1. In `/user/plugins`, create a new folder named `more-log-info`.
2. Drop these files in that directory.
3. Go to the Plugins administration page ( *eg* `http://sho.rt/admin/plugins.php` ) and activate the plugin.
4. Have fun!

License
-------
Free software. Do whatever the hell you want with it.
This Plugin is released under the MIT license.

One more thing
--------------

增加配置文件在 user/config.php 中，举例如下：

```
$more_log_info_column_list = [
    'tag'   => ' ADD `tag` varchar(255) not null default "" comment "记录位置标签" ',
    'cid'   => ' ADD `cid` varchar(255) not null default "" comment "记录渠道来源" ',
    'csr_id'   => ' ADD `csr_id` varchar(255) not null default "" comment "记录唯一用户标识" ',
];
```
以上是增加的字段配置，如果是使用统计功能，需要修改插件内容

api 增加统计相关接口功能   
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


