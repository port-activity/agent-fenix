<?php
namespace SMA\PAA\AGENT;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once "init.php";

use SMA\PAA\AGENT\FENIX\Fenix;
use SMA\PAA\SERVICE\StateService;
use SMA\PAA\AINO\AinoClient;

$apiKey = getenv("API_KEY");
$apiUrl = getenv("API_URL");
$locodes = array_map(function ($l) {
    return trim($l);
}, explode(",", getenv("LOCODES")));
$ainoKey = getenv("AINO_API_KEY");

$apiParameters = ["imo", "vessel_name", "time_type", "state", "time", "payload"];


echo "Polling config is:";
$config = require(__DIR__ . "/SMA/PAA/AGENT/FENIX/FenixConfig.php");
print_r($config);

$apiConfig = new ApiConfig($apiKey, $apiUrl, $apiParameters);

echo "Starting job.\n";

$service = new StateService();
$imos = $service->get(StateService::LATEST_PORT_CALL_IMOS) ?: [];

echo "Found " . sizeof($imos) . " active portcalls.\n";

$aino = null;
if ($ainoKey) {
    $toApplication = parse_url($apiUrl, PHP_URL_HOST);
    $aino = new AinoClient($ainoKey, "Fenix", $toApplication);
}
$agent = new Fenix(null, null, $aino);

$aino = null;
if ($ainoKey) {
    $aino = new AinoClient($ainoKey, "Fenix service", "Fenix");
}
$ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

foreach ($imos as $imo) {
    if ($imo > 100000000) {
        echo "Skipping since fake IMO " . $imo . " doesn't exists outside this application.\n";
    } else {
        echo "Fetching Fenix data for IMO: " . $imo ."\n";
        try {
            $counts = $agent->execute($apiConfig, $imo, $locodes);
            if (isset($aino)) {
                $aino->succeeded(
                    $ainoTimestamp,
                    "Fenix agent succeeded",
                    "Batch run",
                    "timestamp",
                    ["imo" => $imo],
                    $counts
                );
            }
        } catch (\Exception $e) {
            error_log($e->getMessage());
            error_log($e->getTraceAsString());
            if (isset($aino)) {
                $aino->failure($ainoTimestamp, "Fenix agent failed", "Batch run", "timestamp", ["imo" => $imo], []);
            }
        }
    }
}
echo "All done.\n";
