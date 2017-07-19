<?php

namespace App\Http\Controllers;

use AWS;
use Log;
use Redirect;

class SponsorsController extends Controller
{
    /**
     * Redirects user from secret packet link to the S3 url.
     * @param $secret
     * @return \Illuminate\Http\RedirectResponse|string
     * @codeCoverageIgnore
     */
    public function packet($secret)
    {
        if ($secret != env('PACKET_SECRET')) {
            Log::info('sponsorship_packet_auth_fail');

            return 'not authorized';
        }
        Log::info('sponsorship_packet_read');
        $s3 = AWS::createClient('s3');
        $cmd = $s3->getCommand('getObject', [
            'Bucket' => getenv('S3_BUCKET'),
            'Key'    => getenv('S3_PREFIX').'/packet.pdf',
            'ResponseContentType' => 'application/pdf',
        ]);
        $request = $s3->createPresignedRequest($cmd, '+7 days');

        return Redirect::to((string) $request->getUri());
    }
}
