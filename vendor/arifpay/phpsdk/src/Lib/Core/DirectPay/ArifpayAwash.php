<?php

namespace Arifpay\Phpsdk\Lib\Core\DirectPay;

use Arifpay\Phpsdk\ArifPay;
use Arifpay\Phpsdk\Helper\ArifpaySupport;
use Arifpay\Phpsdk\Lib\ArifpayAPIResponse;
use Arifpay\Phpsdk\Lib\ArifpayTransferResponse;
use Arifpay\Phpsdk\Lib\Exception\ArifpayNetworkException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use League\Flysystem\ConnectionErrorException;

class ArifpayAwash
{
    public $http_client;

    public function __construct($http_client)
    {
        $this->http_client = $http_client;
    }

    public function pay($checksessionID, $phoneNumber): ArifpayTransferResponse
    {
        try {
            $response = $this->http_client->post(Arifpay::API_VERSION."/checkout/awash/direct/transfer", [
                RequestOptions::JSON => [
                    "sessionId" => $checksessionID,
                    "phoneNumber" => $phoneNumber,
                ],
            ]);

            $arifAPIResponse = ArifpayAPIResponse::fromJson(json_decode($response->getBody(), true));

            return ArifpayTransferResponse::fromJson($arifAPIResponse->data);
        } catch (ConnectionErrorException $e) {
            throw new ArifpayNetworkException();
        } catch (ClientException $e) {
            ArifpaySupport::__handleException($e);

            throw $e;
        }
    }

    public function verify($checksessionID, $otp, $fail = false): ArifpayTransferResponse
    {
        try {
            $response = $this->http_client->post(Arifpay::API_VERSION."/checkout/awash/direct/verifyOTP", [
                RequestOptions::JSON => [
                    "sessionId" => $checksessionID,
                    "paymentRunMode" => $fail ? "FAIL" : "SUCCESS",
                    "otp" => $otp,
                ],
            ]);

            $arifAPIResponse = ArifpayAPIResponse::fromJson(json_decode($response->getBody(), true));

            return ArifpayTransferResponse::fromJson($arifAPIResponse->data);
        } catch (ConnectionErrorException $e) {
            throw new ArifpayNetworkException();
        } catch (ClientException $e) {
            ArifpaySupport::__handleException($e);

            throw $e;
        }
    }
}
