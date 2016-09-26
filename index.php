<?php

$domains = array(
    'master' => 'https://api-mst-stg.oddsmarket.org',
    'prematch' => 'https://api-pr-stg.oddsmarket.org',
    'live' => 'https://api-lv-stg.oddsmarket.org'
);
$bookmaker_ids = [21];
$sport_ids = [1, 2, 3];
$only_main = false;
$only_back = false;
$show_direct_link = true;
$format = 'JSON'; # JSON / XML
$api_key = 'd8351db183dd6344301f28ba23644894';


$last_updated_at = 0;
$etag = null;

function get_params($sport_ids, $only_main, $only_back, $show_direct_link, $format)
{
    global $last_updated_at, $api_key;

    $params = array();

    $params['apiKey'] = $api_key;
    $params['sportIds'] = implode(',', $sport_ids);
    if ($only_main)
        $params['onlyMain'] = 'true';
    if ($only_back)
        $params['onlyBack'] = 'true';
    if ($show_direct_link)
        $params['showDirectLink'] = 'true';
    if ($format == 'XML')
        $params['format'] = $format;
    $params['lastUpdatedAt'] = $last_updated_at;

    return $params;
}

function get_url($bk_ids)
{
    global $domains;
    return $domains['prematch'] . "/v1/bookmakers/" . implode(',', $bk_ids) . "/odds";
}

function v1_odds($method, $bk_ids, $params)
{
    global $etag, $last_updated_at;
    $new_updated_at = time();

    $headers = array('If-None-Match' => $etag);
    $post_items = array();

    foreach ($params as $key => $value) {
        $post_items[] = $key . '=' . $value;
    }
    $post_string = implode('&', $post_items);
    $url = get_url($bk_ids);
    $ch = curl_init();
    if ($method == 'post') {
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    } else {
        curl_setopt($ch, CURLOPT_URL, $url . '?' . $post_string);
    }
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $body = substr($output, $header_size);

    curl_close($ch);

    if (preg_match('/^(200|304)/', $httpcode, $matches)) {
        $last_updated_at = $new_updated_at;
    }

    return array('last_updated_at' => $last_updated_at, $httpcode => $body);
}

for (; ;) {
    $method = 'get';
    $params = get_params($sport_ids, $only_main, $only_back, $show_direct_link, $format);
    $result = v1_odds($method, $bookmaker_ids, $params);
    foreach ($result as $key => $value) {
        echo "<h4>" . $key. "</h4>";
        echo '<br/>';
        echo "<small>" . $value. "</small>";
    }

    sleep(0.3);
}
