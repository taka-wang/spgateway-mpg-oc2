<?php

/**
 * @author HughesWu extend Roger
 */
class ControllerPaymentSpgatewayMPG extends Controller {

    private $hashKey, $hashIV;

    public function index() {

        $data['button_confirm'] = $this->language->get('button_confirm');

        $this->load->model('checkout/order');

        // 是否為測試模式
        $isTestMode = $this->config->get('SpgatewayMPG_test_mode');

        $data['action'] = $isTestMode ? 'https://ccore.spgateway.com/MPG/mpg_gateway' : 'https://core.spgateway.com/MPG/mpg_gateway';

        // AJAX 訂單送回 Spgateway 之前執行
        $data['ajaxUrl'] = $this->url->link('payment/SpgatewayMPG/confirm');

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
        return $this->load->view($this->template, $data);
    }

    /**
     * 要傳遞資料
     * @return array 一維陣列 key => Input Post Name, value => Input Post Value
     */
    protected function _composePostInput() {

        $result = array();

        $today = time();

        // 訂購   資料
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        // 訂購人 資料
        $user_info = $this->_getUserInfo();

        /**
         * P2G 各項設定參數
         */
        $result['MerchantID'] = trim($this->config->get('SpgatewayMPG_merchant_id'));
        $this->hashKey = trim($this->config->get('SpgatewayMPG_hash_key'));
        $this->hashIV = trim($this->config->get('SpgatewayMPG_hash_iv'));

        $result['RespondType'] = "String";
        $result['TimeStamp'] = $today;
        $result['Version'] = "1.1";
        $result['MerchantOrderNo'] = $order_info['order_id'];
        $result['Amt'] = intval($order_info['total']);
        $result['ItemDesc'] = $this->config->get('SpgatewayMPG_item_desc');
        $result['Email'] = $user_info['Email'];
        $result['LoginType'] = 0;

        $result['ReturnURL'] = $this->url->link('payment/SpgatewayMPG/feedbackShow', '', 'SSL');
        $result['NotifyURL'] = $this->url->link('payment/SpgatewayMPG/feedback', '', 'SSL');
        $result['ClientBackURL'] = $this->url->link('common/home', '', 'SSL');

        // 取得檢查碼
        $result['CheckValue'] = $this->_getCheckValue($result);

        return $result;
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
        $result['ShowName'] = strpos($this->language->get('code'), 'zh-TW') !== true ? $result['LastName'] . $result['FirstName'] : $result['FirstName'] . $result['LastName'];

        return $result;
    }

