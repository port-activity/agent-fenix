<?php

const FENIX_AGENT_FROM_OFFSET_MINUTES = "FENIX_AGENT_FROM_OFFSET_MINUTES";
const FENIX_AGENT_TO_OFFSET_MINUTES = "FENIX_AGENT_TO_OFFSET_MINUTES";

$envNames = array(FENIX_AGENT_FROM_OFFSET_MINUTES, FENIX_AGENT_TO_OFFSET_MINUTES);

foreach ($envNames as $name) {
    if (!getenv($name)) {
        throw new \Exception("Please set env " . $name);
    }
}

return array(
    "from_offset_minutes" => getenv(FENIX_AGENT_FROM_OFFSET_MINUTES),
    "to_offset_minutes" => getenv(FENIX_AGENT_TO_OFFSET_MINUTES)
);
