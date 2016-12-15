<?php

namespace Miniflux\Handler\Service;

use PicoFeed\Client\Client;
use PicoFeed\Client\ClientException;
use Miniflux\Model\Config;
use Miniflux\Model\Item;

function sync($item_id)
{
    $item = Item\get($item_id);

    if ((bool) Config\get('pinboard_enabled')) {
        pinboard_sync($item);
    }

    if ((bool) Config\get('instapaper_enabled')) {
        instapaper_sync($item);
    }

    if ((bool) Config\get('wallabag_enabled')) {
        wallabag_sync($item);
    }
}

function instapaper_sync(array $item)
{
    $params = array(
        'username' => Config\get('instapaper_username'),
        'password' => Config\get('instapaper_password'),
        'url' => $item['url'],
        'title' => $item['title'],
    );

    $url = 'https://www.instapaper.com/api/add?'.http_build_query($params);

    $client = api_get_call($url);

    if ($client !== false) {
        return $client->getStatusCode() === 201;
    }

    return false;
}

function pinboard_sync(array $item)
{
    $params = array(
        'auth_token' => Config\get('pinboard_token'),
        'format' => 'json',
        'url' => $item['url'],
        'description' => $item['title'],
        'tags' => Config\get('pinboard_tags'),
    );

    $url = 'https://api.pinboard.in/v1/posts/add?'.http_build_query($params);

    $client = api_get_call($url);

    if ($client !== false) {
        $response = json_decode($client->getContent(), true);
        return is_array($response) && $response['result_code'] === 'done';
    }

    return false;
}

function wallabag_sync(array $item)
{
    return wallabag_has_url($item['url'])
        ? false
        : wallabag_add_item($item['url'], $item['title']);
}

function wallabag_has_url($url)
{
    $token = wallabag_get_access_token();
    if ($token === false) {
        return false;
    }
    $apiUrl = rtrim(Config\get('wallabag_url'), '\/') . '/api/entries/exists.json?url=' . urlencode($url);
    $headers = array('Authorization: Bearer ' . $token);
    $response = api_get_call($apiUrl, $headers);
    if ($response !== false) {
        $response = json_decode($response->getContent(), true);
    }
    return !empty($response['exists']);
}

function wallabag_add_item($url, $title)
{
    $token = wallabag_get_access_token();
    if ($token === false) {
        return false;
    }
    $apiUrl = rtrim(Config\get('wallabag_url'), '\/') . '/api/entries.json';
    $headers = array('Authorization: Bearer ' . $token);
    $data = array(
        'url' => $url,
        'title' => $title,
    );
    $response = api_post_call($apiUrl, $data, $headers);
    if ($response !== false) {
        $response = json_decode($response, true);
    }
    return !empty($response['id']);
}

function wallabag_get_access_token()
{
    if (!empty($_SESSION['wallabag_access_token'])) {
        return $_SESSION['wallabag_access_token'];
    }
    $url = rtrim(Config\get('wallabag_url'), '\/') . '/oauth/v2/token';
    $data = array(
        'grant_type' => 'password',
        'client_id' => Config\get('wallabag_client_id'),
        'client_secret' => Config\get('wallabag_client_secret'),
        'username' => Config\get('wallabag_username'),
        'password' => Config\get('wallabag_password')
    );
    $response = api_post_call($url, $data);
    if ($response !== false) {
        $response = json_decode($response, true);
        if (!empty($response['access_token'])) {
            $_SESSION['wallabag_access_token'] = $response['access_token'];
            return $_SESSION['wallabag_access_token'];
        }
    }
    return false;
}

function api_get_call($url, array $headers = array())
{
    try {
        $client = Client::getInstance();
        $client->setUserAgent(Config\HTTP_USER_AGENT);
        if ($headers) {
            $client->setHeaders($headers);
        }
        $client->execute($url);
        return $client;
    } catch (ClientException $e) {
        return false;
    }
}

function api_post_call($url, array $data, array $headers = array())
{
    return function_exists('curl_init')
        ? post_curl($url, $data, $headers)
        : post_stream($url, $data, $headers);
}

function post_curl($url, array $data, array $headers = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function post_stream($url, array $data, array $headers = array())
{
    $contentType = array('Content-Type: application/x-www-form-urlencoded');
    $headers = $headers
        ? array_merge($headers, $contentType)
        : $contentType;
    $options = array(
        'http' => array(
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => http_build_query($data)
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return $result;
}