    /**
     * 取得檢查碼
     *
     * @param array  $params    訂單參數
     *
     * @return string checkValue
     */
    protected function _getCheckValue($params) {
        // 要重新排序的參數
        $sortArray = array(
            'MerchantID' => $params['MerchantID'],
            'TimeStamp' => $params['TimeStamp'],
            'MerchantOrderNo' => $params['MerchantOrderNo'],
            'Version' => $params['Version'],
            'Amt' => $params['Amt'],
        );

        ksort($sortArray);

        $check_merstr = http_build_query($sortArray);

        $checkValue_str = 'HashKey=' . $this->hashKey . '&' . $check_merstr . '&HashIV=' . $this->hashIV;

        return strtoupper(hash("sha256", $checkValue_str));
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

        if (isset($result['Status']) && !empty($result['Status'])) {
            /**
             * 頁面資料
             */
            $this->language->load('checkout/success');
            $this->language->load('checkout/success_error');

            // 顯示的 Title
            $title = (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) ? $this->language->get('heading_title') : $this->language->get('heading_title_fail') . $result['Message'];

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
                $this->template = 'common/success.tpl';//for2.2版
            }else {
                $this->template = 'default/template/common/success.tpl'; //for2.1版
            }

            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view($this->template, $data));
        } else {
            $url = $this->url->link('common/home', '', 'SSL');
            header("location:$url");
            exit;
        }
    }

    /**
     * POST 幕後更新
     */
    public function feedback() {

        // 取時間做檔名 (YYYYMMDD)
        $file_name = date('Ymd', time()) . '.txt';

        // 檔案路徑
        $file = DIR_LOGS . $file_name;

        $fp = fopen($file, 'a');

        // 交易狀態
        $result = $_POST;

        //	寫入 LOG
        fwrite($fp, print_r($result, true));

        // 取得該筆交易資料
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($result['MerchantOrderNo']);

        // 是否有資料
        if (!empty($order_info)) {

            /**
             *  取得 付費方式 相關資料
             */
            $this->load->model('setting/setting');

            $store_info = $this->model_setting_setting->getSetting('SpgatewayMPG', $order_info['store_id']);

            // 1. 檢查交易狀態
            if (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) {

                // 2. 檢查交易總金額
                if (intval($order_info['total']) == $result['Amt']) {

                    /**
                     *  3. 檢查 checkCode
                     */
                    $check = array(
                        "MerchantID" => $result['MerchantID'],
                        "Amt" => $result['Amt'],
                        "MerchantOrderNo" => $result['MerchantOrderNo'],
                        "TradeNo" => $result['TradeNo']
                    );

                    ksort($check);

                    $check_str = http_build_query($check);

                    /**
                     * 是否有設定參數
                     */
                    $checkCode = '';

                    if (!isset($store_info['SpgatewayMPG_hash_key']) || !isset($store_info['SpgatewayMPG_hash_iv'])) {
                        $content = $result['MerchantOrderNo'] . ': Hash Setting Errpr';
                        fwrite($fp, $content . "\n");
                        fclose($fp);
                        echo $content;
                        die;
                    } else {
                        $checkCode = 'HashIV=' . $store_info['SpgatewayMPG_hash_iv'] . '&' . $check_str . '&HashKey=' . $store_info['SpgatewayMPG_hash_key'];
                    }

                    $checkCode = strtoupper(hash("sha256", $checkCode));

                    // 如果三次驗證都通過
                    if ($checkCode == $result['CheckCode']) {

                        if ($order_info['order_status_id'] != $store_info['SpgatewayMPG_order_finish_status_id']) {

                            // 修改訂單狀態
                            $this->_updateOrder($order_info, $result, $store_info);
                        }
                    } else {
                        $content = $result['MerchantOrderNo'] . ': ERROR_3';
                        fwrite($fp, $content . "\n");
                        fclose($fp);
                        echo $content;
                        die;
                    }
                } else {
                    $content = $result['MerchantOrderNo'] . ': ERROR_2';
                    fwrite($fp, $content . "\n");
                    fclose($fp);
                    echo $content;
                    die;
                }
            } else {

                $content = $result['MerchantOrderNo'] . ': ERROR_1';
                echo $content;

                fwrite($fp, $content . "\n");

                // 修改訂單狀態 (Only Credit or WebAtm)
                if (in_array($result['PaymentType'], array('CREDIT', 'WEBATM'))) {
                    $this->cart->clear();
                }

                fclose($fp);
                die;
            }
        } else {
            fwrite($fp, $result['MerchantOrderNo'] . ": DataError\n");
        }

        fclose($fp);
    }

    /**
     * 修改訂單狀態
     *
     * @param array $order_info 訂單資訊
     * @param array $result     P2G 回傳結果
     * @param array $store_info 商店資訊
     */
    protected function _updateOrder($order_info, $result, $store_info) {

        // 訂單編號
        $order_id = (int) $order_info['order_id'];

        // 訂單完成狀態
        $order_status_id = in_array($result['Status'], array('SUCCESS', 'CUSTOM')) ? (int) $store_info['SpgatewayMPG_order_finish_status_id'] : (int) $store_info['SpgatewayMPG_order_fail_status_id'];

        // 訂單備註
        $comment = (in_array($result['Status'], array('SUCCESS', 'CUSTOM'))) ? $this->_getComment($result) : $this->_getComment($result) . '錯誤訊息: ' . $result['Message'];

	// Follow MVC architecture, fix quantity subtraction function
        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment);

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

    public function clearCustomerCart($customer_id) {
        $this->db->query("UPDATE `" . DB_PREFIX . "customer` SET cart = '' WHERE customer_id = '" . (int) $customer_id . "'");
        unset($this->session->data['customer_id']);
    }

}

?>
