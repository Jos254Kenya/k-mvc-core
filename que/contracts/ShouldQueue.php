<?php

declare(strict_types=1);

namespace sigawa\mvccore\que\contracts;

/**
 * Marker interface for jobs/listeners that should
 * always be queued instead of running synchronously.
 */
interface ShouldQueue {}
