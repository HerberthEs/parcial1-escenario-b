<?php

namespace App\Adapters;

use RuntimeException;

/** Uso interno del adaptador: un Fault o una respuesta SOAP ilegible. Nunca sale del adaptador. */
class SoapFaultException extends RuntimeException {}
