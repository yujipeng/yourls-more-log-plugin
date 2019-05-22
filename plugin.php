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
	if(!$activated_sql) {
		if ($version) {
			$sql = "ALTER TABLE `$table` $activated_sql";
			$insert = $ydb->fetchAffected($sql);
		} else {
			$ydb->query("ALTER TABLE `$table` $activated_sql");

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
    if(!$_GET['csr_id'] && $_GET['csrId']) $_GET['csr_id'] = $_GET['csrId'];
    // 增加字段处理
    global $more_log_info_column_list;
    foreach($more_log_info_column_list as $key => $val){
        $binds[$key] = $_GET[$key] ? $_POST[$key] : '';
    }
    $column_string = implode(',', array_keys($binds));
    $values_string = ':'.implode(', :', $array_keys[$binds]);

    return $ydb->fetchAffected("INSERT INTO `$table` ( $column_string ) VALUES ( $values_string )", $binds );
}

// 增加api action=statistic
yourls_add_filter( 'api_action_statistic', 'more_log_info_statistic' );
function more_log_info_statistic() {
    $type = $_REQUEST['type'];
    if($_REQUEST['type'] == 'shorturl') {
        $result = more_log_info_statistic_shorturl_specific_day();
    } elseif ($_REQUEST['type'] == 'cid') {
        $result = more_log_info_statistic_cid_specific_day();
    } elseif ($_REQUEST['type'] == 'tag') {
        $result = more_log_info_statistic_tag_specific_day();
    }
    return $result;
}


/**
 * 获取指定日期的统计数据 统计短连接的点击PV/UV
 */
function more_log_info_statistic_shorturl_specific_day() {

    $params = more_log_info_statistic_where();

    $sql = "SELECT a.shorturl AS shorturl, {$params['count_column']}, b.title as title
        FROM " . YOURLS_DB_TABLE_LOG . " a, " . YOURLS_DB_TABLE_URL . " b
        WHERE a.shorturl = b.keyword
            AND click_time >= :from
            AND click_time <= :to {$params['where_string']}
        GROUP BY a.shorturl
        ORDER BY shorturl ASC
        LIMIT :rows;";

    global $ydb;
    $results = $ydb->fetchObjects( $sql, $params['binds'] );
    return $results;
}
/**
 * 获取指定日期的统计数据 统计tag的点击PV/UV
 */
function more_log_info_statistic_tag_specific_day() {
    
    $params = more_log_info_statistic_where();
    
    $sql = "SELECT tag, {$params['count_column']} FROM " . YOURLS_DB_TABLE_LOG . " 
        WHERE click_time >= :from AND click_time <= :to {$params['where_string']}
        GROUP BY tag ORDER BY tag ASC LIMIT :rows;";

    global $ydb;
    $results = $ydb->fetchObjects( $sql, $params['binds'] );
    return $results;
}
/**
 * 获取指定日期的统计数据 统计渠道cid的点击PV/UV
 */
function more_log_info_statistic_cid_specific_day() {

    $params = more_log_info_statistic_where();

    $sql = "SELECT tag, {$params['count_column']} FROM " . YOURLS_DB_TABLE_LOG . " 
        WHERE click_time >= :from AND click_time <= :to {$params['where_string']}
        GROUP BY cid ORDER BY cid ASC LIMIT :rows;";

    global $ydb;
    $results = $ydb->fetchObjects( $sql, $params['binds'] );
    return $results;
}

function more_log_info_statistic_where(){
    $distinct = (int)$_REQUEST['distinct'] ? true : false;
    if($distinct) {
        $count_column = ' COUNT(DISTINCT(csr_id)) AS clicks ';
    } else {
        $count_column = ' COUNT(*) AS clicks ';
    }
    
    $period = date('Y-m-d', $_REQUEST['date'] ? strtotime($_REQUEST['date']) : time());
    $from   = $period . ' 00:00:00';
    $to     = $period . ' 23:59:59';
    $binds = ['from' => $from, 'to' => $to];

    $where_string = '';
    $shorturl = $_REQUEST['shorturl'] ? : '';
    if($shorturl) {
        $binds['shorturl'] = $shorturl;
        $where_string = ' AND tag = :tag  ';
    }
    $tag = $_REQUEST['tag'] ? : '';
    if($tag) {
        $binds['tag'] = $tag;
        $where_string = ' AND tag = :tag  ';
    }
    $cid = $_REQUEST['cid'] ? : '';
    if($cid) {
        $binds['cid'] = $cid;
        $where_string .= ' AND cid = :cid ';
    }
    $rows = (int)$_REQUEST['rows'] ? : 10;
    $binds['rows'] = $rows;
    return [
        'count_column'  => $count_column,
        'where_string'  => $where_string,
        'binds'         => $binds,
    ];
}
