<?php
include_once 'api/config/Config.php';

$payload = file_get_contents("payload.json");

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta http-equiv="Content-Security-Policy" content="form-action https://www.sandbox.paypal.com/checkoutnow" />
		<title>PayPal JS SDK Standard Integration</title>
	</head>
	<body>

	<h1>Smart Buttons Integration</h1>
	<div id="paypal-button-container" style="max-width: 440px"></div>
	<hr/>
	<p id="result-message"></p>
	<script src="https://www.paypal.com/sdk/js?client-id=<?=PAYPAL_CREDENTIALS[PAYPAL_ENVIRONMENT]['client_id']?>&currency=EUR"></script>
	<script>
		function resultMessage(message, options = { hideButtons: false }) {
			const container = document.getElementById("paypal-button-container");
			if (options.hideButtons) container.style.display = "none";
			const p = document.createElement("p");
			p.innerHTML = `<big>${message}</big>`;
			container.parentNode.appendChild(p);
		}
		window.paypal.Buttons({
			createOrder: function () {
				let formData = new FormData();
				formData.append("payload", JSON.stringify(<?= $payload ?>));

				return fetch('<?= $rootPath.URL['services']['orderCreate']?>', {
					method: 'POST'
					,body: formData
				}).then(function(response) {
					return response.json();
				}).then(function(resJson) {
					if (resJson.response?.id) {
						console.log('create response', resJson.response);
						return resJson.response.id;
					} else {
						console.error({callback: "createOrder", serverResponse: resJson.response}, JSON.stringify(resJson.response, null, 2));
						const errorDetail = resJson.response?.details?.[0];
						resultMessage(
							`Could not initiate PayPal Checkout...<br><br>${
								errorDetail?.issue || ""
							} ${
								errorDetail?.description || resJson.response?.message || ""
							} ` +
							(resJson.response?.debug_id ? `(${resJson.response.debug_id})` : ""),
							{ hideButtons: true }
						);
					}
				}).catch((error) => {
					throw new Error(`createOrder callback failed - ${error.message}`);
				});
			},
			onApprove: function (data, actions) {
				return fetch('<?= $rootPath.URL['services']['orderCapture']?>?id='+data.orderID, {
					method: 'POST'
				}).then(function(response) {
					return response.json();
				}).then(function(resJson) {
					const transaction = resJson.response?.purchase_units?.[0]?.payments?.captures?.[0] ||
						resJson.response?.purchase_units?.[0]?.payments?.authorizations?.[0];
					const errorDetail = resJson.response?.details?.[0];
					if (errorDetail?.issue === "INSTRUMENT_DECLINED") {
					// recoverable state, per https://developer.paypal.com/docs/checkout/standard/customize/handle-funding-failures/
						return actions.restart();
					} else if (errorDetail || !transaction || transaction?.status === "DECLINED" || transaction?.status === "FAILED") {
					// Any other error (non-recoverable)
						console.error({callback: "onApprove", response: resJson.response}, JSON.stringify(resJson.response, null, 2));
						// Display a clear failure message informing the user the transaction failed.
						resultMessage(
							`Sorry, your transaction could not be processed. <br><br>${
								errorDetail?.description || ""
							} (${resJson.response?.debug_id || ""})`
						);
					} else if (transaction?.status === "COMPLETED") {
					// Successful transaction!
					// Show a success message to the payer somewhere on this page...
						resultMessage(`<h3>Thank you for your payment!</h3>`);
						// Or, go to another URL with:  window.location.href = 'thank_you.html';
						// Optionally show your own order number/invoice_id to the payer (if set for this transaction)
						if (transaction?.invoice_id)
						resultMessage(
							`Your order number: ${transaction.invoice_id}`
						);
						// For demo purposes:
						console.log('capture response', resJson.response, JSON.stringify(resJson.response, null, 2));
						resultMessage(
							`Transaction ${transaction.status}: ${transaction.id}<br><br>See console for all available details`
						);
					} else {
						resultMessage(
							`Unusual error or transaction status. <br><br>${
								errorDetail?.description || ""
							} (${resJson.response?.debug_id || ""})`
						);
					}
				});
			},
			onCancel(data) {
				console.warn("canceled: ", data);
			},
			onError(err) {
				console.error({callback: "onError", errorObject: err});
			}
		})
		.render("#paypal-button-container")
		.catch((error) => {console.error("failed to render the PayPal Buttons", error)});
	</script>
  </body>
</html>
