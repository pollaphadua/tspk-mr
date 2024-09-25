<?php
require_once '../../vendor/autoload.php';

use Aspose\Words\WordsApi;

$wordsApi = new WordsApi('####-####-####-####-####', '##################');

$requestDocument = "truckfrom/TRUCKCONTROL_TCN2303290011.pdf";
$requestCompressOptions = new CompressOptions(array(
    "images_quality" => 75,
    "images_reduce_size_factor" => 1,
));
$compressDocumentRequest = new CompressDocumentOnlineRequest(
    $requestDocument, $requestCompressOptions, NULL, NULL, NULL, NULL
);
$compressDocument = $wordsApi->compressDocumentOnline($compressDocumentRequest);

$convertDocument = new ConvertDocumentRequest(
    $compressDocument->document()->values(), "pdf", NULL, NULL, NULL, NULL
);
$wordsApi->convertDocument($convertDocument);