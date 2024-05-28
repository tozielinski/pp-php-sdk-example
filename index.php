<?php
include_once 'api/config/Config.php';

$payload = file_get_contents("payload.json");

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<!-- <meta http-equiv="Content-Security-Policy" content="form-action https://www.sandbox.paypal.com/checkoutnow" /> -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
		<title>PayPal JS SDK Standard Integration</title>
	</head>
	<body>

	<h1>Smart Buttons Integration</h1>
	<div id="payload-container"></div>
	<div id="paypal-button-container" style="max-width: 440px"></div>
	<hr/>
	<div id="response-container"></div>
	<script src="https://www.paypal.com/sdk/js?client-id=<?=PAYPAL_CREDENTIALS[PAYPAL_ENVIRONMENT]['client_id']?>&currency=EUR"></script>
	<script>
		function writeResponse (containerTitle, summaryTitle, content) {
			const container = document.getElementById(containerTitle);
			const details = document.createElement("details");
			const summary = document.createElement("summary");
			summary.innerHTML = summaryTitle;
			const pre = document.createElement("pre");
			pre.innerHTML = '<p>'+JSON.stringify(content, null, 2)+'</p>';
			const hr = document.createElement("hr");
			container.appendChild(details);
			details.appendChild(summary);
			details.appendChild(pre);
			container.appendChild(hr);
		}
		window.paypal.Buttons({
			createOrder: function () {
				let formData = new FormData();
				formData.append("payload", JSON.stringify(<?= $payload ?>));
				writeResponse("payload-container", "Payload", <?= $payload ?>)
				return fetch('<?= $rootPath.URL['services']['orderCreate']?>', {
					method: 'POST'
					,body: formData
				}).then(function(response) {
					return response.json();
				}).then(function(resJson) {
					if (resJson.response?.id) {
						writeResponse("response-container", "Create Order Response", resJson.response)
						console.log('create response', resJson.response);
						return resJson.response.id;
					} else {
						console.error({callback: "createOrder", serverResponse: resJson.response}, JSON.stringify(resJson.response, null, 2));
						const errorDetail = resJson.response?.details?.[0];
						writeResponse("response-container", "ERROR",
							`Could not initiate PayPal Checkout...<br><br>${
								errorDetail?.issue || ""
							} ${
								errorDetail?.description || resJson.response?.message || ""
							} ` +
							(resJson.response?.debug_id ? `(${resJson.response.debug_id})` : "")
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
						writeResponse("response-container", "ERROR",
							`Sorry, your transaction will be restarted. <br><br>${
								errorDetail?.description || ""
							} (${resJson.response?.debug_id || ""})`
						);
						return actions.restart();
					} else if (errorDetail || !transaction || transaction?.status === "DECLINED" || transaction?.status === "FAILED") {
					// Any other error (non-recoverable)
						console.error({callback: "onApprove", response: resJson.response}, JSON.stringify(resJson.response, null, 2));
						// Display a clear failure message informing the user the transaction failed.
						writeResponse("response-container", "ERROR",
							`Sorry, your transaction could not be processed. <br><br>${
								errorDetail?.description || ""
							} (${resJson.response?.debug_id || ""})`
						);
					} else if (transaction?.status === "COMPLETED") {
					// Successful transaction!
					// Show a success message to the payer somewhere on this page...
						writeResponse("response-container", "Capture Payment For Order Response", resJson.response)
						console.log('capture response', resJson.response, JSON.stringify(resJson.response, null, 2));
					} else {
						writeResponse("response-container", "ERROR",
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
