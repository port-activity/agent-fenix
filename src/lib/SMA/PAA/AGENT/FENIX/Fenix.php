<?php
namespace SMA\PAA\AGENT\FENIX;

use SMA\PAA\CURL\ICurlRequest;
use SMA\PAA\RESULTPOSTER\IResultPoster;
use SMA\PAA\AGENT\ApiConfig;
use SMA\PAA\AINO\AinoClient;

use Exception;
use DateTimeInterface;
use DateTime;
use DateInterval;
use SMA\PAA\CURL\CurlRequest;
use SMA\PAA\RESULTPOSTER\ResultPoster;

class Fenix
{
    private $config;
    private $curlRequest;
    private $resultPoster;
    private $aino;

    private $fromDate   = "";
    private $toDate     = "";

    public function __construct(
        ICurlRequest $curlRequest = null,
        IResultPoster $resultPoster = null,
        AinoClient $aino = null,
        array $config = null
    ) {
        $this->curlRequest = $curlRequest ?: new CurlRequest();
        $this->resultPoster = $resultPoster ?: new ResultPoster(new CurlRequest());
        $this->aino = $aino;
        $this->config = $config !== null ? $config : require("FenixConfig.php");
        date_default_timezone_set("UTC");
        $this->fromDate = $this->tsFromNow($this->config["from_offset_minutes"]);
        $this->toDate = $this->tsAfterNow($this->config["to_offset_minutes"]);
    }

    private function tsFromNow($offsetMinutes)
    {
        $dateInterval = new DateInterval("PT". $offsetMinutes ."M");
        $date = new DateTime();
        $date->sub($dateInterval);
        return str_replace("+00:00", ".000Z", $date->format(DateTimeInterface::ATOM));
    }

    private function tsAfterNow($offsetMinutes)
    {
        $dateInterval = new DateInterval("PT". $offsetMinutes ."M");
        $date = new DateTime();
        $date->add($dateInterval);
        return str_replace("+00:00", ".000Z", $date->format(DateTimeInterface::ATOM));
    }

    public function execute(ApiConfig $apiConfig, int $imo, array $inboundLocodes)
    {
        $rawResults = $this->fetchResults($imo);
        $parsedResults = $this->parseResults($rawResults, $inboundLocodes);
        return $this->postResults($apiConfig, $parsedResults);
    }

    private function payload($imo)
    {
        return  [
            "imo"       => "" . $imo, // required as string
            "startDate" => $this->fromDate,
            "endDate"   => $this->toDate
        ];
    }
    private function fetchResults($imo): array
    {
        $payload = json_encode($this->payload($imo));
        $this->curlRequest->init(getenv("FENIX_REQUEST_URL"));
        #$this->curlRequest->setOption(CURLOPT_VERBOSE, true);
        $this->curlRequest->setOption(CURLOPT_ENCODING, ""); // allow all encodings, gzip etc.
        $this->curlRequest->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->curlRequest->setOption(CURLOPT_POST, true);
        $this->curlRequest->setOption(CURLOPT_POSTFIELDS, $payload);
        $this->curlRequest->setOption(CURLOPT_USERPWD, getenv("FENIX_BASIC_AUTH"));
        $this->curlRequest->setOption(
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload)
            )
        );
        $curlResponse = $this->curlRequest->execute();
        $http_code = $this->curlRequest->getInfo(CURLINFO_HTTP_CODE);

        if ($curlResponse === false || $http_code !== 200) {
            $info = $this->curlRequest->getInfo();
            $this->curlRequest->close();
            throw new Exception("Error occured during curl exec.\ncurl_getinfo returns:\n".print_r($info, true)."\n");
        }

        $this->curlRequest->close();
        $decoded = json_decode($curlResponse, true);

        if (isset($decoded["error"])) {
            throw new Exception("Error response from server:\n".print_r($decoded, true)."\n");
        }

        return $decoded;
    }

    private function parseResults(array $rawResults, array $inboundLocodes): array
    {
        $tools = new FenixTools();
        return array_filter(
            array_map(function ($result) use ($tools, $inboundLocodes) {
                $converted = null;
                try {
                    $converted = $tools->convert($result, $inboundLocodes);
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                    error_log($e->getTraceAsString());
                    if (isset($this->aino)) {
                        $this->aino->failure(
                            $ainoTimestamp,
                            "Fenix agent failed",
                            "Parse",
                            "timestamp",
                            [],
                            []
                        );
                    }
                }
                return $converted;
            }, $rawResults),
            function ($data) {
                return $data != null;
            }
        );
    }

    private function postResults(ApiConfig $apiConfig, array $results)
    {
        $countOk = 0;
        $countFailed = 0;

        $ainoTimestamp = gmdate("Y-m-d\TH:i:s\Z");

        foreach ($results as $result) {
            $ainoFlowId = $this->resultPoster->resultChecksum($apiConfig, $result);
            try {
                $this->resultPoster->postResult($apiConfig, $result);
                ++$countOk;
                if (isset($this->aino)) {
                    $this->aino->succeeded(
                        $ainoTimestamp,
                        "Fenix agent succeeded",
                        "Post",
                        "timestamp",
                        ["imo" => $result["imo"]],
                        [],
                        $ainoFlowId
                    );
                }
            } catch (\Exception $e) {
                ++$countFailed;
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
                if (isset($this->aino)) {
                    $this->aino->failure(
                        $ainoTimestamp,
                        "Fenix agent failed",
                        "Post",
                        "timestamp",
                        [],
                        [],
                        $ainoFlowId
                    );
                }
            }
        }

        return [
            "ok" => $countOk,
            "failed" => $countFailed
        ];
    }
}
