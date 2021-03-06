<?php

require_once( AMP__DIR__ . '/includes/lib/class-faster-image-b52f1a8-invalid-image-exception.php' );
require_once( AMP__DIR__ . '/includes/lib/class-faster-image-b52f1a8-image-parser.php' );
require_once( AMP__DIR__ . '/includes/lib/class-stream-17b32f3-stream.php' );
require_once( AMP__DIR__ . '/includes/lib/class-stream-17b32f3-stream-buffer-too-small-exception.php' );

/**
 * FasterImage - Because sometimes you just want the size, and you want them in
 * parallel!
 *
 * Based on the PHP stream implementation by Tom Moor (http://tommoor.com)
 * which was based on the original Ruby Implementation by Steven Sykes
 * (https://github.com/sdsykes/fastimage)
 *
 * MIT Licensed
 *
 * @version 0.01
 */
class Faster_Image_B52f1a8_Faster_Image
{
    /**
     * The default timeout
     *
     * @var int
     */
    protected $timeout = 4;

	public function __construct( $user_agent )
	{
		$this->user_agent = $user_agent;
	}

	/**
     * Get the size of each of the urls in a list
     *
     * @param array $urls
     *
     * @return array
     * @throws \Exception
     */
    public function batch(array $urls)
    {

        $multi   = curl_multi_init();
        $results = array();

        // Create the curl handles and add them to the multi_request
        foreach ( array_values($urls) as $count => $uri ) {

            $results[$uri] = array();

            $$count = $this->handle($uri, $results[$uri]);

            $code = curl_multi_add_handle($multi, $$count);

            if ( $code != CURLM_OK ) {
                throw new \Exception("Curl handle for $uri could not be added");
            }
        }

        // Perform the requests
        do {
            while ( ($mrc = curl_multi_exec($multi, $active)) == CURLM_CALL_MULTI_PERFORM ) ;
            if ( $mrc != CURLM_OK && $mrc != CURLM_CALL_MULTI_PERFORM ) {
                throw new \Exception("Curl error code: $mrc");
            }

            if ( $active && curl_multi_select($multi) === -1 ) {
                // Perform a usleep if a select returns -1.
                // See: https://bugs.php.net/bug.php?id=61141
                usleep(250);
            }
        } while ( $active );

        // Figure out why individual requests may have failed
        foreach ( array_values($urls) as $count => $uri ) {
            $error = curl_error($$count);

            if ( $error ) {
                $results[$uri]['failure_reason'] = $error;
            }
        }

        return $results;
    }

    /**
     * @param $seconds
     */
    public function setTimeout($seconds)
    {
        $this->timeout = $seconds;
    }

    /**
     * Create the handle for the curl request
     *
     * @param $url
     * @param $result
     *
     * @return resource
     */
    protected function handle($url, & $result)
    {
        $stream           = new Stream_17b32f3_Stream();
        $parser           = new Faster_Image_B52f1a8_Image_Parser($stream);
        $result['rounds'] = 0;
        $result['bytes']  = 0;
        $result['size']   = 'failed';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_BUFFERSIZE, 256);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
                "Cache-Control: max-age=0",
                "Connection: keep-alive",
                "Keep-Alive: 300",
                "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
                "Accept-Language: en-us,en;q=0.5",
                "Pragma: ", // browsers keep this blank.
            )
        );
        curl_setopt($ch, CURLOPT_ENCODING, "");

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $str) use (& $result, & $parser, & $stream, $url) {

            $result['rounds']++;
            $result['bytes'] += strlen($str);

            $stream->write($str);

            try {
                // store the type in the result array by looking at the bits
                $result['type'] = $parser->parseType();

                /*
                 * We try here to parse the buffer of characters we already have
                 * for the size.
                 */
                $result['size'] = $parser->parseSize() ?: 'failed';
            }
            catch (Stream_17b32f3_Stream_Buffer_Too_Small_Exception $e) {
                /*
                 * If this exception is thrown, we don't have enough of the stream buffered
                 * so in order to tell curl to keep streaming we need to return the number
                 * of bytes we have already handled
                 *
                 * We set the 'size' to 'failed' in the case that we've done
                 * the entire image and we couldn't figure it out. Otherwise
                 * it'll get overwritten with the next round.
                 */
                $result['size'] = 'failed';

                return strlen($str);
            }
            catch (Faster_Image_B52f1a8_Invalid_Image_Exception $e) {

                /*
                 * This means we've determined that we're lost and don't know
                 * how to parse this image.
                 *
                 * We set the size to invalid and move on
                 */
                $result['size'] = 'invalid';

                return -1;
            }


            /*
             * We return -1 to abort the transfer when we have enough buffered
             * to find the size
             */
            //
            // hey curl! this is an error. But really we just are stopping cause
            // we already have what we wwant
            return -1;
        });

        return $ch;
    }
}