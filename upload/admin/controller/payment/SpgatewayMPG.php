<?php

/**
 * @author HughesWu extend Roger
 */
class ControllerPaymentSpgatewayMPG extends Controller {

    private $paymentSubfix = 'SpgatewayMPG';
    private $error = array();

    public function index() {

        $this->load->language('payment/SpgatewayMPG');
        $this->load->model('setting/setting');
        $this->load->model('localisation/language');
        $this->load->model('localisation/geo_zone');
        $this->load->model('localisation/order_status');

        $languages = $this->model_localisation_language->getLanguages();

        $data['languages'] = $languages;
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting($this->paymentSubfix, $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['text_all_zones'] = $this->language->get('text_all_zones');

        $data['entry_subfix'] = $this->paymentSubfix;
        //error_bank 改為 error_heading_title 20150303
        $data['entry_heading_title'] = $this->language->get('heading_title');
        $data['entry_test_mode'] = $this->language->get('entry_test_mode');
        $data['entry_test_fix'] = $this->language->get('entry_test_fix');
        $data['entry_merchant_id'] = $this->language->get('entry_merchant_id');
        $data['entry_hash_key'] = $this->language->get('entry_hash_key');
        $data['entry_hash_iv'] = $this->language->get('entry_hash_iv');
        $data['entry_item_desc'] = $this->language->get('entry_item_desc');
        $data['entry_item_desc_content'] = $this->language->get('entry_item_desc_content');
        $data['entry_order_status'] = $this->language->get('entry_order_status');
        $data['entry_order_finish_status'] = $this->language->get('entry_order_finish_status');
        $data['entry_order_fail_status'] = $this->language->get('entry_order_fail_status');
        $data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['payment_content'] = $this->language->get('payment_content');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['warning2'])) {
            $data['error_warning2'] = $this->error['warning2'];
        } else {
            $data['error_warning2'] = '';
        }

        if (isset($this->error['warning3'])) {
            $data['error_warning3'] = $this->error['warning3'];
        } else {
            $data['error_warning3'] = '';
        }

        if (isset($this->error['warning4'])) {
            $data['error_warning4'] = $this->error['warning4'];
        } else {
            $data['error_warning4'] = '';
        }

        if (isset($this->error['warning5'])) {
            $data['error_warning5'] = $this->error['warning5'];
        } else {
            $data['error_warning5'] = '';
        }

        $data['tab_general'] = $this->language->get('tab_general');

        $data['error_warning'] = (isset($this->error['warning'])) ? $this->error['warning'] : '';
        $data['error_warning2'] = (isset($this->error['warning2'])) ? $this->error['warning2'] : '';
        $data['error_warning3'] = (isset($this->error['warning3'])) ? $this->error['warning3'] : '';
        $data['error_warning4'] = (isset($this->error['warning4'])) ? $this->error['warning4'] : '';
        $data['error_warning5'] = (isset($this->error['warning5'])) ? $this->error['warning5'] : '';

        //error_bank 改為 error_heading_title 20150303
        $data['error_heading_title'] = (isset($this->error['heading_title'])) ? $this->error['heading_title'] : '';


        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/SpgatewayMPG', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $data['action'] = $this->url->link('payment/SpgatewayMPG', 'token=' . $this->session->data['token'], 'SSL');
        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');


        //固定語言檔 20150303
        $data['payment_description'] = (isset($this->request->post[$this->paymentSubfix . '_description'])) ? $this->request->post[$this->paymentSubfix . '_description'] : $this->config->get($this->paymentSubfix . '_description');


        $data['order_status_id'] = (isset($this->request->post[$this->paymentSubfix . '_order_status_id'])) ? $this->request->post[$this->paymentSubfix . '_order_status_id'] : $this->config->get($this->paymentSubfix . '_order_status_id');

        $data['order_finish_status_id'] = (isset($this->request->post[$this->paymentSubfix . '_order_finish_status_id'])) ? $this->request->post[$this->paymentSubfix . '_order_finish_status_id'] : $this->config->get($this->paymentSubfix . '_order_finish_status_id');

        $data['order_fail_status_id'] = (isset($this->request->post[$this->paymentSubfix . '_order_fail_status_id'])) ? $this->request->post[$this->paymentSubfix . '_order_fail_status_id'] : $this->config->get($this->paymentSubfix . '_order_fail_status_id');

        $data['geo_zone_id'] = (isset($this->request->post[$this->paymentSubfix . '_geo_zone_id'])) ? $this->request->post[$this->paymentSubfix . '_geo_zone_id'] : $this->config->get($this->paymentSubfix . '_geo_zone_id');

        $data['status'] = (isset($this->request->post[$this->paymentSubfix . '_status'])) ? $this->request->post[$this->paymentSubfix . '_status'] : $this->config->get($this->paymentSubfix . '_status');

