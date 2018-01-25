<?php

/**
 * @author HughesWu extend Roger
 */
class ControllerPaymentSpgatewayMPG extends Controller {

    private $hashKey, $hashIV;

    public function index() {

        $data['button_confirm'] = $this->language->get('button_confirm');
        
        $this->load->model('checkout/order');
        //$this->load->model('account/order'); // 因為使用 $this->cart->getProducts()取產品資訊，可以不用載入

        // 是否為測試模式
        $isTestMode = $this->config->get('SpgatewayMPG_test_mode');

        $data['action'] = $isTestMode ? 'https://ccore.spgateway.com/MPG/mpg_gateway' : 'https://core.spgateway.com/MPG/mpg_gateway';

        // AJAX 訂單送回 Spgateway 之前執行
        //$data['ajaxUrl'] = $this->url->link('payment/SpgatewayMPG/confirm');
        $data['ajaxUrl'] = $this->url->link('payment/SpgatewayMPG/confirm', '', 'SSL');

        // 要傳遞資料
        $data['params'] = $this->_composePostInput();

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/SpgatewayMPG.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/SpgatewayMPG.tpl';
        }else if(file_exists(DIR_TEMPLATE .'default/template/payment/SpgatewayMPG.tpl')){
            $this->template = 'payment/SpgatewayMPG.tpl';//for 2.2版
        }else {
            $this->template = 'default/template/payment/SpgatewayMPG.tpl';//for 2.1版以前
        }
        //$this->cart->clear();

        // 載入 template 字串
        $this->load->language('payment/SpgatewayMPG');
        $data['msg1'] = $this->language->get('msg1');
        $data['msg2'] = $this->language->get('msg2');
        $data['msg3'] = $this->language->get('msg3');

