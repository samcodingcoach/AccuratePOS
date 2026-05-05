<?php
/**
 * Class AccurateAPI untuk handle semua API calls ke Accurate
 * Versi Kompatibel: PHP 5.6
 * Integrasi Auth: API Token Version 1.0.3 (Non-OAuth)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../utils/utils.php';

class AccurateAPI {
    private $apiToken;
    private $signatureSecret;
    public $host;
    
    public function __construct() {
        // Mengambil kredensial dari config.php
        $this->apiToken = ACCURATE_API_TOKEN;
        $this->signatureSecret = ACCURATE_SIGNATURE_SECRET;
        
        // Mengambil host URL secara otomatis saat class dipanggil
        $this->host = $this->resolveHost();
    }
    
    /**
     * Memanggil /api/api-token.do untuk mendapatkan URL Host (misal: https://odin.accurate.id)
     */
    private function resolveHost() {
        $url = 'https://account.accurate.id/api/api-token.do';
        $response = $this->executeCurl($url, 'POST');
        
        if ($response['success']) {
            // Membaca host dari struktur JSON terbaru Accurate
            if (isset($response['data']['d']['database']['host'])) {
                return rtrim($response['data']['d']['database']['host'], '/');
            } 
            // Fallback untuk struktur lama
            elseif (isset($response['data']['d']['host'])) {
                return rtrim($response['data']['d']['host'], '/');
            }
        }
        
        // Catat ke log jika gagal mendapatkan host
        if (function_exists('logError')) {
            logError("Gagal mendapatkan Host URL. Response: " . json_encode($response), __FILE__, __LINE__);
        }
        return null;
    }

    /**
     * Menghasilkan Timestamp format WIB (Asia/Jakarta) sesuai syarat Accurate
     */
    private function getAccurateTimestamp() {
        $dt = new DateTime("now", new DateTimeZone("Asia/Jakarta"));
        return $dt->format('d/m/Y H:i:s');
    }

    /**
     * Menghasilkan Signature menggunakan algoritma HMAC-SHA256
     */
    private function generateAccurateSignature($timestamp) {
        $hash = hash_hmac('sha256', $timestamp, $this->signatureSecret, true);
        return base64_encode($hash);
    }
    
    public function getBaseUrl() {
        return $this->host;
    }
    
    /**
     * Menyusun endpoint URL dan memanggil executeCurl
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null, $headers = array()) {
        if (!$this->host) {
            return array(
                'success' => false, 
                'http_code' => 0, 
                'data' => null, 
                'error' => 'Host URL tidak ditemukan. Periksa API Token Anda.'
            );
        }

        // Susun full URL secara presisi
        $endpoint = ltrim($endpoint, '/');
        $url = $this->host . '/' . $endpoint;
        
        return $this->executeCurl($url, $method, $data, $headers);
    }

    /**
     * Eksekusi cURL dengan Injeksi Header API Token Accurate
     */
    private function executeCurl($url, $method = 'GET', $data = null, $customHeaders = array()) {
        $ch = curl_init();
        
        // Generate Security Headers
        $timestamp = $this->getAccurateTimestamp();
        $signature = $this->generateAccurateSignature($timestamp);

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true, // Mengatasi response code 308 (Redirect)
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_UNRESTRICTED_AUTH => true, // Mencegah header auth terhapus saat redirect
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Nuansa Accurate API Client/2.0'
        );
        
        curl_setopt_array($ch, $options);
        
        $defaultHeaders = array(
            "Accept: application/json",
            "Authorization: Bearer " . $this->apiToken,
            "X-Api-Timestamp: " . $timestamp,
            "X-Api-Signature: " . $signature
        );
        
        $allHeaders = array_merge($defaultHeaders, $customHeaders);
        
        $method = strtoupper($method);
        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    $isFormData = false;
                    foreach ($allHeaders as $header) {
                        if (stripos($header, 'Content-Type: application/x-www-form-urlencoded') !== false) {
                            $isFormData = true;
                            break;
                        }
                    }
                    if ($isFormData) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                        $allHeaders[] = 'Content-Type: application/json';
                    }
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    $allHeaders[] = 'Content-Type: application/json';
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'GET':
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                break;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            if (function_exists('logError')) logError("cURL Error: " . $error, __FILE__, __LINE__);
            return array('success' => false, 'http_code' => 0, 'data' => null, 'error' => $error);
        }
        
        $decodedResponse = json_decode($response, true);
        $success = ($httpCode >= 200 && $httpCode < 300);
        
        // Standar Accurate: HTTP 200 tapi field 's' false berarti error logika aplikasi
        if ($success && is_array($decodedResponse) && isset($decodedResponse['s']) && $decodedResponse['s'] === false) {
            $success = false;
        }
        
        $errorMessage = null;
        if (!$success) {
            if (is_array($decodedResponse) && isset($decodedResponse['d']) && is_array($decodedResponse['d']) && !empty($decodedResponse['d'])) {
                $errorMessage = implode(', ', $decodedResponse['d']);
            } elseif (is_array($decodedResponse) && isset($decodedResponse['error'])) {
                $errorMessage = $decodedResponse['error'];
            } elseif (is_array($decodedResponse) && isset($decodedResponse['message'])) {
                $errorMessage = $decodedResponse['message'];
            } else {
                $errorMessage = "HTTP " . $httpCode . " error";
            }
            if (function_exists('logError')) logError("API Error: " . $errorMessage . " (HTTP " . $httpCode . ") - URL: " . $url, __FILE__, __LINE__);
        }
        
        return array(
            'success' => $success,
            'http_code' => $httpCode,
            'data' => $decodedResponse,
            'error' => $errorMessage
        );
    }

    /* ====================================================================
       DAFTAR ENDPOINT SPESIFIK 
       ==================================================================== */

    public function getCustomerList($params = array(), $page = null) {
        $endpoint = 'accurate/api/customer/list.do';
        
        $defaultParams = array(
            'sp.page' => 1,
            'sp.pageSize' => 100,
            'fields' => 'id,name,no,customerNo,email,mobilePhone,phone,address,createDate,createdDate,lastUpdate,balanceList'
        );
        
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page' => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }
        
        $params = array_merge($defaultParams, $params);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getCustomerDetail($customerId) {
        if (empty($customerId)) {
            return array('success' => false, 'message' => 'Customer ID is required', 'data' => null);
        }
        
        $endpoint = 'accurate/api/customer/detail.do';
        $params = array('id' => $customerId);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getBranchList($params = array()) {
        $endpoint = 'accurate/api/branch/list.do';
        
        $defaultParams = array(
            'sp.pageSize' => 25,
            'sp.page' => 1
        );
        
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getBranchDetail($id) {
        $endpoint = 'accurate/api/branch/detail.do';
        $params = array('id' => $id);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getWarehouseList($params = array(), $page = null) {
        $endpoint = 'accurate/api/warehouse/list.do';
        
        $defaultParams = array(
            'sp.pageSize' => 25,
            'sp.page' => 1
        );
        
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page' => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }
        
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getWarehouseDetail($id) {
        $endpoint = 'accurate/api/warehouse/detail.do';
        $params = array('id' => $id);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getItemList($limit = 100, $page = 1, $filters = array()) {
        $endpoint = 'accurate/api/item/list.do';
        $params = array(
            'sp.pageSize' => $limit,
            'sp.page' => $page,
            'fields' => 'id,name,no,upcNo,itemType,unitPrice,availableToSell,lastUpdate,itemCategory'
        );
        
        if (!empty($filters)) {
            $params = array_merge($params, $filters);
        }
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getItemDetail($itemId) {
        if (empty($itemId)) {
            return array('success' => false, 'message' => 'Item ID is required', 'data' => null);
        }
        
        $endpoint = 'accurate/api/item/detail.do';
        $params = array('id' => $itemId);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getItemByUPC($upcNo) {
        if (empty($upcNo)) return array('success' => false, 'message' => 'UPC No is required');

        $endpoint = 'accurate/api/item/search-by-no-upc.do';
        $params = array('keywords' => $upcNo);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getItemStock($itemNo, $warehouseName = '') {
        if (empty($itemNo)) return array('success' => false, 'message' => 'Nomor barang (no) diperlukan');

        $endpoint = 'accurate/api/item/get-stock.do';
        $params = array('no' => $itemNo);
        
        if (!empty($warehouseName)) {
            $params['warehouseName'] = $warehouseName;
        }

        $endpoint .= '?' . http_build_query($params);
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getSellingPrice($params) {
        if (empty($params['no']) && empty($params['upcNo'])) {
            return array('success' => false, 'message' => 'Nomor barang (no) atau UPC diperlukan');
        }

        $endpoint = 'accurate/api/item/get-selling-price.do';
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getPurchaseOrderList($params = array()) {
        $endpoint = 'accurate/api/purchase-order/list.do';
        
        $defaultParams = array(
            'sp.page' => 1,
            'sp.pageSize' => 200,
            'fields' => 'id,number,transDate,dueDate,totalAmount,status,statusName,vendor,vendor.name',
        );
        
        $finalParams = array_merge($defaultParams, $params);
        $endpoint .= '?' . http_build_query($finalParams);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getPurchaseOrderDetail($purchaseOrderNumber) {
        if (empty($purchaseOrderNumber)) {
            return array(
                'success' => false,
                'message' => 'Purchase order ID / Number PO is required',
                'data' => null
            );
        }
        
        $endpoint = 'accurate/api/purchase-order/detail.do';
        $params = array('number' => $purchaseOrderNumber);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getVendorDetail($vendorId = null, $vendorNo = null) {
        $endpoint = 'accurate/api/vendor/detail.do';
        $params = array();

        if (!empty($vendorId)) {
            $params['id'] = $vendorId;
        } elseif (!empty($vendorNo)) {
            $params['vendorNo'] = $vendorNo;
        } else {
            return array(
                'success' => false,
                'error' => 'ID atau Nomor Vendor tidak boleh kosong'
            );
        }
        
        $endpoint .= '?' . http_build_query($params);
        return $this->makeRequest($endpoint, 'GET');
    }


    /**
     * Mendapatkan daftar Karyawan (Employee)
     * Scope: employee_view
     */
    public function getEmployeeList($params = array(), $page = null) {
        $endpoint = 'accurate/api/employee/list.do';
        
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page' => 1,
            // Anda bisa mengatur default fields jika diperlukan
            // 'fields' => 'id,name,no,email,mobilePhone,position'
        );
        
        // Handle backward compatibility (jika parameter pertama adalah limit/pageSize)
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page' => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }
        
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Mendapatkan detail Karyawan (Employee) berdasarkan ID atau Number
     * Scope: employee_view
     */
    public function getEmployeeDetail($id = null, $number = null) {
        $endpoint = 'accurate/api/employee/detail.do';
        $params = array();

        if (!empty($id)) {
            $params['id'] = $id;
        } elseif (!empty($number)) {
            // Catatan: Dokumentasi Accurate terkadang menggunakan 'no' untuk Nomor Karyawan,
            // namun beberapa endpoint menggunakan 'number'. Kita sesuaikan dengan parameter yang diminta.
            $params['no'] = $number; 
        } else {
            return array(
                'success' => false,
                'error' => 'ID atau Nomor Karyawan tidak boleh kosong'
            );
        }
        
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Mendapatkan daftar Faktur Penjualan (Sales Invoice)
     * Scope: sales_invoice_view
     */
    public function getSalesInvoiceList($params = array(), $page = null) {
        $endpoint = 'accurate/api/sales-invoice/list.do';
        
        // Parameter default yang sering dibutuhkan untuk faktur
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page' => 1,
            // Menampilkan ID, Nomor Faktur, Tanggal, Nama Pelanggan, Total, dan Status
            'fields' => 'id,number,transDate,customer,customer.name,totalAmount,statusName'
        );
        
        // Menangani format parameter lama (jika parameter pertama adalah integer untuk limit)
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page' => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }
        
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Mendapatkan detail Faktur Penjualan (Sales Invoice) berdasarkan ID atau Number
     * Scope: sales_invoice_view
     */
    public function getSalesInvoiceDetail($id = null, $number = null) {
        $endpoint = 'accurate/api/sales-invoice/detail.do';
        $params = array();

        if (!empty($id)) {
            $params['id'] = $id;
        } elseif (!empty($number)) {
            $params['number'] = $number;
        } else {
            return array(
                'success' => false,
                'error' => 'ID atau Nomor Faktur tidak boleh kosong'
            );
        }
        
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    /**
     * Menampilkan faktur penjualan berdasarkan filter tertentu (customerNo, fromDate, toDate)
     * Endpoint: /accurate/api/sales-invoice/detail-invoice.do
     * Scope: sales_invoice_view
     */
    public function getSalesInvoiceDetailFiltered($params = array()) {
        $endpoint = 'accurate/api/sales-invoice/detail-invoice.do';
        
        // Parameter default untuk paginasi (jika endpoint ini mendukung paginasi)
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page' => 1
        );
        
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }
}
?>