<?php

namespace pocketcloud\cloud\bridge\network\packet;

enum RequestPacketFailureReason {

    case THEN_CRASHED;
    case REQUEST_TIMEOUT;
    case EVENT_CANCELLED;
}