        return $this->load->view($this->template, $data);
    }

    /**
     * 要傳遞資料
     * @return array 一維陣列 key => Input Post Name, value => Input Post Value
     */
    protected function _composePostInput() {

        $result = array();

        // 訂購   資料
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        
        //$products = $this->model_account_order->getOrderProducts($this->session->data['order_id']);
        $products = $this->cart->getProducts(); // get active products from cart

        // 訂購人 資料
        $user_info = $this->_getUserInfo();

        /**
         * SPG 各項設定參數
         */
        $this->hashKey = trim($this->config->get('SpgatewayMPG_hash_key'));
        $this->hashIV = trim($this->config->get('SpgatewayMPG_hash_iv'));
        $post_data = [
            'MerchantID' => trim($this->config->get('SpgatewayMPG_merchant_id')),
            'RespondType' => 'String',
            'TimeStamp' =>  time(),
            'Version' => '1.4',
            'MerchantOrderNo' => $order_info['order_id'],
            'Amt' => intval($order_info['total']),
            'ItemDesc' => $this->getItemDescByProduct($products),
            // "ExpireDate" => date('Ymd', time()+intval($this->ExpireDate)*24*60*60),
            "Email" => $user_info['Email'],
            'LoginType' => '0',
            "NotifyURL" => $this->url->link('payment/SpgatewayMPG/feedback', '', 'SSL'),        // 幕後
            "ReturnURL" => $this->url->link('payment/SpgatewayMPG/feedbackShow', '', 'SSL'),    // 幕前(線上)
            "ClientBackURL" => $this->url->link('common/home', '', 'SSL'),                      // 取消交易
            "LangType" => $this->language->get('code')
        ];

        $aes = $this->create_mpg_aes_encrypt($post_data, $this->hashKey, $this->hashIV);
        $sha256 = $this->aes_sha256_str($aes, $this->hashKey, $this->hashIV);

        return [
            'MerchantID' => trim($this->config->get('SpgatewayMPG_merchant_id')),
            'TradeInfo' => $aes,
            'TradeSha' => $sha256,
            'Version' => '1.4',
            'Cart_version' => 'Spgateway_MPG_OpenCart2.2'
        ];
    }

    /**
     * 訂購人資料
     *
     * @return array 一維陣列 key => 欄位名稱, value => 值
     */
    protected function _getUserInfo() {

        if ($this->customer->isLogged()) {
            // account = guest
            // guest = array('id', 'username', ..)
            $result = array(
                'FirstName' => $this->customer->getFirstName() ? $this->customer->getFirstName() : $_SESSION[$_SESSION['account']]['firstname'],
                'LastName' => $this->customer->getLastName() ? $this->customer->getLastName() : $_SESSION[$_SESSION['account']]['lastname'],
                'Email' => ($this->customer->getEmail() ? $this->customer->getEmail() : $_SESSION[$_SESSION['account']]['email']),
                'Phone' => ($this->customer->getTelephone() ? $this->customer->getTelephone() : $_SESSION[$_SESSION['account']]['telephone']),
            );
        } elseif (!empty($_POST)) {
            $result = array(
                'FirstName' => $_POST["firstname"],
                'LastName' => $_POST["lastname"],
                'Email' => $_POST["email"],
                'Phone' => $_POST["telephone"],
            );
        } elseif (isset($this->session->data['guest'])) {
            $result = array(
                'FirstName' => isset($this->session->data['guest']['firstname']) ? $this->session->data['guest']['firstname'] : $this->session->data['guest']['payment']['firstname'],
                'LastName' => isset($this->session->data['guest']['lastname']) ? $this->session->data['guest']['lastname'] : $this->session->data['guest']['payment']['lastname'],
                'Email' => isset($this->session->data['guest']['email']) ? $this->session->data['guest']['email'] : $this->session->data['guest']['payment']['email'],
                'Phone' => isset($this->session->data['guest']['telephone']) ? $this->session->data['guest']['telephone'] : $this->session->data['guest']['payment']['telephone'],
            );
        } else {
            $result = array(
                'FirstName' => '',
                'LastName' => '',
                'Email' => '',
                'Phone' => '',
            );
        }

        $result['ShowLanguage'] = $this->language->get('code');
        $result['ShowName'] = strpos($this->language->get('code'), 'zh') ? $result['LastName'] . $result['FirstName'] : $result['FirstName'] . $result['LastName'];

        return $result;
    }

    /**
     * AJAX 訂單送回 Spgateway 之前執行
     */
    public function confirm() {

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('SpgatewayMPG_order_status_id'));
    }

    /**
     * PAGE 幕前顯示
     */
    public function feedbackShow() {

        // 交易狀態
        $result = $_POST;
        //file_put_contents('/tmp/debug.txt',print_r($_POST, true));

        /**
         * 頁面資料
         */
        $this->language->load('checkout/success');
        $this->language->load('checkout/success_error');

        // 比對 TradeSha 是否正確
        if (! $this->check_trade_sha($result)) {
            $url = $this->url->link('common/home', '', 'SSL');
            header("location:$url");
            exit;
        }

        // AES decrypt
        $result = $this->create_aes_decrypt($result['TradeInfo'], 
                                            trim($this->config->get('SpgatewayMPG_hash_key')), 
                                            trim($this->config->get('SpgatewayMPG_hash_iv')));
        if (empty($result['Status'])) {
            $url = $this->url->link('common/home', '', 'SSL');
            header("location:$url");
            exit;
        }

        // 顯示的 Title
        $title = (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) ? 
                    $this->language->get('heading_title') : 
                    $this->language->get('heading_title_fail') . $result['Message'];

        $this->document->setTitle($title);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('common/home'),
            'text' => $this->language->get('text_home'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/cart'),
            'text' => $this->language->get('text_basket'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/checkout', '', 'SSL'),
            'text' => $this->language->get('text_checkout'),
            'separator' => $this->language->get('text_separator')
        );

        $data['breadcrumbs'][] = array(
            'href' => $this->url->link('checkout/success'),
            'text' => $this->language->get('text_success'),
            'separator' => $this->language->get('text_separator')
        );

        $data['heading_title'] = $title;

        if ($this->customer->isLogged()) {
            // 顯示的結果
            $show_text_customer = (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) ? $this->language->get('text_customer') : $this->language->get('text_customer_fail');

            $data['text_message'] = sprintf($show_text_customer, $this->url->link('account/account', '', 'SSL'), $this->url->link('account/order', '', 'SSL'), $this->url->link('account/download', '', 'SSL'), $this->url->link('information/contact'));
        } else {
            // 顯示的結果
            $show_text_guest = (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) ? $this->language->get('text_guest') : $this->language->get('text_guest_fail');

            $data['text_message'] = sprintf($show_text_guest, $this->url->link('information/contact'));
        }

        $data['button_continue'] = $this->language->get('button_continue');

        $data['continue'] = $this->url->link('common/home');


        //訂單成功後，購物車清空
        if (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) {
            $this->cart->clear();
        }

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/common/success.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/common/success.tpl';
        }else if(file_exists(DIR_TEMPLATE .'default/template/common/success.tpl')){
            $this->template = 'common/success.tpl'; // for2.2版
        }else {
            $this->template = 'default/template/common/success.tpl'; // for2.1版
        }

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view($this->template, $data));

    }

    /**
     * POST 幕後更新
     */
    public function feedback() {

        // 取時間做檔名 (YYYYMMDD)
        $file_name = 'spgateway' . date('Ymd', time()) . '.txt';

        // 檔案路徑
        $file = DIR_LOGS . $file_name;
        $fp = fopen($file, 'a');

        // 交易狀態
        $result = $_POST;
        $content = '';

        // 比對TradeSha 是否正確
        if (!$this->check_trade_sha($result)) {
            $content = 'SHA 檢查失敗';
            goto ERROR2;
        }

        // AES decrypt
        $result = $this->create_aes_decrypt($result['TradeInfo'], 
                                            trim($this->config->get('SpgatewayMPG_hash_key')), 
                                            trim($this->config->get('SpgatewayMPG_hash_iv')));
        if (empty($result)) {
            $content = 'AES 解碼失敗';
            goto ERROR2;
        }

        //	寫入 LOG
        fwrite($fp, print_r($result, true));

        // 取得該筆交易資料
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($result['MerchantOrderNo']);

        // OC是否有交易資料
        if (empty($order_info)) {
            $content = '訂單編號#'. $result['MerchantOrderNo'] . ': 資料錯誤，查無此訂單編號';
            goto ERROR2;
        }

        // 取得 付費方式 相關資料
        $this->load->model('setting/setting');
        $store_info = $this->model_setting_setting->getSetting('SpgatewayMPG', $order_info['store_id']);

        // 檢查交易狀態，大部分的失敗情況發生在此
        if (!in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) {
            $content = '訂單編號#'. $result['MerchantOrderNo'] . ': 交易狀態失敗';
            goto ERROR;
        }

        // 檢查是否有付款
        if (empty($result['PayTime'])) {
            $content = '訂單編號#'. $result['MerchantOrderNo'] . ': 訂單並未付款';
            goto ERROR;
        }

        // 檢查交易總金額
        if (intval($order_info['total']) != $result['Amt']) {
            $content = '訂單編號#'. $result['MerchantOrderNo'] . ': 交易總金額異常';
            goto ERROR;
        }

        // 不可能發生的狀態
        if (!isset($store_info['SpgatewayMPG_hash_key']) || ! isset($store_info['SpgatewayMPG_hash_iv'])) {
            $content = '訂單編號#'. $result['MerchantOrderNo'] . ': 加解密Key設定錯誤';
            goto ERROR;
        }

        // 交易早已成功
        if ($order_info['order_status_id'] == $store_info['SpgatewayMPG_order_finish_status_id']) {
            $content = '訂單編號#'. $result['MerchantOrderNo'] . ': 重複成功';
            goto ERROR2; // 交易早已成功，不用更新資料庫
        }

        // 修改訂單狀態為成功
        $this->_updateOrder($order_info, $result, $store_info);
        fclose($fp);
        exit(true);

        // GOTO LABELS
        ERROR:
        // 失敗狀況，若交易早已是成功，不可更新訂單狀態為失敗      
        if ($order_info['order_status_id'] != $store_info['SpgatewayMPG_order_finish_status_id']) {
            $this->_updateOrder($order_info, $result, $store_info);
        }

        ERROR2:
        // 回寫到錯誤日誌
        fwrite($fp, $content . "\n");
        fclose($fp);
        echo $content;
        exit(false);
    }

    /**
     * 修改訂單狀態
     *
     * @param array $order_info 訂單資訊
     * @param array $result     SPG 回傳結果
     * @param array $store_info 商店資訊
     */
    protected function _updateOrder($order_info, $result, $store_info, $notify = true) {

        // 訂單編號
        $order_id = (int) $order_info['order_id'];

        // 訂單完成狀態
        $order_status_id = in_array($result['Status'], array('SUCCESS', 'CUSTOM')) ? 
                                    (int) $store_info['SpgatewayMPG_order_finish_status_id'] : 
                                    (int) $store_info['SpgatewayMPG_order_fail_status_id'];

        // 訂單備註
        $comment = (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) ? 
                                    $this->_getComment($result) : 
                                    '<font color="red">錯誤訊息: ' . $result['Message'] . '</font><br />' . $this->_getComment($result);

	    // Follow MVC architecture, fix quantity subtraction function
        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, $notify);

    }

    /**
     * 訂單備註
     *
     * @param string $returnResult
     */
    protected function _getComment($returnResult) {
        $result = '';

        $paymentTransform = array(
            'CREDIT' => '信用卡',
            'WEBATM' => 'WebATM',
            'VACC' => 'ATM轉帳',
            'CVS' => '超商代碼繳費',
            'BARCODE' => '條碼繳費',
        );

        switch ($returnResult['PaymentType']) {

            case 'CREDIT':

                $result .= '繳費方式: ' . $paymentTransform[$returnResult['PaymentType']] . '<br />';
                $result .= '銀行回應碼: ' . $returnResult['RespondCode'] . '<br />';
                $result .= '銀行授權碼: ' . $returnResult['Auth'] . '<br />';
                $result .= '卡號前六碼: ' . $returnResult['Card6No'] . '<br />';
                $result .= '卡號末四碼: ' . $returnResult['Card4No'] . '<br />';

                //	分期
                if (isset($returnResult['Inst']) && !empty($returnResult['Inst'])) {
                    $result .= '分期期數: ' . $returnResult['Inst'] . '<br />';

                    //	首期金額
                    if (isset($returnResult['InstFirst']) && !empty($returnResult['InstFirst'])) {
                        $result .= '首期金額: ' . $returnResult['InstFirst'] . '<br />';
                    }

                    //	每期金額
                    if (isset($returnResult['InstEach']) && !empty($returnResult['InstEach'])) {
                        $result .= '每期金額: ' . $returnResult['InstEach'] . '<br />';
                    }
                }

                break;

            case 'WEBATM':
            case 'VACC':

                $result .= '繳費方式: ' . $paymentTransform[$returnResult['PaymentType']] . '<br />';
                $result .= '付款人金融機構代碼: ' . $returnResult['PayBankCode'] . '<br />';
                $result .= '付款人金融機構帳號末五碼: ' . $returnResult['PayerAccount5Code'] . '<br />';
                break;

            case 'CVS':

                $result .= '繳費方式: ' . $paymentTransform[$returnResult['PaymentType']] . '<br />';
                $result .= '繳費代碼: ' . $returnResult['CodeNo'] . '<br />';
                break;

            case 'BARCODE':

                $result .= '繳費方式: ' . $paymentTransform[$returnResult['PaymentType']] . '<br />';
                $result .= '第一段條碼: ' . $returnResult['Barcode_1'] . '<br />';
                $result .= '第二段條碼: ' . $returnResult['Barcode_2'] . '<br />';
                $result .= '第三段條碼: ' . $returnResult['Barcode_3'] . '<br />';
                break;

            default :
                break;
        }

        return $result;
    }

    /**
     * 產品資訊
     *
     * @param string $returnResult
     */
    private function getItemDescByProduct($products)
    {
        if (! isset($products)) return $this->config->get('SpgatewayMPG_item_desc');
        $item_desc = '';
        foreach ($products as $_ind => $product) {
            if (($_ind+1) == count($products)) {
                $item_desc .= $product['name'].' x '.$product['quantity'];
                break;
            }
            $item_desc .= $product['name'].' x '.$product['quantity'].', ';
        }
        // 如果長度超過 50，使用 OC 後台的預設字串
        if (strlen($item_desc) > 50) return $this->config->get('SpgatewayMPG_item_desc');
        return $item_desc;
    }

    private function check_trade_sha($return_data)
    {
        if (empty($return_data['TradeSha'])) return false;
        if (empty($return_data['TradeInfo'])) return false;
        $local_sha = $this->aes_sha256_str( $return_data['TradeInfo'], 
                                            trim($this->config->get('SpgatewayMPG_hash_key')), 
                                            trim($this->config->get('SpgatewayMPG_hash_iv')));
        if ($return_data['TradeSha'] != $local_sha) return false;
        return true;
    }

    /**
     *MPG aes加密
     *
     * @access private
     * @param array $parameter ,string $key, string $iv
     * @version 1.4
     * @return string
     */
    private function create_mpg_aes_encrypt($parameter, $key = "", $iv = "")
    {
        $return_str = '';
        if (!empty($parameter)) {
            ksort($parameter);
            $return_str = http_build_query($parameter);
        }
        return trim(
            bin2hex(
                @mcrypt_encrypt(
                    MCRYPT_RIJNDAEL_128,
                    $key,
                    $this->addpadding($return_str),
                    MCRYPT_MODE_CBC, $iv
                )
            )
        );
    }

    private function addpadding($string, $blocksize = 32) {
        $len = strlen($string);
        $pad = $blocksize - ($len % $blocksize);
        $string .= str_repeat(chr($pad), $pad);
        return $string;
    }

     /**
     *MPG sha256加密
     *
     * @access private
     * @param string $str ,string $key, string $iv
     * @version 1.4
     * @return string
     */
    private function aes_sha256_str($str, $key = "", $iv = "")
    {
        return strtoupper(hash("sha256", 'HashKey='.$key.'&'.$str.'&HashIV='.$iv));
    }

    /**
     *MPG aes解密
     *
     * @access private
     * @param array $parameter ,string $key, string $iv
     * @version 1.4
     * @return array|boolean
     */
    private function create_aes_decrypt($parameter = "", $key = "", $iv = "")
    {
        $decrypt_data = $this->strippadding(@mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key,
            hex2bin($parameter), MCRYPT_MODE_CBC, $iv));
        if(! $decrypt_data) return false;
        $dec_data = explode('&',$decrypt_data);
        foreach ($dec_data as $_ind => $value) {
            $trans_data = explode('=', $value);
            $return_data[$trans_data[0]] = $trans_data[1];
        }
        $return_data['Message'] = urldecode($return_data['Message']);
        return $return_data;
    }

    private function strippadding($string) 
    {
        $slast = ord(substr($string, -1));
        $slastc = chr($slast);
        if (preg_match("/$slastc{" . $slast . "}/", $string)) {
            $string = substr($string, 0, strlen($string) - $slast);
            return $string;
        } else {
            return false;
        }
    }

}

?>
