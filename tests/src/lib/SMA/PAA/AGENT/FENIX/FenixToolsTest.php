<?php

namespace SMA\PAA\AGENT\FENIX;

use PHPUnit\Framework\TestCase;

use SMA\PAA\FAKECURL\FakeCurlRequest;
use SMA\PAA\FAKERESULTPOSTER\FakeResultPoster;
use SMA\PAA\AGENT\ApiConfig;

final class FenixToolsTest extends TestCase
{
    public function testOrderPreliminaryParsing(): void
    {
        $inJson = file_get_contents(__DIR__ . "/FenixToolsOrderPreliminaryIn.json");
        $inData = json_decode($inJson, true);
        $outJson = file_get_contents(__DIR__ . "/FenixToolsOrderPreliminaryOut.json");
        $outData = json_decode($outJson, true);

        $tools = new FenixTools();

        $this->assertEquals(
            $outData,
            $tools->convert($inData, array("SEGVX"))
        );
    }

    public function testOrderConfirmedParsing(): void
    {
        $inJson = file_get_contents(__DIR__ . "/FenixToolsOrderConfirmedIn.json");
        $inData = json_decode($inJson, true);
        $outJson = file_get_contents(__DIR__ . "/FenixToolsOrderConfirmedOut.json");
        $outData = json_decode($outJson, true);

        $tools = new FenixTools();

        $this->assertEquals(
            $outData,
            $tools->convert($inData, array("SEGVX"))
        );
    }

    public function testOrderStartedCommencedParsing(): void
    {
        $inJson = file_get_contents(__DIR__ . "/FenixToolsOrderStartedIn.json");
        $inData = json_decode($inJson, true);
        $outJson = file_get_contents(__DIR__ . "/FenixToolsOrderStartedOut.json");
        $outData = json_decode($outJson, true);

        $tools = new FenixTools();

        $this->assertEquals(
            $outData,
            $tools->convert($inData, array("SEGVX"))
        );
    }

    public function testOrderStartedCommencedLastBlockNullTimeParsing(): void
    {
        $inJson = file_get_contents(__DIR__ . "/FenixToolsOrderStartedLastBlockNullTimeIn.json");
        $inData = json_decode($inJson, true);
        $outJson = file_get_contents(__DIR__ . "/FenixToolsOrderStartedLastBlockNullTimeOut.json");
        $outData = json_decode($outJson, true);

        $tools = new FenixTools();

        $this->assertEquals(
            $outData,
            $tools->convert($inData, array("SEGVX"))
        );
    }

    public function testOrderFinishedParsing(): void
    {
        $inJson = file_get_contents(__DIR__ . "/FenixToolsOrderFinishedIn.json");
        $inData = json_decode($inJson, true);
        $outJson = file_get_contents(__DIR__ . "/FenixToolsOrderFinishedOut.json");
        $outData = json_decode($outJson, true);

        $tools = new FenixTools();

        $this->assertEquals(
            $outData,
            $tools->convert($inData, array("SEGVX"))
        );
    }
}
