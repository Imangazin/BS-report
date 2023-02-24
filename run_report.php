<?php
//BS authorization
require_once("info.php");
require_once 'lib/D2LAppContextFactory.php';
//BS API calls
require_once 'doValenceRequest.php';

//get current date
$endDate = date("Y-m-d");

//week before from current date
$startDate = date_format(date_sub(date_create($endDate),date_interval_create_from_date_string("7 days")),"Y-m-d");

//call parameters, OrgUnitId, start and end dates
$filters = array(array("Name"=> "parentOrgUnitId", "Value"=> $config['OrgUnitId']),
		 array("Name"=> "startDate", "Value"=> $startDate),
		 array("Name"=> "endDate", "Value"=> $endDate));

$json_params = array("DataSetId"=> $config['reportId'], "Filters"=> $filters);

//requesting the report, it will generate the report and provides a job id
$request_report = doValenceRequest('POST','/d2l/api/lp/' . $config['LP_Version'] . '/dataExport/create', $json_params);

//pausing for 30 seconds to give BS to generate the report, propably no need, just following GUI
sleep(30);

//getting jobId
$exportJobId = $request_report['response']->ExportJobId;

//download the report as zip file
$get_report = doValenceRequest('GET', '/d2l/api/lp/' . $config['LP_Version'] . '/dataExport/download/' . $exportJobId, array(), 1);


//sending email, script by ChatGPT :), hope it works

// Recipient email address
$to = $config['emailTo'];

// Email subject
$subject = 'Awards Issued for Health and Safety Training';

// Email message
$message = '<html><body>Hello,<br><p>Please find attached copy of the Awards Issued for Health and Safety Training from '.$startDate .' to '. $endDate .'.</p><br>CPI</body></html>';

// File path of the zip file
$file_path = 'award_report.zip';

// Read the file into a variable
$file_content = file_get_contents($file_path);

// Encode the file content for email transmission
$file_encoded = base64_encode($file_content);

// Email headers
$headers = array(
    'From: '. $config["senderName"] .' <'.$config["replyTo"].'>',
    'Reply-To: '. $config["replyTo"],
    'Content-Type: multipart/mixed; boundary="boundary1"'
);

// Email body
$body = "--boundary1\r\n";
$body .= "Content-Type: text/html; charset=UTF-8\r\n";
$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$body .= "$message\r\n\r\n";
$body .= "--boundary1\r\n";
$body .= "Content-Type: application/zip; name=\"award_report.zip\"\r\n";
$body .= "Content-Transfer-Encoding: base64\r\n";
$body .= "Content-Disposition: attachment; filename=\"award_report.zip\"\r\n\r\n";
$body .= "$file_encoded\r\n\r\n";
$body .= "--boundary1--";

// Send the email

if($request_report['Code']==200){
    mail($to, $subject, $body, implode("\r\n", $headers));	
} else {
   echo "Error downloading the report.";
}

?>
