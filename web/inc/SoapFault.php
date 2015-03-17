SoapFault extends Exception {
/* Inherited properties */
protected string $message ;
protected int $code ;
protected string $file ;
protected int $line ;
/* Methods */
__construct ( string $faultcode , string $faultstring [, string $faultactor [, string $detail [, string $faultname [, string $headerfault ]]]] )
SoapFault ( string $faultcode , string $faultstring [, string $faultactor [, string $detail [, string $faultname [, string $headerfault ]]]] )
public string __toString ( void )
/* Inherited methods */
final public string Exception::getMessage ( void )
final public Exception Exception::getPrevious ( void )
final public mixed Exception::getCode ( void )
final public string Exception::getFile ( void )
final public int Exception::getLine ( void )
final public array Exception::getTrace ( void )
final public string Exception::getTraceAsString ( void )
public string Exception::__toString ( void )
final private void Exception::__clone ( void )
}