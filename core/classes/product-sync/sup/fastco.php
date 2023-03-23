<?php

use Curl\Curl as Curl;

/**
 * Static methods to be used in Tire and Rim Product Sync classes (and
 * in some cases, for inventory processes)
 *
 * Those classes could just extend this one but I don't see the point.
 *
 * @see Product_Sync_Fastco_Tire_CA
 * @see Product_Sync_Fastco_Wheel_CA
 * @see SIS_Fastco_Tires_CA
 * @see SIS_Fastco_Rims_CA
 */
class Product_Sync_Fastco
{

    /**
     * Get rims data. If we're on the click it wheels server, we can use FTP since
     * that IP will be whitelisted. Otherwise, we get the data from the click it wheels
     * server (which has an endpoint to expose the data)
     *
     * Note that this is not only used in product sync, but in the inventory processes as well.
     *
     * @param $type
     * @return false|mixed
     */
    static function get_fastco_file_parsed($type = 'tires_ca')
    {
        $ftp = new FTP_Get_Csv();
        $ftp->method = 'ftp';
        $ftp->host = 'ftp.fastco.ca';
        $ftp->port = 21;
        $ftp->username = '##removed';
        $ftp->password = Product_Sync::get_credentials()['fastco'];

        if ($type === 'tires_ca') {
            $ftp->remote_file_name = 'FastcoTireInventory.csv';
        } else if ($type === 'rims_ca') {
            $ftp->remote_file_name = 'FastcoWheelInventory.csv';
        } else {
            return [
              'error' => 'invalid_type',
              'payload' => [],
            ];
        }

        $ftp->run();

        list($columns, $data, $error) = Product_Sync::parse_csv($ftp->get_local_full_path(), []);

        // not too early
        $ftp->unlink(true);

        if ($error) {
            log_data([$error, $columns], 'fastco-file-err');

            return [
              'error' => $error,
              'payload' => [],
            ];
        } else {

            return [
              'error' => '',
              'payload' => $data,
            ];
        }
    }

    /**
     * Get tires data. If we're on the click it wheels server, we can use FTP since
     * that IP will be whitelisted. Otherwise, we get the data from the click it wheels
     * server (which has an endpoint to expose the data)
     *
     * Note that this is not only used in product sync, but in the inventory processes as well.
     *
     * @return array|false|mixed
     */
    static function get_tires_ca_data()
    {
        $type = 'tires_ca';
        if (IN_PRODUCTION && !IS_WFL) {
            $response = self::get_fastco_file_parsed($type);
            $payload = @$response['payload'];
            return is_array($payload) ? $payload : [];
        } else {
            $url = "https://tiresource.com/ajax.php?__route__=fastco_fetch&type=$type&auth=32723472134723487234";
            $curl = new Curl();
            $res = $curl->get($url);

            $response_arr = json_decode($res->response, JSON_INVALID_UTF8_IGNORE);
            $payload = @$response_arr['payload'];
            return is_array($payload) ? $payload : [];
        }
    }

    static function get_rims_ca_data()
    {
        $type = 'rims_ca';
        if (IN_PRODUCTION && !IS_WFL) {
            $response = self::get_fastco_file_parsed($type);
            $payload = @$response['payload'];
            return is_array($payload) ? $payload : [];
        } else {
            $url = "https://tiresource.com/ajax.php?__route__=fastco_fetch&type=$type&auth=32723472134723487234";
            $curl = new Curl();
            $res = $curl->get($url);

            $response_arr = json_decode($res->response, JSON_INVALID_UTF8_IGNORE);
            $payload = @$response_arr['payload'];
            return is_array($payload) ? $payload : [];
        }
    }
}
