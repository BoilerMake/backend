<?php

namespace App\Http\Controllers;

use AWS;
use Log;

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

        // Get the object
        $result = $s3->getObject([
            'Bucket' => getenv('S3_BUCKET'),
            'Key'    => getenv('S3_PREFIX').'/packet.pdf',
        ]);

        // Display the object in the browser
        header("Content-Type: {$result['ContentType']}");
        header('Content-Disposition: inline; filename="BoilerMake_sponsorship_packet.pdf"');
        echo $result['Body'];
    }
}
