<?php

/** 
	1，先打开https://www.lagou.com
	2，搜索相应的职位，设置好薪水，工作地点，学历，行业等
	3，打开FF/chorme浏览器，打开F12，查看网络，选中XHR
	4，选中postionAjax这一行，拷贝url粘贴到下面的配置，运行即可
*/

//----------------------------设置查询内容START-----------------------------
$php_search_url = 'https://www.lagou.com/jobs/positionAjax.json?gj=5-10%E5%B9%B4&px=default&city=%E4%B8%8A%E6%B5%B7&needAddtionalResult=false';
//$php_search_url = 'https://www.lagou.com/jobs/positionAjax.json?gj=5-10%E5%B9%B4&xl=%E7%A1%95%E5%A3%AB&px=default&city=%E4%B8%8A%E6%B5%B7&needAddtionalResult=false';
$position_key = 'PHP';
//----------------------------设置查询内容END-----------------------------

for ($i = 1; $i < 1000; $i++) {
    $post_data = [
        'first' => $i == 1 ? true : false,
        'pn'    => $i,
        'kd'    => $position_key,
    ];

    $res = find_position($position_key, $post_data, $php_search_url);
    
    //quit
    if(!$res) {
        break;
    }
}

/**
    开始查询全部内容
*/
function find_position($position_key, $post_data, $php_search_url)
{    
    // $output = file_get_contents($php_search_url);

    $output = curl_post($post_data, $php_search_url);
    $output = json_decode($output, true);

    if(isset($output['success']) && $output['success'] == true) {
        //获取当前全部职业列表
        if(isset($output['content']['positionResult']['totalCount']) && $output['content']['positionResult']['totalCount'] > 0) {
            $positionResult = $output['content']['positionResult']['result'];
            echo 'compare: ' . $output['content']['positionResult']['totalCount'] . '--->' . count($positionResult) . PHP_EOL;

            if($positionResult) {
                foreach($positionResult as $from => $result) {
                    //基础数据
                    $str = '招聘公司：' . $result['companyFullName'] . '(' . $result['companyShortName'] . ')' . PHP_EOL;
                    $str .= '职位：' . $result['positionName'] . PHP_EOL;
                    $str .= '描述：' . $result['salary'] . '/'. $result['city'] . '/经验'. $result['workYear'] . '/'. $result['education'] . '及以上/'. $result['jobNature'] . PHP_EOL;
                    $str .= '发布时间：' . $result['formatCreateTime'] . PHP_EOL;
                    $str .= '职位诱惑：' . implode(',', $result['companyLabelList']) . PHP_EOL;

                    //读取详细页面
                    $positionId = $result['positionId'];
                    //https://www.lagou.com/jobs/3103601.html
                    $content_url = 'https://www.lagou.com/jobs/%s.html';
                    $contents = file_get_contents(sprintf($content_url, $positionId));

                    preg_match_all('/<dd class="job_bt">(.*?)<\/dd>/s', $contents, $job_say);

                    if(isset($job_say[1][0]) && $job_say[1][0]) {
                        $match_content = str_replace('&nbsp;', '', $job_say[1][0]);
                        $job_say = strip_tags($match_content);

                        // print_r($job_say);

                        $str .= $job_say;
                        $str .= '--------------------------------------------------------------------------------------------------------------------' . PHP_EOL . PHP_EOL;
                    }

                    write_in($position_key, $positionId, $str);

                    $from += 1;
                    echo "success | num: {$from}, position: {$positionId}." . PHP_EOL;
                }

                return 1;
            }
        }

        echo "no data." . PHP_EOL;
    } else {
        echo "error get data." . PHP_EOL;
    }

    //quit.
    return 0;
}

/**
    @param string $msg
*/
function write_in($position_key, $positionId, $msg) {
    file_put_contents('LA GOU - ' . $position_key . '(' . date('Ymd') . ')' . '.txt', $msg, 8);
}

/**
    //设置post数据
    $post_data = array(
        "username" => "coder",
        "password" => "12345"
        );
*/
function curl_post($post_data, $php_search_url)
{
    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $php_search_url);
    //设置头文件的信息作为数据流输出
    // curl_setopt($curl, CURLOPT_HEADER, 1);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在

    //随便修改，如果被封了，就换IP
    $ip = '10.22.123.50';
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:'.$ip, 'CLIENT-IP:'.$ip));  //构造IP

    //设置post方式提交
     curl_setopt($curl, CURLOPT_POST, 1);
     curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);

    return $data;
}