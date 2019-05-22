<?php
/*
Plugin Name: more-log-statistic
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

// 定义 plugin 的名称，也是加载目录名称 more_log_statistic;
// 定义新增字段 在 user/config.php 中添加
// $more_log_statistic_column_list = [
//     'tag' ,  'cid' ,  'csr_id'  
// ];
// 增加api action=statistic
yourls_add_filter( 'api_action_statistic', 'more_log_statistic' );
function more_log_statistic() {
    $type = $_REQUEST['type'] ? : 'shorturl';
    if($type == 'shorturl') {
        $result = more_log_statistic_shorturl_specific_day();
    } elseif(in_array($type , $more_log_statistic_column_list)) {
        $result = more_log_statistic_tag_specific_day($type);
    } else $result = [];
    return $result;
}


/**
 * 获取指定日期的统计数据 统计shorturl的点击PV/UV
 */
function more_log_statistic_shorturl_specific_day() {

    $params = more_log_statistic_where();

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
 * 获取指定日期的统计数据 统计指定字段 column 的点击PV/UV
 */
function more_log_statistic_column_specific_day($column = 'shorturl') {
    
    $params = more_log_statistic_where();
    
    $sql = "SELECT {$column}, {$params['count_column']} FROM " . YOURLS_DB_TABLE_LOG . " 
        WHERE click_time >= :from AND click_time <= :to {$params['where_string']}
        GROUP BY {$column} ORDER BY {$column} ASC LIMIT :rows;";

    global $ydb;
    $results = $ydb->fetchObjects( $sql, $params['binds'] );
    return $results;
}

/**
 * 查询的基本条件处理 
 */
function more_log_statistic_where(){
    $distinct = (int)$_REQUEST['distinct'] ? true : false;
    if($distinct) {
        $count_column = ' COUNT(DISTINCT(csr_id)) AS clicks ';
    } else {
        $count_column = ' COUNT(*) AS clicks ';
    }
    
    $period = date('Y-m-d', $_REQUEST['date'] ? strtotime($_REQUEST['date']) : time());
    $from   = $period . ' 00:00:00';
    $to     = $period . ' 23:59:59';
    $rows = (int)$_REQUEST['rows'] ? : 10;
    $binds = ['from' => $from, 'to' => $to , 'rows' => $rows];

    $where_string = '';
    $shorturl = $_REQUEST['shorturl'] ? : '';
    if($shorturl) {
        $binds['shorturl'] = $shorturl;
        $where_string = ' AND shorturl= :shorturl';
    }

    // 历史原因增加的冗余
    if(!$_REQUEST['csr_id'] && $_REQUEST['csrId']) $_REQUEST['csr_id'] = $_REQUEST['csrId'];

    // 处理配置中的可参与统计的字段
    $where_string = '';
    global $more_log_statistic_column_list ;
    foreach($more_log_statistic_column_list as $column){
        if($_REQUEST[$column]) {
            $binds[$column] = $_REQUEST[$column];
            $where_string .= " AND {$column} = :{$column} ";
        }
    }
    
    return [
        'count_column'  => $count_column,
        'where_string'  => $where_string,
        'binds'         => $binds,
    ];
}
