<?php

namespace SMA\PAA\AGENT\FENIX;

use PHPUnit\Framework\TestCase;

use SMA\PAA\FAKECURL\FakeCurlRequest;
use SMA\PAA\FAKERESULTPOSTER\FakeResultPoster;
use SMA\PAA\AGENT\ApiConfig;

final class FenixTest extends TestCase
{
    public function testExecute(): void
    {
        $curlRequest = new FakeCurlRequest();
        $resultPoster = new FakeResultPoster();
        $config = array(
            "from_offset_minutes" => "1440",
            "to_offset_minutes" => "1440"
        );
        $fenix = new Fenix($curlRequest, $resultPoster, null, $config);
        $curlRequest->getInfoReturn[CURLINFO_HTTP_CODE] = 200;
        $curlRequest->executeReturn = file_get_contents(__DIR__ . "/ValidServerData.json");
        $fenix->execute(
            new ApiConfig("key", "http://url/foo", ["foo"]),
            9295347,
            array("SEGVX")
        );
        // file_put_contents(__DIR__ . "/ValidPosterData.json", json_encode($resultPoster->results, JSON_PRETTY_PRINT));
        $this->assertEquals(
            json_decode(file_get_contents(__DIR__ . "/ValidPosterData.json"), true),
            $resultPoster->results
        );
    }
}