        $data['sort_order'] = (isset($this->request->post[$this->paymentSubfix . '_sort_order'])) ? $this->request->post[$this->paymentSubfix . '_sort_order'] : $this->config->get($this->paymentSubfix . '_sort_order');

        $data['test_mode'] = (isset($this->request->post[$this->paymentSubfix . '_test_mode'])) ? $this->request->post[$this->paymentSubfix . '_test_mode'] : $this->config->get($this->paymentSubfix . '_test_mode');

        $data['test_fix'] = (isset($this->request->post[$this->paymentSubfix . '_test_fix'])) ? $this->request->post[$this->paymentSubfix . '_test_fix'] : $this->config->get($this->paymentSubfix . '_test_fix');

        $data['merchant_id'] = (isset($this->request->post[$this->paymentSubfix . '_merchant_id'])) ? $this->request->post[$this->paymentSubfix . '_merchant_id'] : $this->config->get($this->paymentSubfix . '_merchant_id');

        $data['hash_key'] = (isset($this->request->post[$this->paymentSubfix . '_hash_key'])) ? $this->request->post[$this->paymentSubfix . '_hash_key'] : $this->config->get($this->paymentSubfix . '_hash_key');

        $data['hash_iv'] = (isset($this->request->post[$this->paymentSubfix . '_hash_iv'])) ? $this->request->post[$this->paymentSubfix . '_hash_iv'] : $this->config->get($this->paymentSubfix . '_hash_iv');

        $data['item_desc'] = (isset($this->request->post[$this->paymentSubfix . '_item_desc'])) ? $this->request->post[$this->paymentSubfix . '_item_desc'] : $this->config->get($this->paymentSubfix . '_item_desc');

        $data['payment_credit'] = (isset($this->request->post[$this->paymentSubfix . '_payment_credit'])) ? $this->request->post[$this->paymentSubfix . '_payment_credit'] : $this->config->get($this->paymentSubfix . '_payment_credit');

        $data['payment_credit_inst'] = (isset($this->request->post[$this->paymentSubfix . '_payment_credit_inst'])) ? $this->request->post[$this->paymentSubfix . '_payment_credit_inst'] : $this->config->get($this->paymentSubfix . '_payment_credit_inst');

        $data['payment_webatm'] = (isset($this->request->post[$this->paymentSubfix . '_payment_webatm'])) ? $this->request->post[$this->paymentSubfix . '_payment_webatm'] : $this->config->get($this->paymentSubfix . '_payment_webatm');

        $data['payment_vacc'] = (isset($this->request->post[$this->paymentSubfix . '_payment_vacc'])) ? $this->request->post[$this->paymentSubfix . '_payment_vacc'] : $this->config->get($this->paymentSubfix . '_payment_vacc');

        $data['payment_cvs'] = (isset($this->request->post[$this->paymentSubfix . '_payment_cvs'])) ? $this->request->post[$this->paymentSubfix . '_payment_cvs'] : $this->config->get($this->paymentSubfix . '_payment_cvs');

        $data['payment_barcode'] = (isset($this->request->post[$this->paymentSubfix . '_payment_barcode'])) ? $this->request->post[$this->paymentSubfix . '_payment_barcode'] : $this->config->get($this->paymentSubfix . '_payment_barcode');

        $data['header'] = $this->load->controller('common/header');

        $data['column_left'] = $this->load->controller('common/column_left');

        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('payment/SpgatewayMPG.tpl', $data));
    }

    protected function validate() {

        if (!$this->user->hasPermission('modify', 'payment/SpgatewayMPG')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post[$this->paymentSubfix . '_merchant_id']) {
            $this->error['warning2'] = $this->language->get('error_merchant_id');
        }

        if (!$this->request->post[$this->paymentSubfix . '_hash_key']) {
            $this->error['warning3'] = $this->language->get('error_hash_key');
        }

        if (!$this->request->post[$this->paymentSubfix . '_hash_iv']) {
            $this->error['warning4'] = $this->language->get('error_hash_iv');
        }

        if (!$this->request->post[$this->paymentSubfix . '_item_desc']) {
            $this->error['warning5'] = $this->language->get('error_item_desc');
        } else if (mb_strlen($this->request->post[$this->paymentSubfix . '_item_desc']) > 50) {
            $this->error['warning5'] = $this->language->get('error_item_desc_length');
        } else if (!preg_match("/^[\x80-\xff_a-zA-Z0-9,_ ]+$/", $this->request->post[$this->paymentSubfix . '_item_desc'])) {
            $this->error['warning5'] = $this->language->get('error_item_desc_content');
        }

        return !$this->error;
    }

}
