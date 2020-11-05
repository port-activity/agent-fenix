<?php
namespace SMA\PAA\AGENT\FENIX;

use Exception;
use DateTimeInterface;
use DateTime;

use const SMA\PAA\SERVICE\STATUS_PORT;

class FenixTools
{

    // These are tighly matched to result data statusIds
    const STATUS_01_UNKNOWN                     = 1;
    const STATUS_02_UNKNOWN                     = 2;
    const STATUS_03_BLOCK_PRELIMINARY_ASSIGNED  = 3;
    const STATUS_04_BLOCK_DEFINITEVILY_ASSIGNED = 4;
    const STATUS_05_BLOCK_STARTED               = 5;
    const STATUS_06_BLOCK_FINISHED              = 6;
    const STATUS_07_ORDER_PRELIMINARY           = 7;
    const STATUS_08_ORDER_DEFINITIVE            = 8;
    const STATUS_09_ORDER_CONFIRMED             = 9;
    const STATUS_10_ORDER_STARTED               = 10;
    const STATUS_11_ORDER_FINISHED              = 11;
    const STATUS_12_ORDER_UNKNOWN               = 12;
    const STATUS_13_ORDER_UNKNOWN               = 13;
    const STATUS_14_ORDER_UNKNOWN               = 14;
    const STATUS_15_ORDER_DELETED               = 15;

    private function dataFromLastEventBlock(array $data, string $key): string
    {
        $res = "";

        if (!empty($data["eventBlocks"])) {
            $eventBlock = end($data["eventBlocks"]);

            if (!empty($eventBlock[$key])) {
                $res = $eventBlock[$key];
            }
        }

        return $res;
    }

    public function convert(array $data, array $locodes)
    {
        $usePilotageStart = [
            self::STATUS_07_ORDER_PRELIMINARY,
            self::STATUS_08_ORDER_DEFINITIVE,
            self::STATUS_09_ORDER_CONFIRMED
        ];

        $map = [
            self::STATUS_07_ORDER_PRELIMINARY => "Pilotage_Commenced",
            self::STATUS_08_ORDER_DEFINITIVE  => "Pilotage_Commenced",
            self::STATUS_09_ORDER_CONFIRMED   => "Pilotage_Commenced",
            self::STATUS_10_ORDER_STARTED     => "Pilotage_Commenced",
            self::STATUS_11_ORDER_FINISHED    => "Pilotage_Completed"
        ];

        $typeMap = [
            self::STATUS_07_ORDER_PRELIMINARY => "Estimated",
            self::STATUS_08_ORDER_DEFINITIVE  => "Estimated",
            self::STATUS_09_ORDER_CONFIRMED   => "Planned",
            self::STATUS_10_ORDER_STARTED     => "Actual",
            self::STATUS_11_ORDER_FINISHED    => "Actual",
        ];

        $keyMap = [
            self::STATUS_10_ORDER_STARTED     => "actualStartTime",
            self::STATUS_11_ORDER_FINISHED    => "actualEndTime"
        ];

        if (!array_key_exists("statusId", $data)) {
            throw new \Exception("Missing statusId in data: " . print_r($data, true));
        }

        if (in_array($data["unLocode"], $locodes)
            && array_key_exists($data["statusId"], $map)
        ) {
            $state = $map[$data["statusId"]];

            $fenixTime = "";
            if (in_array($data["statusId"], $usePilotageStart)) {
                if (isset($data["orderedPilotageStart"])) {
                    $fenixTime = $data["orderedPilotageStart"];
                }
            } elseif ($data["statusId"] === self::STATUS_10_ORDER_STARTED ||
                      $data["statusId"] === self::STATUS_11_ORDER_FINISHED) {
                        $fenixTime = $this->dataFromLastEventBlock($data, $keyMap[$data["statusId"]]);
            } else {
                if (isset($data["timestamp"])) {
                    $fenixTime = preg_replace('/\..*?(\+|\-)/', "$1", $data["timestamp"]);
                }
            }

            $time = "";
            if ($fenixTime !== "") {
                $dateTime = DateTime::createFromFormat("Y-m-d\TH:i:sP", $fenixTime);
                if ($dateTime !== false) {
                    $time = $dateTime->format("Y-m-d\TH:i:sO");
                } else {
                    throw new \Exception("Can't create DateTime from time " . $fenixTime);
                }
            } else {
                throw new \Exception(
                    "Can't resolve time for when resoving time for state '"
                    . $state
                    . "'. "
                    . print_r($data, true)
                );
            }

            $payload = [];

            $payload["source"] = "Fenix";

            if (isset($data["swPortVisitId"])) {
                $payload["external_id"] = $data["swPortVisitId"];
            }

            if (isset($data["swPilotageType"])) {
                if ($data["swPilotageType"] === "ARR") {
                    $payload["direction"] = "inbound";
                } elseif ($data["swPilotageType"] === "DEP") {
                    $payload["direction"] = "outbound";
                }
            }

            if (isset($data["shipBeam"])) {
                $payload["vessel_beam"] = $data["shipBeam"];
            }

            if (isset($data["shipCurrentDraft"])) {
                $payload["vessel_draft"] = $data["shipCurrentDraft"];
            }

            if (isset($data["shipLoa"])) {
                $payload["vessel_loa"] = $data["shipLoa"];
            }

            $payload["original_message"] = $data;

            return [
                "imo"           => $data["imo"],
                "vessel_name"   => "",
                "time_type"     => $typeMap[$data["statusId"]],
                "state"         => $state,
                "time"          => $time,
                "payload"       => $payload
            ];
        }
    }
}
