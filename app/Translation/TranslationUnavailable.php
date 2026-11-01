<?php

namespace App\Translation;

use RuntimeException;

/** The backend is down, rejected the request or ran out of quota; the next driver should be tried. */
class TranslationUnavailable extends RuntimeException {}
