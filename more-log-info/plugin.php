<?php
/*
Plugin Name: more-log-info
Plugin URI: http://github.com/your_name/your_plugin
Description: One line description of your plugin
Version: 1.0
Author: jeepyu
Author URI: http://your-site-if-any/
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

/*

 Your code goes here.
 
 Suggested read:
 https://github.com/YOURLS/YOURLS/wiki/Coding-Standards
 https://github.com/YOURLS/YOURLS/wiki#for-developpers
 https://github.com/YOURLS/YOURLS/wiki/Plugin-List#get-your-plugin-listed-here
 
 Have fun!
 
 */

// 定义 plugin 的名称，也是加载目录名称 more_log_info;
// 定义新增字段 在 user/config.php 中添加
// $more_log_info_column_list = [
//     'tag'   => ' ADD `tag` varchar(255) not null default "" comment "记录位置标签" ',
//     'cid'   => ' ADD `cid` varchar(255) not null default "" comment "记录渠道来源" ',
//     'csr_id'   => ' ADD `csr_id` varchar(255) not null default "" comment "记录唯一用户标识" ',
// ];
// Activation: add the column to the Log table if not added
yourls_add_action( 'activated_more-log-info/plugin.php', 'more_log_info_activated' );
function more_log_info_activated() {
	global $ydb; 
    
	$table = YOURLS_DB_TABLE_LOG;
	$version = version_compare(YOURLS_VERSION, '1.7.3') >= 0;

	if ($version) {
		$sql = "DESCRIBE `$table`";
		$results = $ydb->fetchObjects($sql);
	} else {
		$results = $ydb->get_results("DESCRIBE $table");
    }
    $current_column_list = [];
    foreach($results as $r) {
        $current_column_list[] = $r->Field;
	}
    global $more_log_info_column_list;
    $column_list_keys = array_keys($more_log_info_column_list);
    $unactivated_list = array_diff($column_list_keys, $current_column_list);

    if(!$unactivated_list) return true;
    foreach($unactivated_list as $col){
        $activated_list[] = $more_log_info_column_list[$col];
    }
    $activated_sql = implode(',', $activated_list);
	if($activated_sql) {
		if ($version) {
            $sql = "ALTER TABLE `$table` $activated_sql";
			$insert = $ydb->fetchAffected($sql);
		} else {
			$insert = $ydb->query("ALTER TABLE `$table` $activated_sql");

		}
    }
}
// 记录日志，适用于上面增加的字段
yourls_add_filter( 'shunt_log_redirect', 'more_log_info_log_redirect' );
function more_log_info_log_redirect($bool, $keyword) {
    $bool = true;
	if ( !yourls_do_log_redirect() )
		return true;

	global $ydb;
	$table = YOURLS_DB_TABLE_LOG;
    $binds = array(
        'click_time'    => date( 'Y-m-d H:i:s' ),
        'shorturl'      => yourls_sanitize_string($keyword),
        'referrer'      => isset($_SERVER['HTTP_REFERER']) ? yourls_sanitize_url_safe($_SERVER['HTTP_REFERER']) : 'direct',
        'user_agent'    => yourls_get_user_agent(),
        'ip_address'    => yourls_get_IP(),
        'country_code'  => yourls_geo_ip_to_countrycode($ip),
    );
    // 历史原因增加的冗余
    if(!$_REQUEST['csr_id'] && $_REQUEST['csrId']) $_REQUEST['csr_id'] = $_REQUEST['csrId'];
    // 增加字段处理
    global $more_log_info_column_list;
    foreach($more_log_info_column_list as $key => $val){
        $binds[$key] = $_REQUEST[$key] ? $_REQUEST[$key] : '';
    }
    $column_string = implode(',', array_keys($binds));
    $values_string = ':'.implode(', :', array_keys($binds));

    $sql = "INSERT INTO `$table` ( $column_string ) VALUES ( $values_string )";
    return $ydb->fetchAffected($sql, $binds );
}



