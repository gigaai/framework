<?php


namespace GigaAI\Http;


/**
 * Class HttpClient
 *
 * @package GigaAI\Http
 */
class HttpClient
{
    /**
     * Execute a POST request
     *
     * @param $url
     * @param array $data
     * @param string $method
     *
     * @return mixed
     */
    public function post($url, $data = [], $method = 'POST')
    {
        $ch = curl_init();

        if (!empty($data)) {
            $data = http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //to suppress the curl output

        $result = curl_exec($ch);

        curl_close ($ch);

        if (false !== $result) {
            $result = json_decode($result);
        }

        return $result;
    }

    /**
     * Execute a DELETE request
     *
     * @param $url
     * @param array $data
     *
     * @return mixed
     */
    public function delete($url, $data = [])
    {
        return self::post($url, $data, 'DELETE');
    }
}