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
        
        // 1. Handle jika dipanggil dengan format pagination lama: getCustomerList($limit, $page)
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page' => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }

        // 2. Definisikan Default Parameter & Seragamkan Output Fields di sini
        $defaultParams = array(
            'sp.page' => 1,
            'sp.pageSize' => 100,
            'fields' => 'id,name,customerNo' 
        );

        // 3. Ambil parameter filter mentah dari client jika ada
        $search     = isset($params['search']) ? trim($params['search']) : '';
        $customerNo = isset($params['customerNo']) ? trim($params['customerNo']) : '';
        $name       = isset($params['name']) ? trim($params['name']) : '';

        // Hapus key kustom dari array agar tidak terkirim mentah-mentah ke Accurate
        unset($params['search'], $params['customerNo'], $params['name']);

        // 4. Proses Aturan Sinkronisasi Filter khusus Accurate API
        
        // Kondisi A: Jika mencari spesifik Nomor Pelanggan (?customerNo=MB002)
        if (!empty($customerNo)) {
            $params['filter.no.op'] = 'EQUAL';
            $params['filter.no.val'] = array($customerNo); // Otomatis menjadi filter.no.val[0] saat build query
        }
        // Kondisi B: Jika mencari spesifik Nama Pelanggan (?name=Shopee)
        elseif (!empty($name)) {
            $params['filter.keywords.op'] = 'EQUAL';
            $params['filter.keywords.val'] = $name;
        }
        // Kondisi C: Jika menggunakan pencarian umum pencocokan otomatis (?search=...)
        elseif (!empty($search)) {
            // Jika mengandung angka atau pola awalan member, gunakan filter nomor
            if (preg_match('/[0-9]/', $search) || stripos($search, 'C.') === 0 || stripos($search, 'MB') === 0) {
                $params['filter.no.op'] = 'EQUAL';
                $params['filter.no.val'] = array($search);
            } else {
                // Jika teks murni, gunakan filter nama keywords
                $params['filter.keywords.op'] = 'EQUAL';
                $params['filter.keywords.val'] = $search;
            }
        }

        // 5. Gabungkan parameter default dengan filter yang sudah dikonversi
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

    
    public function getItemDetailByNo($itemNo) {
        if (empty($itemNo)) {
            return array('success' => false, 'error' => 'Item No is required', 'data' => null);
        }
        
        $endpoint = 'accurate/api/item/detail.do';
        $params = array(
            'no' => trim($itemNo)
        );
        
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

    public function searchItemOrSN($keywords) {
        $endpoint = 'accurate/api/item/search-by-item-or-sn.do';
        
        $params = array(
            'keywords' => trim($keywords)
        );
        
        // Gabungkan parameter menjadi query string
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

   
    public function getSerialNumberPerWarehouse($itemNo) {
        if (empty($itemNo)) {
            return array('success' => false, 'error' => 'Parameter itemNo wajib diisi', 'data' => null);
        }
        
        $endpoint = 'accurate/api/report/serial-number-per-warehouse.do';
        
        $params = array(
            'itemNo' => trim($itemNo)
        );
        
        // Gabungkan parameter menjadi query string URL
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

    public function getListStock($warehouseName = '', $limit = 100, $page = 1) {
        // Inisialisasi variabel params agar aman dari Undefined Variable Error
        $params = array();

        // Menyusun parameter subset data per halaman & pengurutan sesuai dokumentasi sp
        $params['sp.pageSize'] = $limit;
        $params['sp.page']     = $page;
        $params['sp.sort'] = 'name|asc'; // Nama A-Z, Kuantitas paling sedikit

        $endpoint = 'accurate/api/item/list-stock.do';
        
        if (!empty($warehouseName)) {
            $params['warehouseName'] = $warehouseName;
        }

        // Gabungkan seluruh parameter query string
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

   
    public function getSellingPrice($params) {
        $hasNo = !empty($params['no']);
        $hasUpc = !empty($params['upcNo']);
        $hasPriceCategory = !empty($params['priceCategoryName']);

        // Validasi Dua Kondisi
        // Kondisi 1: no dan priceCategoryName ada bersamaan
        // Kondisi 2: upcNo ada (atau no ada tanpa priceCategoryName)
        if (($hasNo && $hasPriceCategory) || $hasNo || $hasUpc) {
            // Parameter sudah memenuhi salah satu dari dua kondisi yang sah, proses dilanjutkan
        } else {
            return array(
                'success' => false, 
                'message' => 'Kombinasi parameter tidak valid. Diperlukan pencarian berdasarkan: (no / upcNo) ATAU (no dan priceCategoryName)'
            );
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

    public function getEmployeeList($params = array(), $page = null) {
        $endpoint = 'accurate/api/employee/list.do';
        
        // 1. Handle backward compatibility
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page' => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }

        // 2. Definisikan Default Parameter
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page' => 1,
        );
        
        // 3. Ambil custom parameter dari request klien
        $search = isset($params['search']) ? trim($params['search']) : '';
        $number = isset($params['number']) ? trim($params['number']) : '';
        $id     = isset($params['id']) ? trim($params['id']) : '';
        $name   = isset($params['name']) ? trim($params['name']) : '';
        $sales  = isset($params['sales']) ? trim($params['sales']) : '';

        // Hapus custom key agar tidak terkirim mentah-mentah ke server Accurate
        unset($params['search'], $params['number'], $params['id'], $params['name'], $params['sales']);

        // ==============================================================
        // 4. PEMETAAN FILTER: KEYWORDS & SALESMAN
        // ==============================================================
        
        // A. Filter Keywords
        $keywordValue = '';
        if (!empty($number)) {
            $keywordValue = $number;
        } elseif (!empty($name)) {
            $keywordValue = $name;
        } elseif (!empty($id)) {
            $keywordValue = $id;
        } elseif (!empty($search)) {
            $keywordValue = $search;
        }

        if (!empty($keywordValue)) {
            $params['filter.keywords.op'] = 'CONTAIN';
            $params['filter.keywords.val'] = array($keywordValue);
        }

        // B. Filter Boolean Salesman (true/false)
        if ($sales === 'true' || $sales === '1' || $sales === true) {
            $params['filter.salesman'] = 'true';
        } elseif ($sales === 'false' || $sales === '0' || $sales === false) {
            $params['filter.salesman'] = 'false';
        }

        // 5. Gabungkan parameter dan bentuk URL
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    
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

    
    public function getSalesInvoiceList($params = array(), $page = null) {
        $endpoint = 'accurate/api/sales-invoice/list.do';
        
        // 1. Parameter Dasar Default dari Accurate Cloud
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page'     => 1,
            'fields'      => 'id,number,transDate,customer,customer.name,totalAmount,statusName'
        );
        
        // Handle format legacy (jika parameter pertama dikirim berupa integer pageSize)
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page'     => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }
        
        // 2. OTOMATISASI FILTER GLOBAL (Translasi Kriteria POS ke Struktur API Accurate)
        $processedFilters = array();

        // A. Peta Otomatis Tanggal Awal & Akhir (Konversi internal YYYY-MM-DD ke dd/mm/yyyy)
        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $processedFilters['filter.transDate.op']     = 'BETWEEN';
            $processedFilters['filter.transDate.val[0]'] = date('d/m/Y', strtotime($params['start_date']));
            $processedFilters['filter.transDate.val[1]'] = date('d/m/Y', strtotime($params['end_date']));
        }

        // B. Peta Otomatis Kata Kunci Pencarian (Smart Keyword Switcher)
        if (!empty($params['search'])) {
            $keyword = trim($params['search']);
            
            // Deteksi cerdas: jika ada pola nomor faktur (SI.) atau murni digit angka numerik
            if (strpos(strtoupper($keyword), 'SI.') !== false || preg_replace('/[^0-9]/', '', $keyword) === $keyword) {
                $processedFilters['filter.number.op']  = 'EQUAL';
                $processedFilters['filter.number.val'] = $keyword;
            } else {
                // Jika berbentuk string teks bebas, asumsikan sebagai pencarian Nomor Akun Pelanggan
                $processedFilters['filter.customerNo'] = $keyword;
            }
        }

        // C. Bersihkan parameter kustom bawaan POS agar tidak bentrok saat di-merge
        unset($params['start_date'], $params['end_date'], $params['search']);

        // 3. Satukan parameter dasar, parameter kustom murni Accurate, dan filter hasil pemrosesan
        $queryParams = array_merge($defaultParams, $processedFilters, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

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

    public function saveSalesInvoice($data = array()) {
        $endpoint = 'accurate/api/sales-invoice/save.do';
        
        // Validasi awal: Pastikan payload berbentuk array dan tidak kosong
        if (!is_array($data) || empty($data)) {
            return array(
                'success' => false,
                'error'   => 'Payload data transaksi faktur tidak boleh kosong.'
            );
        }

        // 1. Cek parameter customerNo
        if (!isset($data['customerNo']) || trim($data['customerNo']) === '') {
            return array(
                'success' => false,
                'error'   => 'Parameter "customerNo" (Nomor Pelanggan) wajib diisi dan tidak boleh kosong.'
            );
        }

        // 2. Cek struktur data detailItem (Minimal 1 data)
        if (!isset($data['detailItem']) || !is_array($data['detailItem']) || count($data['detailItem']) < 1) {
            return array(
                'success' => false,
                'error'   => 'Detail barang (detailItem) wajib diisi dan minimal harus berisi 1 data barang.'
            );
        }

        // 3. Validasi mendalam pada detailItem
        foreach ($data['detailItem'] as $index => $item) {
            if (!isset($item['itemNo']) || trim($item['itemNo']) === '') {
                return array(
                    'success' => false,
                    'error'   => "Gagal memproses. Pada detailItem indeks ke-{$index}, parameter 'itemNo' tidak boleh kosong."
                );
            }
            if (!isset($item['unitPrice']) || $item['unitPrice'] === '') {
                return array(
                    'success' => false,
                    'error'   => "Gagal memproses. Pada detailItem indeks ke-{$index}, parameter 'unitPrice' tidak boleh kosong."
                );
            }
        }

        // 4. Validasi detailExpense (Berdiri sendiri untuk pendapatan/pembiayaan lain)
        if (isset($data['detailExpense']) && is_array($data['detailExpense']) && count($data['detailExpense']) > 0) {
            foreach ($data['detailExpense'] as $index => $expense) {
                if (!isset($expense['accountNo']) || trim($expense['accountNo']) === '') {
                    return array(
                        'success' => false,
                        'error'   => "Gagal memproses data. Pada detailExpense indeks ke-{$index}, parameter 'accountNo' wajib diisi."
                    );
                }
                if (!isset($expense['expenseAmount']) || trim($expense['expenseAmount']) === '') {
                    return array(
                        'success' => false,
                        'error'   => "Gagal memproses data. Pada detailExpense indeks ke-{$index}, nominal 'expenseAmount' wajib diisi."
                    );
                }
            }
        }

       

        // Jalankan request POST ke Accurate Cloud
        return $this->makeRequest($endpoint, 'POST', $data);
    }

    public function getSalesReceiptList($params = array(), $page = null) {
        $endpoint = 'accurate/api/sales-receipt/list.do';
        
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page'     => 1,
            'fields'      => 'id,number,transDate,customer,customer.name,totalPayment,charField2,paymentMethodName,bank,bank.name'
        );
        
        if (is_int($params) && $page !== null) {
            $params = array(
                'sp.pageSize' => $params,
                'sp.page'     => $page
            );
        } elseif (!is_array($params)) {
            $params = array();
        }
        
        $processedFilters = array();

        if (!empty($params['start_date']) && !empty($params['end_date'])) {
            $processedFilters['filter.transDate.op']     = 'BETWEEN';
            $processedFilters['filter.transDate.val[0]'] = date('d/m/Y', strtotime($params['start_date']));
            $processedFilters['filter.transDate.val[1]'] = date('d/m/Y', strtotime($params['end_date']));
        }

        if (!empty($params['customerNo'])) {
            $processedFilters['filter.customerNo'] = trim($params['customerNo']);
        }
        
        if (!empty($params['number'])) {
            $processedFilters['filter.number.op']  = 'EQUAL';
            $processedFilters['filter.number.val'] = trim($params['number']);
        }

        unset($params['start_date'], $params['end_date'], $params['customerNo'], $params['number']);

        $queryParams = array_merge($defaultParams, $processedFilters, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getSalesReceiptDetail($id = null, $number = null) {
        $endpoint = 'accurate/api/sales-receipt/detail.do';
        $params = array();

        if (!empty($id)) {
            $params['id'] = $id;
        } elseif (!empty($number)) {
            $params['number'] = $number;
        } else {
            return array(
                'success' => false,
                'error' => 'ID atau Nomor Penerimaan Penjualan tidak boleh kosong'
            );
        }
        
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function saveSalesReceipt($data = array()) {
        $endpoint = 'accurate/api/sales-receipt/save.do';
        
        // Validasi awal: Pastikan payload berbentuk array dan tidak kosong
        if (!is_array($data) || empty($data)) {
            return array(
                'success' => false,
                'error'   => 'Payload data transaksi penerimaan tidak boleh kosong.'
            );
        }

        // 1. STRIKT VALIDASI: Cek customerNo
        if (!isset($data['customerNo']) || trim($data['customerNo']) === '') {
            return array(
                'success' => false,
                'error'   => 'Parameter "customerNo" (Nomor Pelanggan) wajib diisi dan tidak boleh kosong.'
            );
        }

        // 2. STRIKT VALIDASI: Cek bankNo
        if (!isset($data['bankNo']) || trim($data['bankNo']) === '') {
            return array(
                'success' => false,
                'error'   => 'Parameter "bankNo" (Nomor Akun Bank/Kas) wajib diisi dan tidak boleh kosong.'
            );
        }

        // 3. STRIKT VALIDASI: Cek chequeAmount
        if (!isset($data['chequeAmount']) || $data['chequeAmount'] === '') {
            return array(
                'success' => false,
                'error'   => 'Parameter "chequeAmount" (Jumlah Pembayaran) wajib diisi dan tidak boleh kosong.'
            );
        }

        // 4. STRIKT VALIDASI: Cek transDate
        if (!isset($data['transDate']) || trim($data['transDate']) === '') {
            return array(
                'success' => false,
                'error'   => 'Parameter "transDate" (Tanggal Transaksi) wajib diisi dan tidak boleh kosong.'
            );
        }

        // 5. STRIKT VALIDASI: Cek chequeDate
        if (!isset($data['chequeDate']) || trim($data['chequeDate']) === '') {
            return array(
                'success' => false,
                'error'   => 'Parameter "chequeDate" (Tanggal Cek/Pelunasan) wajib diisi dan tidak boleh kosong.'
            );
        }

        // 6. STRIKT VALIDASI: Cek struktur dan ketersediaan data detailInvoice (Minimal 1 data)
        if (!isset($data['detailInvoice']) || !is_array($data['detailInvoice']) || count($data['detailInvoice']) < 1) {
            return array(
                'success' => false,
                'error'   => 'Detail alokasi faktur (detailInvoice) wajib diisi dan minimal harus berisi 1 data faktur.'
            );
        }

        // 7. STRIKT VALIDASI: Loop setiap baris alokasi faktur, pastikan invoiceNo dan paymentAmount aman
        foreach ($data['detailInvoice'] as $index => $inv) {
            // Cek nomor faktur (invoiceNo)
            if (!isset($inv['invoiceNo']) || trim($inv['invoiceNo']) === '') {
                return array(
                    'success' => false,
                    'error'   => "Gagal memproses data. Pada detailInvoice indeks ke-{$index}, parameter 'invoiceNo' tidak boleh kosong."
                );
            }

            // Cek nominal bayar per faktur (paymentAmount)
            if (!isset($inv['paymentAmount']) || $inv['paymentAmount'] === '') {
                return array(
                    'success' => false,
                    'error'   => "Gagal memproses data. Pada detailInvoice indeks ke-{$index}, parameter 'paymentAmount' tidak boleh kosong."
                );
            }
        }

        // Jika semua lolos, jalankan request POST ke Accurate Cloud
        return $this->makeRequest($endpoint, 'POST', $data);
    }

    public function getSellingPriceAdjustmentDetail($id = null, $number = null) {
        $endpoint = 'accurate/api/sellingprice-adjustment/detail.do';
      
        $params = array();

        if (!empty($id)) {
            $params['id'] = $id;
        } elseif (!empty($number)) {
            $params['number'] = $number;
        } else {
            return array(
                'success' => false,
                'error' => 'ID atau Nomor Transaksi Penyesuaian Harga tidak boleh kosong.'
            );
        }
        
        $endpoint .= '?' . http_build_query($params);
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getShipmentList($params = array(), $page = null) {
        $endpoint = 'accurate/api/shipment/list.do';
        
        // Parameter default untuk paginasi
        $defaultParams = array(
            'sp.page' => 1,
            'sp.pageSize' => 100
        );
        
        // Format pemanggilan fungsi versi lama (limit, page)
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


    public function getGLAccountList($params = array()) {
        $endpoint = 'accurate/api/glaccount/list.do';

        // 1. Definisikan Default Parameter (Fields & Paginasi)
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page'     => 1,
            'fields'      => 'id,name,no'
        );
        
        // 2. Ambil custom parameter dari request klien
        $search = isset($params['search']) ? trim($params['search']) : '';

        // Hapus key buatan sendiri agar tidak ikut ter-build ke query Accurate
        unset($params['search']);

        // ==============================================================
        // 3. PEMETAAN FILTER SESUAI PERMINTAAN
        // ==============================================================
        
        // A. Filter Tipe Akun (Piutang)
        $params['filter.accountType.op']  = 'EQUAL';
        $params['filter.accountType.val'] = 'REVENUE';
        
        // B. Filter Leaf (Hanya akun anak/ujung) dan Suspended
        // Note: suspended = true berarti mengambil akun yang dinonaktifkan. 
        // Ubah menjadi 'false' jika Anda ingin mengambil akun yang sedang aktif.
        $params['filter.leafOnly']  = 'true';
        $params['filter.suspended'] = 'false'; 

        // C. Filter Pencarian (Mencari berdasarkan Nomor Akun)
        if (!empty($search)) {
            $params['filter.keywords.op']  = 'EQUAL';
            $params['filter.keywords.val'] = array($search);
        }

        // 4. Gabungkan parameter dan bentuk URL
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            // http_build_query akan mengonversi array keywords.val menjadi val[0]=...
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getGLAccountList_CASHBANK($params = array()) {
        $endpoint = 'accurate/api/glaccount/list.do';

        // 1. Definisikan Default Parameter (Fields & Paginasi)
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page'     => 1,
            'fields'      => 'id,name,no'
        );
        
        // 2. Ambil custom parameter dari request klien
        $search = isset($params['search']) ? trim($params['search']) : '';

        // Hapus key buatan sendiri agar tidak ikut ter-build ke query Accurate
        unset($params['search']);

        // ==============================================================
        // 3. PEMETAAN FILTER SESUAI PERMINTAAN
        // ==============================================================
        
        // A. Filter Tipe Akun (Kas & Bank)
        $params['filter.accountType.op']  = 'EQUAL';
        $params['filter.accountType.val'] = 'CASH_BANK';
        
        // B. Filter Leaf (Hanya akun anak/ujung) dan Suspended
        $params['filter.leafOnly']  = 'true';
        $params['filter.suspended'] = 'false'; 

        // C. Filter Pencarian (Mencari berdasarkan Nomor Akun)
        if (!empty($search)) {
            $params['filter.keywords.op']  = 'EQUAL';
            $params['filter.keywords.val'] = array($search);
        }

        // 4. Gabungkan parameter dan bentuk URL
        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getItemCategoryList($params = array()) {
        $endpoint = 'accurate/api/item-category/list.do';
        
        $defaultParams = array(
            'sp.pageSize' => 100,
            'sp.page'     => 1,
            'fields'      => 'id,name,nameWithIndent,parent'
        );
        
        $search = isset($params['search']) ? trim($params['search']) : '';
        unset($params['search']);

        if (!empty($search)) {
            $params['filter.keywords.op']  = 'CONTAIN';
            $params['filter.keywords.val'] = array($search);
        }

        $queryParams = array_merge($defaultParams, $params);
        
        if (!empty($queryParams)) {
            $endpoint .= '?' . http_build_query($queryParams);
        }
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function getItemCategoryDetail($id) {
        if (empty($id)) {
            return array('success' => false, 'error' => 'Kategori ID wajib diisi', 'data' => null);
        }
        
        $endpoint = 'accurate/api/item-category/detail.do';
        $params = array('id' => $id);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'GET');
    }

    public function deleteSalesInvoice($number) {
        if (empty($number)) {
            return array('success' => false, 'error' => 'Nomor Faktur wajib diisi', 'data' => null);
        }
        
        $endpoint = 'accurate/api/sales-invoice/delete.do';
        $params = array('number' => $number);
        $endpoint .= '?' . http_build_query($params);
        
        return $this->makeRequest($endpoint, 'DELETE');
    }
    public function getDatabaseList() {
        $url = 'https://account.accurate.id/api/db-list.do';
        return $this->executeCurl($url, 'GET');
    }

    public function getDatabaseDetail($id) {
        if (empty($id)) {
            return array('success' => false, 'error' => 'ID Database wajib diisi', 'data' => null);
        }
        $url = 'https://account.accurate.id/api/db-detail.do';
        $params = array('id' => $id);
        $url .= '?' . http_build_query($params);
        return $this->executeCurl($url, 'GET');
    }

    public function getCompanyProfile() {
        $endpoint = 'accurate/api/company/detail.do';
        return $this->makeRequest($endpoint, 'GET');
    }

    public function saveItemCategory($data = array()) {
        $endpoint = 'accurate/api/item-category/save.do';
        
        if (!isset($data['name']) || trim($data['name']) === '') {
            return array(
                'success' => false,
                'error'   => 'Parameter "name" (Nama Kategori) wajib diisi.'
            );
        }

        return $this->makeRequest($endpoint, 'POST', $data);
    }
}
?>