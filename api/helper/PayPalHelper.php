<?php

  session_start();
  include_once('HttpHelper.php');

  class PayPalHelper {

    private $_http = null;
    private $_apiUrl = null;
    private $_token = null;

    public function __construct() {
      $this->_http = new HttpHelper;
      $this->_apiUrl = PAYPAL_ENDPOINTS[PAYPAL_ENVIRONMENT];
    }

    private function _createApiUrl($resource) {
      if($resource == 'oauth2/token') {
        return $this->_apiUrl . "/v1/" . $resource;
      } else {
        return $this->_apiUrl . "/v2/" . $resource;
      }
    }

    private function _getToken() {
      $this->_http->resetHelper();
      $this->_http->setUrl($this->_createApiUrl("oauth2/token"));
      $this->_http->setAuthentication(PAYPAL_CREDENTIALS[PAYPAL_ENVIRONMENT]['client_id'] . ":" . PAYPAL_CREDENTIALS[PAYPAL_ENVIRONMENT]['client_secret']);
      $this->_http->setBody("grant_type=client_credentials");
      $returnData = $this->_http->sendRequest();
      $this->_token = $returnData["response"]['access_token'];
    }

    private function _createOrder($postData) {
      $this->_http->resetHelper();
      $this->_http->addHeader("Content-Type: application/json");
      $this->_http->addHeader("Authorization: Bearer " . $this->_token);
      $this->_http->setUrl($this->_createApiUrl("checkout/orders"));
      $this->_http->setBody($postData);
      return $this->_http->sendRequest();
    }

    private function _getOrderDetails($orderId) {
      $this->_http->resetHelper();
      $this->_http->addHeader("Content-Type: application/json");
      $this->_http->addHeader("Authorization: Bearer " . $this->_token);
      $this->_http->setUrl($this->_createApiUrl("checkout/orders/" . $orderId));
      return $this->_http->sendRequest();
    }

    private function _patchOrder($orderId, $postData) {
      $this->_http->resetHelper();
      $this->_http->addHeader("Content-Type: application/json");
      $this->_http->addHeader("Authorization: Bearer " . $this->_token);
      $this->_http->setUrl($this->_createApiUrl("checkout/orders/" . $orderId));
      $this->_http->setPatchBody($postData);
      return $this->_http->sendRequest();
    }

    private function _captureOrder($orderId) {
      $this->_http->resetHelper();
      $this->_http->addHeader("Content-Type: application/json");
      $this->_http->addHeader("Authorization: Bearer " . $this->_token);
//	  $this->_http->addHeader("PayPal-Mock-Response: {'mock_application_codes': 'DUPLICATE_INVOICE_ID'}");
//    $this->_http->addHeader("PayPal-Mock-Response: {'mock_application_codes': 'INSTRUMENT_DECLINED'}");
      $this->_http->setUrl($this->_createApiUrl("checkout/orders/" . $orderId . "/capture"));
      $postData='{}';
      $this->_http->setBody($postData);
      return $this->_http->sendRequest();
    }

    public function orderCreate($postData) {
      if($this->_token === null) {
        $this->_getToken();
      }
      $returnData = $this->_createOrder($postData);
      return $returnData;
    }

    public function orderGet($orderId) {
      if($this->_token === null) {
        $this->_getToken();
      }
      return $this->_getOrderDetails($orderId);
    }

    public function orderPatch($orderId, $postData) {
      if($this->_token === null) {
        $this->_getToken();
      }
      $returnData = $this->_patchOrder($orderId, $postData);
      return array(
        "ack" => true,
        "data" => $returnData
      );
    }

    public function orderCapture($orderId) {
      if($this->_token === null) {
        $this->_getToken();
      }
      return $this->_captureOrder($orderId);
    }

  }

?>
